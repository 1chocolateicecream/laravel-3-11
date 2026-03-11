<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = ['name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function internships(): BelongsToMany
    {
        return $this->belongsToMany(Internship::class, 'group_internships')
                    ->withPivot('start_at', 'end_at')
                    ->withTimestamps();
    }
}