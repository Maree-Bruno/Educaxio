<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
    ];

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}