<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    public function index(): View
    {
        $videos = Video::with('course')->where('status', 'published')->paginate(15);
        return view('admin.videos.index', compact('videos'));
    }

    public function create(): View
    {
        return view('admin.videos.edit', ['video' => new Video()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_seconds' => 'nullable|integer|min:0',
            'is_free_designated' => 'nullable|boolean',
            'status' => 'required|in:draft,in_review,published,archived',
        ]);
        $data['slug'] = Str::slug($data['title']);
        Video::create($data);
        return redirect()->route('admin.videos.index')->with('success', 'ویدیو ایجاد شد.');
    }

    public function edit(Video $video): View
    {
        return view('admin.videos.edit', compact('video'));
    }

    public function update(Request $request, Video $video): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_seconds' => 'nullable|integer|min:0',
            'is_free_designated' => 'nullable|boolean',
            'status' => 'required|in:draft,in_review,published,archived',
        ]);
        $video->update($data);
        return redirect()->route('admin.videos.index')->with('success', 'ویدیو به‌روزرسانی شد.');
    }

    public function destroy(Video $video): RedirectResponse
    {
        $video->delete();
        return redirect()->route('admin.videos.index')->with('success', 'ویدیو حذف شد.');
    }
}
