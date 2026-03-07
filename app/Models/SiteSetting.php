<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    private const LOGO_DISK = 'site_assets';

    protected $fillable = [
        'company_name',
        'brand_title',
        'brand_subtitle',
        'hero_eyebrow',
        'hero_description',
        'hero_stat_value',
        'hero_stat_label',
        'banner_heading',
        'top_header_address',
        'top_header_email',
        'top_header_phone',
        'logo_path',
        'linkedin_url',
        'facebook_url',
        'instagram_url',
        'whatsapp_url',
        'created_by',
        'updated_by',
    ];

    public function toApiArray(): array
    {
        $logoUrl = null;

        if ($this->logo_path) {
            if (Storage::disk(self::LOGO_DISK)->exists($this->logo_path)) {
                $logoUrl = Storage::disk(self::LOGO_DISK)->url($this->logo_path);
            } elseif (Storage::disk('public')->exists($this->logo_path)) {
                $logoUrl = Storage::disk('public')->url($this->logo_path);
            }
        }

        return [
            'company_name' => $this->company_name,
            'brand_title' => $this->brand_title,
            'brand_subtitle' => $this->brand_subtitle,
            'hero_eyebrow' => $this->hero_eyebrow,
            'hero_description' => $this->hero_description,
            'hero_stat_value' => $this->hero_stat_value,
            'hero_stat_label' => $this->hero_stat_label,
            'banner_heading' => $this->banner_heading,
            'top_header_address' => $this->top_header_address,
            'top_header_email' => $this->top_header_email,
            'top_header_phone' => $this->top_header_phone,
            'logo_url' => $logoUrl,
            'linkedin_url' => $this->linkedin_url,
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,
            'whatsapp_url' => $this->whatsapp_url,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
