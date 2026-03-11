<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    protected $fillable = [
        'user_id',
        'group_id',
        'internship_id',
        'company_id',
        'motivation_letter',
        'status'
    ];

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function internship(): BelongsTo { return $this->belongsTo(Internship::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    public function evaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class);
    }
}