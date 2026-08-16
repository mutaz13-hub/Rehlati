<?php

namespace App\Services;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Small abstraction over native spatial columns (MySQL/PostgreSQL).
 *
 * On drivers without spatial support (e.g. SQLite used by the test suite)
 * plain decimal columns are used instead, so the same code path keeps working.
 */
class SpatialService
{
    public function supportsSpatial(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'pgsql'], true);
    }

    /**
     * Value array ready for insert/update. On spatial drivers the point is
     * built with raw ST_GeomFromText SQL; elsewhere plain lat/lng are stored.
     *
     * @return array{coordinates?: Expression, latitude?: float, longitude?: float}
     */
    public function pointValue(float $latitude, float $longitude): array
    {
        if ($this->supportsSpatial()) {
            return [
                'coordinates' => DB::raw(
                    sprintf("ST_GeomFromText('POINT(%s %s)', 4326)", $this->format($latitude), $this->format($longitude))
                ),
            ];
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    /**
     * Column expressions to extract raw latitude/longitude from a point column.
     * ST_X gives the longitude, ST_Y the latitude.
     *
     * @return array<Expression|string>
     */
    public function pointSelect(string $table, string $column = 'coordinates'): array
    {
        if ($this->supportsSpatial()) {
            return [
                DB::raw("ST_X({$table}.{$column}) AS longitude"),
                DB::raw("ST_Y({$table}.{$column}) AS latitude"),
            ];
        }

        return ["{$table}.latitude", "{$table}.longitude"];
    }

    /**
     * String variant of pointSelect() for selectRaw() eager-load closures.
     */
    public function pointSelectRaw(string $table, string $column = 'coordinates'): string
    {
        if ($this->supportsSpatial()) {
            return "{$table}.*, ST_X({$table}.{$column}) AS longitude, ST_Y({$table}.{$column}) AS latitude";
        }

        return "{$table}.*, {$table}.latitude AS latitude, {$table}.longitude AS longitude";
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(sprintf('%.8f', $value), '0'), '.');
    }
}
