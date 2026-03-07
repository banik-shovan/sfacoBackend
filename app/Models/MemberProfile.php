<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberProfile extends Model
{
    protected $fillable = [
        'user_id',
        'date_of_birth',
        'nid_passport_number',
        'gender',
        'profile_photo_path',
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
        'profile_completed_at',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'profile_completed_at' => 'datetime',
        ];
    }
}
