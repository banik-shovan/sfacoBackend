<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

trait InteractsWithMemberProfiles
{
    /**
     * @return list<array<string, mixed>>
     */
    protected function memberProfileFields(): array
    {
        return [
            [
                'key' => 'name',
                'section' => 'Personal',
                'label' => 'Full Name',
                'required' => true,
                'type' => 'text',
                'note' => 'As per ICAB record',
                'source' => 'users',
            ],
            [
                'key' => 'date_of_birth',
                'section' => 'Personal',
                'label' => 'Date of Birth',
                'required' => true,
                'type' => 'date',
                'note' => null,
                'source' => 'member_profiles',
            ],
            [
                'key' => 'nid_passport_number',
                'section' => 'Personal',
                'label' => 'NID/Passport Number',
                'required' => true,
                'type' => 'text',
                'note' => null,
                'source' => 'member_profiles',
            ],
            [
                'key' => 'gender',
                'section' => 'Personal',
                'label' => 'Gender',
                'required' => true,
                'type' => 'text',
                'note' => null,
                'source' => 'member_profiles',
            ],
            [
                'key' => 'profile_photo',
                'section' => 'Personal',
                'label' => 'Profile Photo',
                'required' => false,
                'type' => 'file',
                'note' => 'JPG/PNG, max 2MB',
                'source' => 'member_profiles',
            ],
            [
                'key' => 'email',
                'section' => 'Contact',
                'label' => 'Email Address',
                'required' => true,
                'type' => 'email',
                'note' => 'Must be unique',
                'source' => 'users',
            ],
            [
                'key' => 'whatsapp_number',
                'section' => 'Contact',
                'label' => 'Whatsapp Number',
                'required' => true,
                'type' => 'text',
                'note' => 'Must be unique',
                'source' => 'member_profiles',
            ],
            [
                'key' => 'current_address',
                'section' => 'Address',
                'label' => 'Current Address',
                'required' => true,
                'type' => 'textarea',
                'note' => null,
                'source' => 'member_profiles',
            ],
            [
                'key' => 'permanent_address',
                'section' => 'Address',
                'label' => 'Permanent Address',
                'required' => true,
                'type' => 'textarea',
                'note' => null,
                'source' => 'member_profiles',
            ],
            [
                'key' => 'articled_period_from',
                'section' => 'Article Info',
                'label' => 'Articled Period From',
                'required' => true,
                'type' => 'month',
                'note' => 'Accepts YYYY-MM from HTML month input or MM/YYYY',
                'source' => 'member_profiles',
            ],
            [
                'key' => 'articled_period_to',
                'section' => 'Article Info',
                'label' => 'Articled Period To',
                'required' => true,
                'type' => 'month',
                'note' => 'Accepts YYYY-MM from HTML month input or MM/YYYY',
                'source' => 'member_profiles',
            ],
            [
                'key' => 'principal_supervisor_name',
                'section' => 'Alumni Info',
                'label' => 'Principal / Supervisor Name',
                'required' => true,
                'type' => 'text',
                'note' => null,
                'source' => 'member_profiles',
            ],
            [
                'key' => 'icab_registration_no',
                'section' => 'Alumni Info',
                'label' => 'ICAB Registration No',
                'required' => false,
                'type' => 'text',
                'note' => null,
                'source' => 'member_profiles',
            ],
            [
                'key' => 'current_organization',
                'section' => 'Professional',
                'label' => 'Current Organization',
                'required' => true,
                'type' => 'text',
                'note' => null,
                'source' => 'member_profiles',
            ],
            [
                'key' => 'designation',
                'section' => 'Professional',
                'label' => 'Designation',
                'required' => true,
                'type' => 'text',
                'note' => null,
                'source' => 'member_profiles',
            ],
            [
                'key' => 'ca_status',
                'section' => 'Qualification',
                'label' => 'CA Status',
                'required' => true,
                'type' => 'select',
                'note' => null,
                'options' => [
                    ['value' => 'qualified', 'label' => 'Qualified'],
                    ['value' => 'part_qualified', 'label' => 'Part Qualified'],
                    ['value' => 'ca_cc', 'label' => 'CA (CC)'],
                ],
                'source' => 'member_profiles',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateMemberProfilePayload(Request $request, User $user): array
    {
        $profile = $user->memberProfile()->first();

        return $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'nid_passport_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:50'],
            'remove_profile_photo' => ['sometimes', 'boolean'],
            'profile_photo' => ['sometimes', 'file', 'image', 'max:2048'],
            'whatsapp_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
                Rule::unique('member_profiles', 'whatsapp_number')->ignore($profile?->id),
            ],
            'current_address' => ['sometimes', 'nullable', 'string'],
            'permanent_address' => ['sometimes', 'nullable', 'string'],
            'articled_period_from' => ['sometimes', 'nullable', 'regex:/^((0[1-9]|1[0-2])\\/\\d{4}|\\d{4}-(0[1-9]|1[0-2]))$/'],
            'articled_period_to' => ['sometimes', 'nullable', 'regex:/^((0[1-9]|1[0-2])\\/\\d{4}|\\d{4}-(0[1-9]|1[0-2]))$/'],
            'principal_supervisor_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'icab_registration_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'current_organization' => ['sometimes', 'nullable', 'string', 'max:255'],
            'designation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ca_status' => ['sometimes', 'nullable', Rule::in(['qualified', 'part_qualified', 'ca_cc'])],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function saveMemberProfile(Request $request, User $user): array
    {
        $validated = $this->validateMemberProfilePayload($request, $user);

        $userUpdates = [];

        foreach (['name', 'email'] as $field) {
            if (array_key_exists($field, $validated)) {
                $userUpdates[$field] = $validated[$field];
            }
        }

        if ($userUpdates !== []) {
            $user->forceFill($userUpdates)->save();
        }

        $profile = $user->memberProfile()->firstOrCreate([]);

        if (($validated['remove_profile_photo'] ?? false) && $profile->profile_photo_path) {
            Storage::disk('public')->delete($profile->profile_photo_path);
            $profile->profile_photo_path = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($profile->profile_photo_path) {
                Storage::disk('public')->delete($profile->profile_photo_path);
            }

            $profile->profile_photo_path = $request->file('profile_photo')->store('member-profiles', 'public');
        }

        $profileUpdates = [];

        foreach ([
            'date_of_birth',
            'nid_passport_number',
            'gender',
            'whatsapp_number',
            'current_address',
            'permanent_address',
            'articled_period_from',
            'articled_period_to',
            'principal_supervisor_name',
            'icab_registration_no',
            'current_organization',
            'designation',
            'ca_status',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $profileUpdates[$field] = in_array($field, ['articled_period_from', 'articled_period_to'], true)
                    ? $this->normalizeMonthYear($validated[$field])
                    : $validated[$field];
            }
        }

        if ($profile->isDirty('profile_photo_path')) {
            $profileUpdates['profile_photo_path'] = $profile->profile_photo_path;
        }

        if ($profileUpdates !== []) {
            $profile->fill($profileUpdates);
            $profile->updated_by = $request->user()->id;
            $profile->save();
        } elseif (! $profile->exists) {
            $profile->save();
        }

        $freshUser = $user->fresh()->load('memberProfile');
        $snapshot = $this->memberProfileData($freshUser);
        $completedAt = $snapshot['is_complete']
            ? ($freshUser->memberProfile?->profile_completed_at ?? now())
            : null;

        if ($freshUser->memberProfile && $freshUser->memberProfile->profile_completed_at?->toISOString() !== $completedAt?->toISOString()) {
            $freshUser->memberProfile->forceFill([
                'profile_completed_at' => $completedAt,
            ])->saveQuietly();

            $freshUser->unsetRelation('memberProfile');
            $freshUser->load('memberProfile');
            $snapshot = $this->memberProfileData($freshUser);
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    protected function memberProfileData(User $user): array
    {
        $user->unsetRelation('memberProfile');
        $user->load('memberProfile');

        /** @var MemberProfile|null $profile */
        $profile = $user->memberProfile;

        $data = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'date_of_birth' => $profile?->date_of_birth?->format('Y-m-d'),
            'nid_passport_number' => $profile?->nid_passport_number,
            'gender' => $profile?->gender,
            'profile_photo_url' => $profile?->profile_photo_path ? Storage::disk('public')->url($profile->profile_photo_path) : null,
            'whatsapp_number' => $profile?->whatsapp_number,
            'current_address' => $profile?->current_address,
            'permanent_address' => $profile?->permanent_address,
            'articled_period_from' => $profile?->articled_period_from,
            'articled_period_to' => $profile?->articled_period_to,
            'principal_supervisor_name' => $profile?->principal_supervisor_name,
            'icab_registration_no' => $profile?->icab_registration_no,
            'current_organization' => $profile?->current_organization,
            'designation' => $profile?->designation,
            'ca_status' => $profile?->ca_status,
            'profile_completed_at' => $profile?->profile_completed_at?->toISOString(),
            'updated_at' => $profile?->updated_at?->toISOString(),
        ];

        $data['missing_required_fields'] = $this->missingRequiredMemberProfileFields($data);
        $data['is_complete'] = $data['missing_required_fields'] === [];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    protected function missingRequiredMemberProfileFields(array $data): array
    {
        $missing = [];

        foreach ($this->memberProfileFields() as $field) {
            if (! $field['required']) {
                continue;
            }

            if ($field['key'] === 'profile_photo') {
                continue;
            }

            $value = $data[$field['key']] ?? null;

            if ($value === null || $value === '') {
                $missing[] = $field['key'];
            }
        }

        return $missing;
    }

    protected function normalizeMonthYear(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (preg_match('/^(0[1-9]|1[0-2])\/(\d{4})$/', $value, $matches) === 1) {
            return $matches[2].'-'.$matches[1];
        }

        return $value;
    }
}
