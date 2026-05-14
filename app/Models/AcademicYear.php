<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
    ];

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}