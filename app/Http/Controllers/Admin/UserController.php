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
                $term = '%' . addcslashes((string) $request->string('q'), '\\%_') . '%';
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

        // Last-admin invariant under concurrency: the count check and the
        // demotion must happen inside one transaction against a locked read,
        // otherwise two simultaneous demotions can each observe "2 admins"
        // and leave the system with none.
        [$saved, $error] = DB::transaction(function () use ($user, $request, $validated): array {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->first();

            if ($locked === null) {
                return [false, null];
            }

            if ($locked->id === $request->user()->id && $validated['status'] === 'suspended') {
                return [false, 'status'];
            }

            if ($locked->id === $request->user()->id && ! $request->boolean('is_admin')) {
                return [false, 'is_admin'];
            }

            if ($locked->is_admin && ! $request->boolean('is_admin') && User::query()->where('is_admin', true)->count() <= 1) {
                return [false, 'last_admin'];
            }

            $locked->forceFill([
                'status' => $validated['status'],
                'is_admin' => $request->boolean('is_admin'),
            ])->save();

            return [true, null];
        });

        if ($saved) {
            return back()->with('status', 'اطلاعات کاربر با موفقیت به‌روزرسانی شد.');
        }

        return back()->withErrors(match ($error) {
            'status' => ['status' => 'نمی‌توانید حساب کاربری خودتان را تعلیق کنید.'],
            'is_admin' => ['is_admin' => 'نمی‌توانید نقش مدیریت را از حساب خودتان سلب کنید.'],
            'last_admin' => ['is_admin' => 'حداقل یک مدیر باید در سیستم باقی بماند.'],
            default => ['status' => 'کاربر یافت نشد.'],
        });
    }
}
