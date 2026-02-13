<?php

declare(strict_types=1);

namespace database\schema;

use dto\ColumnDefinition;

interface SchemaDriverInterface
{
    public function applyColumnChanges(
        string $table,
        ColumnDefinition $column,
        array $currentFields,
        array $currentKeys
    ): array;

    public function quote(string $id): string;
}