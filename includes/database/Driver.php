<?php

namespace database;

use dto\FieldInfo;
use dto\TableInfo;

/**
 *
 */
interface Driver
{
    public function getDatabases(): array;
    /**
     * @return TableInfo[]
     */
    public function getTables(string $db = ''): array;
    /**
     * @return FieldInfo[]
     */
    public function getFields(string $table): array;
    public function getKeys(string $table, bool $full = false): array;
    public function getConstraints(string $table, bool $full = false): array;
    public function selectDb(string $db);
    public function sqlCreateTable(string $table): string;
    public function getTableDetailsWithComments(string $table): array;
    public function getCharsets(): array;
    public function getProcessList(): array;
    public function getTableInfo(string $table): TableInfo;
}
