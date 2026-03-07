<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlumniContent extends Model
{
    protected $fillable = [
        'alumni_name',
        'alumni_description',
        'banner_heading',
        'banners',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'banners' => 'array',
        ];
    }

    public function toApiArray(): array
    {
        return [
            'alumni_name' => $this->alumni_name,
            'alumni_description' => $this->alumni_description,
            'banner_heading' => $this->banner_heading,
            'banners' => collect($this->banners ?? [])
                ->map(fn ($banner) => [
                    'image_url' => data_get($banner, 'image_url'),
                    'heading' => data_get($banner, 'heading'),
                ])
                ->values()
                ->all(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
