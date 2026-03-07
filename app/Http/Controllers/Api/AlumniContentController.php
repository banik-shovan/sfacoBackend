<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlumniContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlumniContentController extends Controller
{
    private const ASSET_DISK = 'site_assets';

    public function show(Request $request): JsonResponse
    {
        $content = AlumniContent::query()->first();

        return response()->json([
            'content' => $content ? $this->serializeContent($content, $request) : $this->emptyContent(),
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alumni_name' => ['nullable', 'string', 'max:255'],
            'alumni_description' => ['nullable', 'string', 'max:4000'],
            'banner_heading' => ['nullable', 'string', 'max:255'],
            'banners' => ['nullable', 'array', 'max:20'],
            'banners.*.image_url' => ['required_with:banners', 'nullable', 'url', 'max:2048'],
            'banners.*.heading' => ['nullable', 'string', 'max:255'],
        ]);

        $content = AlumniContent::query()->firstOrCreate([], [
            'created_by' => $request->user()->id,
        ]);

        $content->fill([
            'alumni_name' => $validated['alumni_name'] ?? $content->alumni_name,
            'alumni_description' => $validated['alumni_description'] ?? $content->alumni_description,
            'banner_heading' => $validated['banner_heading'] ?? $content->banner_heading,
            'banners' => $this->normalizeBanners($validated['banners'] ?? $content->banners ?? []),
            'updated_by' => $request->user()->id,
        ])->save();

        return response()->json([
            'message' => 'Alumni content saved successfully.',
            'content' => $this->serializeContent($content->fresh(), $request),
        ]);
    }

    public function uploadBanner(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'banner' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $path = $validated['banner']->store('alumni-banners', self::ASSET_DISK);

        return response()->json([
            'message' => 'Banner uploaded successfully.',
            'image_url' => $this->resolvePublicImageUrl($path, $request),
            'path' => $path,
        ], 201);
    }

    private function emptyContent(): array
    {
        return [
            'alumni_name' => null,
            'alumni_description' => null,
            'banner_heading' => null,
            'banners' => [],
            'updated_at' => null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $banners
     * @return array<int, array<string, string|null>>
     */
    private function normalizeBanners(array $banners): array
    {
        return collect($banners)
            ->map(function ($banner) {
                $imageUrl = trim((string) data_get($banner, 'image_url', ''));
                $heading = trim((string) data_get($banner, 'heading', ''));

                if ($imageUrl === '') {
                    return null;
                }

                return [
                    'image_url' => $this->normalizeImageUrlForStorage($imageUrl),
                    'heading' => $heading !== '' ? $heading : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function serializeContent(AlumniContent $content, Request $request): array
    {
        $data = $content->toApiArray();
        $data['banners'] = collect($data['banners'] ?? [])
            ->map(fn ($banner) => [
                'image_url' => $this->resolveRuntimeImageUrl(data_get($banner, 'image_url'), $request),
                'heading' => data_get($banner, 'heading'),
            ])
            ->filter(fn ($banner) => ! empty($banner['image_url']))
            ->values()
            ->all();

        return $data;
    }

    private function resolvePublicImageUrl(string $path, Request $request): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/').'/uploads/'.ltrim($path, '/');
    }

    private function resolveRuntimeImageUrl(?string $rawUrl, Request $request): ?string
    {
        if (! $rawUrl) {
            return null;
        }

        $url = trim($rawUrl);
        $base = rtrim($request->getSchemeAndHttpHost(), '/');

        if (str_starts_with($url, '/')) {
            return $base.$url;
        }

        if (preg_match('/^https?:\/\//i', $url) !== 1) {
            return $base.'/'.ltrim($url, '/');
        }

        $parts = parse_url($url);
        $host = $parts['host'] ?? null;

        if ($host === 'localhost') {
            $path = $parts['path'] ?? '';
            $query = isset($parts['query']) ? '?'.$parts['query'] : '';
            return $base.$path.$query;
        }

        return $url;
    }

    private function normalizeImageUrlForStorage(string $url): string
    {
        if (preg_match('/^https?:\/\/[^\/]+(\/uploads\/.+)$/i', $url, $matches) === 1) {
            return $matches[1];
        }

        return $url;
    }
}
