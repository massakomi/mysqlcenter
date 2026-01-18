<?php

declare(strict_types=1);

namespace dto;

final class Constraint
{
    public function __construct(
        public ?string $TABLE_SCHEMA = null,
        public ?string $TABLE_NAME = null,
        public ?string $CONSTRAINT_NAME = null,
        public ?string $CONSTRAINT_TYPE = null,
        public ?int $ORDINAL_POSITION = null,
        public ?string $COLUMN_NAME = null,
    ) {
    }
}
