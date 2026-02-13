<?php

declare(strict_types=1);

namespace database\schema;

class SchemaDriverFactory
{
    public static function create(string $driver): SchemaDriverInterface
    {
        return match ($driver) {
            'pgsql' => new PgSqlSchemaDriver(),
            default => new MySqlSchemaDriver(),
        };
    }
}
