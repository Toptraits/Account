<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffMember extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_number',
        'name',
        'designation',
        'department',
        'email',
        'mobile',
        'date_of_joining',
        'employment_type',
        'status',
        'subjects',
        'classes',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'date_of_joining' => 'date',
            'subjects' => 'array',
            'classes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
