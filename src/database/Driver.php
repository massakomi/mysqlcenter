<?php

declare(strict_types=1);

namespace database;

use dto\Constraint;
use dto\FieldInfo;
use dto\KeyInfo;
use dto\TableInfo;

/**
 *
 */
interface Driver
{
    /**
     * @return array<array<string>>
     */
    public function getDatabases(): array;

    /**
     * @return array<string>
     */
    public function getDatabaseNames(): array;
    /**
     * @return TableInfo[]
     */
    public function getTables(string $db = ''): array;
    /**
     * @return FieldInfo[]
     */
    public function getFields(string $table): array;

    /**
     * @param string $table
     * @return array<string, array<string, string>>
     */
    public function getKeys(string $table): array;

    /**
     * @param string $table
     * @return array<KeyInfo>
     */
    public function getKeysFull(string $table): array;

    /**
     * @param string $table
     * @return array<Constraint>
     */
    public function getConstraints(string $table): array;

    /**
     * @param string $db
     * @return void
     */
    public function selectDb(string $db): void;

    /**
     * @param string $table
     * @return string
     */
    public function sqlCreateTable(string $table): string;

    /**
     * @param string $table
     * @return array<array<string>>
     */
    public function getTableDetailsWithComments(string $table): array;

    /**
     * @return array<array<string>>
     */
    public function getCharsets(): array;

    /**
     * @return array<array<string>>
     */
    public function getProcessList(): array;

    /**
     * @param string $table
     * @return TableInfo
     */
    public function getTableInfo(string $table): TableInfo;
}
