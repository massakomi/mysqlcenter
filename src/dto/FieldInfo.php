<?php

declare(strict_types=1);

namespace dto;

final class FieldInfo
{
    public function __construct(
        public ?string $Field = null,
        public ?string $Type = null,
        public ?string $Null = null,
        public ?string $Key = null,
        public ?string $Default = null,
        public ?string $Extra = null,
        public ?string $Length = null,
    ) {
    }

    /**
     * @param iterable<array<string>> $row
     */
    public function fill(iterable $row): void
    {
        foreach ($row as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
