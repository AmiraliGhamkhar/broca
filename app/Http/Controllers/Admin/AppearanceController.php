<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AppearanceController extends Controller
{
    public function edit(): View
    {
        return view('admin.appearance.edit', ['settings' => SiteSetting::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'hero' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'hero_image_alt' => ['required', 'string', 'max:255'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_hero' => ['nullable', 'boolean'],
        ]);

        $settings = SiteSetting::current();
        $settings->hero_image_alt = $data['hero_image_alt'];

        if ($request->boolean('remove_logo')) {
            $this->removeStoredFile($settings->logo_image_path);
            $settings->logo_image_path = null;
        }
        if ($request->boolean('remove_hero')) {
            $this->removeStoredFile($settings->hero_image_path);
            $settings->hero_image_path = null;
        }
        if ($request->hasFile('logo')) {
            $newPath = Storage::disk('public')->url($request->file('logo')->store('site-assets/logo', 'public'));
            $this->removeStoredFile($settings->logo_image_path);
            $settings->logo_image_path = $newPath;
        }
        if ($request->hasFile('hero')) {
            $newPath = Storage::disk('public')->url($request->file('hero')->store('site-assets/hero', 'public'));
            $this->removeStoredFile($settings->hero_image_path);
            $settings->hero_image_path = $newPath;
        }

        $settings->save();

        return back()->with('status', 'ظاهر سایت با موفقیت به‌روزرسانی شد.');
    }

    private function removeStoredFile(?string $url): void
    {
        if ($url && str_contains($url, '/storage/')) {
            Storage::disk('public')->delete((string) str($url)->after('/storage/'));
        }
    }
}
