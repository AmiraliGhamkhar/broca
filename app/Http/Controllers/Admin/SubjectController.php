<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        $subjects = Subject::withCount('courses')
            ->orderBy('sort_order')
            ->get();

        return view('admin.subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        return view('admin.subjects.edit', ['subject' => new Subject(['is_visible' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $subject = new Subject($validated);
        $subject->slug = Slug::unique($validated['name'], fn (string $slug) => Subject::where('slug', $slug)->exists());
        $subject->is_visible = $request->boolean('is_visible', true);
        $subject->save();

        return redirect()->route('admin.subjects.index')->with('status', 'مبحث/درس‌نامه با موفقیت ایجاد شد.');
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', ['subject' => $subject]);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $subject->fill($validated);
        if ($validated['name'] !== $subject->getOriginal('name')) {
            $subject->slug = Slug::unique($validated['name'], fn (string $slug) => Subject::where('slug', $slug)->where('id', '!=', $subject->id)->exists());
        }
        $subject->is_visible = $request->boolean('is_visible');
        $subject->save();

        return redirect()->route('admin.subjects.index')->with('status', 'مبحث با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->courses()->exists()) {
            return back()->withErrors(['error' => 'امکان حذف مبحث دارای دوره وجود ندارد. ابتدا دوره‌های آن را منتقل یا حذف کنید.']);
        }

        $subject->delete();

        return redirect()->route('admin.subjects.index')->with('status', 'مبحث با موفقیت حذف شد.');
    }
}
