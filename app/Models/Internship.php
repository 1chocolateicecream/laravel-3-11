<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Internship extends Model
{
    protected $fillable = ['name', 'goals', 'evaluation_type'];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_internships')
                    ->withPivot('start_at', 'end_at')
                    ->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}