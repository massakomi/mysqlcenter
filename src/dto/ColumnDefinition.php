<?php

declare(strict_types=1);

namespace dto;

final class ColumnDefinition
{
    public function __construct(
        public string $oldName,
        public string $newName,
        public string $type,
        public ?int $length = null,
        public bool $nullable = true,
        public mixed $default = null,
        public bool $primary = false,
        public bool $index = false,
        public bool $unique = false,
    ) {
    }

    public function getFullType(): string
    {
        if ($this->length !== null) {
            return $this->type.'('.$this->length.')';
        }

        return $this->type;
    }

    public function isRenamed(): bool
    {
        return $this->oldName !== $this->newName;
    }
}
