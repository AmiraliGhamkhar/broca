<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount(['enrollments', 'subscriptions', 'quizAttempts'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.addcslashes((string) $request->string('q'), '\\%_').'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('role'), function ($q) use ($request) {
                if ($request->string('role') === 'admin') {
                    $q->where('is_admin', true);
                } elseif ($request->string('role') === 'learner') {
                    $q->where('is_admin', false);
                }
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load([
            'enrollments.course.subject',
            'subscriptions.plan',
            'quizAttempts.quiz',
            'consents',
        ]);

        return view('admin.users.show', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,suspended'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        // Guard against self-lockout / self-demotion
        if ($user->id === $request->user()->id && $validated['status'] === 'suspended') {
            return back()->withErrors(['status' => 'نمی‌توانید حساب کاربری خودتان را تعلیق کنید.']);
        }

        if ($user->id === $request->user()->id && ! $request->boolean('is_admin')) {
            return back()->withErrors(['is_admin' => 'نمی‌توانید نقش مدیریت را از حساب خودتان سلب کنید.']);
        }

        // Last-admin invariant: the system must never lose its final
        // administrator, regardless of who performs the demotion.
        if ($user->is_admin && ! $request->boolean('is_admin') && User::query()->where('is_admin', true)->count() <= 1) {
            return back()->withErrors(['is_admin' => 'حداقل یک مدیر باید در سیستم باقی بماند.']);
        }

        $user->forceFill([
            'status' => $validated['status'],
            'is_admin' => $request->boolean('is_admin'),
        ])->save();

        // Suspension must bite now, not on the user's next request: the
        // session rows are deleted so a live tab loses its cookie immediately
        // instead of keeping read access until `active` middleware next runs.
        // Best-effort — a non-database session driver has no table to clear.
        try {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->delete();
        } catch (\Throwable) {
            // file/redis/cache drivers: EnsureActive still locks them out.
        }

        return back()->with('status', 'اطلاعات کاربر با موفقیت به‌روزرسانی شد.');
    }
}
