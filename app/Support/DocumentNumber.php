<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Gap-free, per-year document numbers such as SO-2026-0042.
 *
 * A row per prefix/year is locked while the counter is incremented, so concurrent requests never
 * hand out the same number. Must be called inside the transaction that creates the document.
 */
class DocumentNumber
{
    public function next(string $prefix, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return DB::transaction(function () use ($prefix, $year): string {
            // Lock first and only insert when the counter does not exist yet. Inserting unconditionally
            // (INSERT IGNORE) takes a shared lock on the existing row in MySQL; two requests holding it and both
            // asking for the exclusive lock below would deadlock on every concurrent document.
            $sequence = $this->lockSequence($prefix, $year);

            if ($sequence === null) {
                DB::table('document_sequences')->insertOrIgnore(['prefix' => $prefix, 'year' => $year, 'last_number' => 0]);
                $sequence = $this->lockSequence($prefix, $year);
            }

            $number = (int) $sequence->last_number + 1;

            DB::table('document_sequences')->where('id', $sequence->id)->update(['last_number' => $number]);

            return sprintf('%s-%d-%04d', $prefix, $year, $number);
        });
    }

    private function lockSequence(string $prefix, int $year): ?object
    {
        return DB::table('document_sequences')
            ->where('prefix', $prefix)
            ->where('year', $year)
            ->lockForUpdate()
            ->first(['id', 'last_number']);
    }
}
