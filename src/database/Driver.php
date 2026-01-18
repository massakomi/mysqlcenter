<?php

declare(strict_types=1);

namespace database;

use dto\Constraint;
use dto\FieldInfo;
use dto\KeyInfo;
use dto\TableInfo;

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
     * @return array<string, array<string, string>>
     */
    public function getKeys(string $table): array;

    /**
     * @return array<KeyInfo>
     */
    public function getKeysFull(string $table): array;

    /**
     * @return array<Constraint>
     */
    public function getConstraints(string $table): array;

    public function selectDb(string $db): void;

    public function sqlCreateTable(string $table): string;

    /**
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

    public function getTableInfo(string $table): TableInfo;

    /**
     * @return array<string>
     */
    public function getIdentityInfo(string $table): array;
}
