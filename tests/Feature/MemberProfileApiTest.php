<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_save_and_fetch_profile(): void
    {
        Storage::fake('public');

        $member = User::factory()->create([
            'role' => 'member',
            'is_active' => true,
            'is_approved' => true,
        ]);

        Sanctum::actingAs($member);

        $response = $this->post('/api/member/profile', [
            'name' => 'Member Name',
            'email' => 'member@example.com',
            'date_of_birth' => '1990-05-10',
            'nid_passport_number' => 'NID-12345',
            'gender' => 'male',
            'whatsapp_number' => '8801712345678',
            'current_address' => 'Current Address',
            'permanent_address' => 'Permanent Address',
            'articled_period_from' => '2015-01',
            'articled_period_to' => '2017-12',
            'principal_supervisor_name' => 'Supervisor Name',
            'icab_registration_no' => 'ICAB-101',
            'current_organization' => 'SFACO',
            'designation' => 'Manager',
            'ca_status' => 'qualified',
            'profile_photo' => UploadedFile::fake()->image('photo.jpg'),
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('profile.email', 'member@example.com')
            ->assertJsonPath('profile.is_complete', true)
            ->assertJsonPath('profile.missing_required_fields', []);

        $photoPath = $member->fresh()->memberProfile->profile_photo_path;

        $this->assertNotNull($photoPath);
        Storage::disk('public')->assertExists($photoPath);

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'name' => 'Member Name',
            'email' => 'member@example.com',
        ]);

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $member->id,
            'whatsapp_number' => '8801712345678',
            'ca_status' => 'qualified',
            'articled_period_from' => '2015-01',
            'articled_period_to' => '2017-12',
        ]);

        $this->getJson('/api/member/profile')
            ->assertOk()
            ->assertJsonPath('profile.current_organization', 'SFACO');
    }

    public function test_admin_can_view_and_update_member_profile(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'is_approved' => true,
        ]);

        $member = User::factory()->create([
            'role' => 'member',
            'is_active' => true,
            'is_approved' => true,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/members/{$member->id}/profile", [
            'name' => 'Edited Member',
            'email' => 'edited-member@example.com',
            'whatsapp_number' => '8801812345678',
            'current_address' => 'Admin Updated Address',
        ])
            ->assertOk()
            ->assertJsonPath('profile.name', 'Edited Member')
            ->assertJsonPath('profile.email', 'edited-member@example.com');

        $this->getJson("/api/admin/members/{$member->id}/profile")
            ->assertOk()
            ->assertJsonPath('profile.current_address', 'Admin Updated Address')
            ->assertJsonPath('profile.missing_required_fields.0', 'date_of_birth');
    }

    public function test_admin_profile_routes_reject_non_member_targets(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'is_approved' => true,
        ]);

        $otherAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'is_approved' => true,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/members/{$otherAdmin->id}/profile", [
            'current_address' => 'Should Fail',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only member accounts can be managed here.');
    }

    public function test_member_profile_schema_is_available_to_authenticated_users(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'is_active' => true,
            'is_approved' => true,
        ]);

        Sanctum::actingAs($member);

        $this->getJson('/api/member-profile/schema')
            ->assertOk()
            ->assertJsonPath('fields.0.key', 'name')
            ->assertJsonMissing(['key' => 'password'])
            ->assertJsonMissing(['key' => 'password_confirmation']);
    }

    public function test_member_profile_accepts_legacy_month_year_format(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'is_active' => true,
            'is_approved' => true,
        ]);

        Sanctum::actingAs($member);

        $this->postJson('/api/member/profile', [
            'articled_period_from' => '01/2015',
            'articled_period_to' => '12/2017',
        ])
            ->assertOk()
            ->assertJsonPath('profile.articled_period_from', '2015-01')
            ->assertJsonPath('profile.articled_period_to', '2017-12');
    }

    public function test_member_can_change_password_from_separate_endpoint(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'is_active' => true,
            'is_approved' => true,
            'password' => 'oldPassword123',
        ]);

        Sanctum::actingAs($member);

        $this->postJson('/api/member/change-password', [
            'current_password' => 'oldPassword123',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password changed successfully.');

        $this->assertTrue(Hash::check('newPassword123', $member->fresh()->password));
    }
}
