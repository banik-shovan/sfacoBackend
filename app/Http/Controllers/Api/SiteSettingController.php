<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    private const LOGO_DISK = 'site_assets';

    public function show(): JsonResponse
    {
        $setting = SiteSetting::query()->first();

        return response()->json([
            'settings' => $setting?->toApiArray() ?? $this->emptySettings(),
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'brand_title' => ['nullable', 'string', 'max:255'],
            'brand_subtitle' => ['nullable', 'string', 'max:255'],
            'hero_eyebrow' => ['nullable', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string', 'max:2000'],
            'hero_stat_value' => ['nullable', 'string', 'max:40'],
            'hero_stat_label' => ['nullable', 'string', 'max:255'],
            'banner_heading' => ['nullable', 'string', 'max:255'],
            'top_header_address' => ['nullable', 'string', 'max:255'],
            'top_header_email' => ['nullable', 'email', 'max:255'],
            'top_header_phone' => ['nullable', 'string', 'max:50'],
            'linkedin_url' => ['nullable', 'url', 'max:2048'],
            'facebook_url' => ['nullable', 'url', 'max:2048'],
            'instagram_url' => ['nullable', 'url', 'max:2048'],
            'whatsapp_url' => ['nullable', 'url', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $setting = SiteSetting::query()->firstOrCreate([], [
            'created_by' => $request->user()->id,
        ]);

        if (($validated['remove_logo'] ?? false) && $setting->logo_path) {
            $this->deleteLogo($setting->logo_path);
            $setting->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                $this->deleteLogo($setting->logo_path);
            }

            $setting->logo_path = $request->file('logo')->store('site-settings', self::LOGO_DISK);
        }

        $setting->fill([
            'company_name' => $validated['company_name'] ?? $setting->company_name,
            'brand_title' => $validated['brand_title'] ?? $setting->brand_title,
            'brand_subtitle' => $validated['brand_subtitle'] ?? $setting->brand_subtitle,
            'hero_eyebrow' => $validated['hero_eyebrow'] ?? $setting->hero_eyebrow,
            'hero_description' => $validated['hero_description'] ?? $setting->hero_description,
            'hero_stat_value' => $validated['hero_stat_value'] ?? $setting->hero_stat_value,
            'hero_stat_label' => $validated['hero_stat_label'] ?? $setting->hero_stat_label,
            'banner_heading' => $validated['banner_heading'] ?? $setting->banner_heading,
            'top_header_address' => $validated['top_header_address'] ?? $setting->top_header_address,
            'top_header_email' => $validated['top_header_email'] ?? $setting->top_header_email,
            'top_header_phone' => $validated['top_header_phone'] ?? $setting->top_header_phone,
            'linkedin_url' => $validated['linkedin_url'] ?? $setting->linkedin_url,
            'facebook_url' => $validated['facebook_url'] ?? $setting->facebook_url,
            'instagram_url' => $validated['instagram_url'] ?? $setting->instagram_url,
            'whatsapp_url' => $validated['whatsapp_url'] ?? $setting->whatsapp_url,
            'updated_by' => $request->user()->id,
        ])->save();

        return response()->json([
            'message' => 'Site settings saved successfully.',
            'settings' => $setting->fresh()->toApiArray(),
        ]);
    }

    private function emptySettings(): array
    {
        return [
            'company_name' => null,
            'brand_title' => null,
            'brand_subtitle' => null,
            'hero_eyebrow' => null,
            'hero_description' => null,
            'hero_stat_value' => null,
            'hero_stat_label' => null,
            'banner_heading' => null,
            'top_header_address' => null,
            'top_header_email' => null,
            'top_header_phone' => null,
            'logo_url' => null,
            'linkedin_url' => null,
            'facebook_url' => null,
            'instagram_url' => null,
            'whatsapp_url' => null,
            'updated_at' => null,
        ];
    }

    private function deleteLogo(string $path): void
    {
        Storage::disk(self::LOGO_DISK)->delete($path);

        // Clean up older files that were stored on the Laravel public disk.
        Storage::disk('public')->delete($path);
    }
}
