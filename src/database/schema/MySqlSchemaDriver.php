<?php

declare(strict_types=1);

namespace database\schema;

use dto\ColumnDefinition;

class MySqlSchemaDriver implements SchemaDriverInterface
{
    public function quote(string $id): string
    {
        return '`'.$id.'`';
    }

    public function applyColumnChanges(
        string           $table,
        ColumnDefinition $column,
        array            $currentFields,
        array            $currentKeys
    ): array {

        $sql = [];

        $qt      = $this->quote($table);
        $oldCol  = $this->quote($column->oldName);
        $newCol  = $this->quote($column->newName);

        /*
         * =========================================
         * 1. COLUMN STRUCTURE (CHANGE)
         * =========================================
         */

        $definition = $newCol.' '.$column->getFullType();

        $definition .= $column->nullable ? ' NULL' : ' NOT NULL';

        if ($column->default !== null && $column->default !== '') {
            $definition .= ' DEFAULT '.$column->default;
        }

        // CHANGE работает и для rename и для обычного изменения
        $sql[] = "ALTER TABLE $qt CHANGE $oldCol $definition";


        /*
         * =========================================
         * 2. АНАЛИЗ ТЕКУЩИХ КЛЮЧЕЙ
         * =========================================
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


        /*
         * =========================================
         * 3. PRIMARY KEY SYNC
         * =========================================
         */

        if ($column->primary) {

            if ($currentPrimary !== $column->newName) {

                if ($currentPrimary !== '') {
                    $sql[] = "ALTER TABLE $qt DROP PRIMARY KEY";
                }

                $sql[] = "ALTER TABLE $qt ADD PRIMARY KEY ($newCol)";
            }

        } else {

            if ($currentPrimary === $column->oldName) {
                $sql[] = "ALTER TABLE $qt DROP PRIMARY KEY";
            }
        }


        /*
         * =========================================
         * 4. INDEX / UNIQUE SYNC
         * =========================================
         */

        $indexName = $table.'_'.$column->newName.'_idx';

        $hasIndexNow = isset($existingIndexes[$column->oldName]);

        // если индекс был, но теперь не нужен
        if ($hasIndexNow && !$column->index && !$column->unique) {

            $oldIndexName = $existingIndexes[$column->oldName]['name'];

            $sql[] = "ALTER TABLE $qt DROP INDEX ".
                $this->quote($oldIndexName);
        }

        // если нужен UNIQUE
        if ($column->unique) {

            if (!$hasIndexNow
                || $existingIndexes[$column->oldName]['type'] !== 'UNI') {

                $sql[] = "ALTER TABLE $qt ADD UNIQUE ".
                    $this->quote($indexName).
                    " ($newCol)";
            }
        }

        // если нужен обычный INDEX
        elseif ($column->index) {

            if (!$hasIndexNow
                || $existingIndexes[$column->oldName]['type'] !== 'MUL') {

                $sql[] = "ALTER TABLE $qt ADD INDEX ".
                    $this->quote($indexName).
                    " ($newCol)";
            }
        }

        return $sql;
    }
}
