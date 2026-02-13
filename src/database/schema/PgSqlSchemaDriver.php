<?php

declare(strict_types=1);

namespace database\schema;

use dto\ColumnDefinition;

class PgSqlSchemaDriver implements SchemaDriverInterface
{
    public function quote(string $id): string
    {
        return '"'.$id.'"';
    }

    public function applyColumnChanges(
        string $table,
        ColumnDefinition $column,
        array $currentFields,
        array $currentKeys
    ): array {

        $sql = [];
        $qt  = $this->quote($table);
        $col = $this->quote($column->newName);

        /*
         * ===== 1. COLUMN STRUCTURE =====
         */

        if ($column->isRenamed()) {
            $sql[] = "ALTER TABLE $qt RENAME COLUMN ".
                $this->quote($column->oldName).
                " TO $col";
        }

        $sql[] = "ALTER TABLE $qt ALTER COLUMN $col TYPE ".
            $column->getFullType();

        $sql[] = $column->nullable
            ? "ALTER TABLE $qt ALTER COLUMN $col DROP NOT NULL"
            : "ALTER TABLE $qt ALTER COLUMN $col SET NOT NULL";

        if ($column->default !== null && $column->default !== '') {
            $sql[] = "ALTER TABLE $qt ALTER COLUMN $col SET DEFAULT ".$column->default;
        } else {
            $sql[] = "ALTER TABLE $qt ALTER COLUMN $col DROP DEFAULT";
        }

        /*
         * ===== 2. KEY SYNC =====
         */

        $currentPrimary = '';
        $existingIndexes = [];

        foreach ($currentKeys as $field => $keyTypes) {
            foreach ($keyTypes as $indexName => $type) {

                if ($type === 'PRI') {
                    $currentPrimary = $field;
                    continue;
                }

                $existingIndexes[$field] = [
                    'type' => $type,
                    'name' => $indexName,
                ];
            }
        }

        // PRIMARY
        if ($currentPrimary !== $column->newName && $column->primary) {

            if ($currentPrimary !== '') {
                $sql[] = "ALTER TABLE $qt DROP CONSTRAINT ".
                    $this->quote($table.'_pkey');
            }

            $sql[] = "ALTER TABLE $qt ADD PRIMARY KEY ($col)";
        }

        // INDEX
        $indexName = $table.'_'.$column->newName.'_idx';

        if ($column->unique) {
            $sql[] = 'CREATE UNIQUE INDEX '.
                $this->quote($indexName).
                " ON $qt ($col)";
        } elseif ($column->index) {
            $sql[] = 'CREATE INDEX '.
                $this->quote($indexName).
                " ON $qt ($col)";
        }

        return $sql;
    }
}
