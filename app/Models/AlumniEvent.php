<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlumniEvent extends Model
{
    protected $fillable = [
        'title',
        'date_label',
        'description',
        'action_label',
        'action_url',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'date_label' => $this->date_label,
            'description' => $this->description,
            'action_label' => $this->action_label,
            'action_url' => $this->action_url,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
