<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:archive-academic-years')]
#[Description('Mark academic year–school pivot rows as archived 7 days after end_date')]
class ArchiveAcademicYears extends Command
{
    public function handle(): int
    {
        $cutoff = now()->subDays(7)->toDateString();

        $updated = DB::table('academic_year_school')
            ->whereNull('archived_at')
            ->whereNotNull('end_date')
            ->where('end_date', '<', $cutoff)
            ->update(['archived_at' => now()]);

        $this->info("Archived {$updated} academic year(s).");

        return self::SUCCESS;
    }
}