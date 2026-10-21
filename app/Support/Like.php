<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Query\Builder;

/**
 * Escaping for user-supplied LIKE patterns, so `%` and `_` match literally.
 */
final class Like
{
    /**
     * Builds a "contains" pattern with `\`, `%` and `_` escaped. Pair it with `ESCAPE '\'`.
     */
    public static function contains(string $term): string
    {
        return '%'.addcslashes($term, '\\%_').'%';
    }

    /**
     * Registers `whereContains` / `orWhereContains` on the query builder (they also work on Eloquent builders).
     * The explicit ESCAPE clause is needed because SQLite has no default escape character.
     */
    public static function registerMacros(): void
    {
        Builder::macro('whereContains', function (string $column, string $term, string $boolean = 'and') {
            /** @var Builder $this */
            return $this->whereRaw($this->grammar->wrap($column)." like ? escape '\\'", [Like::contains($term)], $boolean);
        });

        Builder::macro('orWhereContains', function (string $column, string $term) {
            /** @var Builder $this */
            return $this->whereContains($column, $term, 'or');
        });
    }
}
