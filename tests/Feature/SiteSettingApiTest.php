<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SiteSettingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_logo_on_public_uploads_disk(): void
    {
        Storage::fake('site_assets');

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'is_approved' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->post('/api/admin/site-settings', [
            'company_name' => 'SFACO',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('settings.company_name', 'SFACO');

        $logoPath = \App\Models\SiteSetting::query()->firstOrFail()->logo_path;

        Storage::disk('site_assets')->assertExists($logoPath);

        $this->getJson('/api/site-settings')
            ->assertOk()
            ->assertJsonPath('settings.company_name', 'SFACO')
            ->assertJsonPath(
                'settings.logo_url',
                Storage::disk('site_assets')->url($logoPath)
            );
    }
}
