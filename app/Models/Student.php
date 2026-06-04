<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'lastname',
        'firstname',
        'email',
        'picture',
        'school_id',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Student $student) {
            $base = Str::slug($student->firstname.'-'.$student->lastname);
            $slug = $base;
            $n = 2;
            while (static::where('slug', $slug)->exists()) {
                $slug = $base.'-'.$n++;
            }
            $student->slug = $slug;
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function attendanceStatuses(): HasMany
    {
        return $this->hasMany(StudentAttendanceStatus::class);
    }
}
