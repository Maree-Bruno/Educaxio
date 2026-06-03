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

    public static function defaultDates(string $year): array
    {
        [$startYear, $endYear] = explode('-', $year);
        return [
            'start_date' => "{$startYear}-09-01",
            'end_date'   => "{$endYear}-06-30",
        ];
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class)
            ->withPivot(['start_date', 'end_date', 'archived_at']);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
