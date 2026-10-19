<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a query as a CSV download, chunk by chunk, so large ledgers never have to fit in memory.
 * The file starts with a UTF-8 byte order mark so spreadsheet programs read accents and the € sign correctly.
 */
final class CsvExport
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $headers
     * @param  callable(TModel): list<scalar|null>  $row
     */
    public static function download(string $filename, Builder $query, array $headers, callable $row): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $headers, $row): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\u{FEFF}");
            fputcsv($out, $headers, escape: '');

            $query->chunkById(500, function ($models) use ($out, $row): void {
                foreach ($models as $model) {
                    fputcsv($out, array_map(self::cell(...), $row($model)), escape: '');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Neutralises values a spreadsheet would run as a formula (CSV injection).
     */
    private static function cell(string|int|float|bool|null $value): string|int|float|bool|null
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
