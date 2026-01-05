<?php

namespace dto;

/**
 *
 */
final class FieldInfo
{
    public function __construct(
        public ?string $Field = null,
        public ?string $Type = null,
        public ?string $Null = null,
        public ?string $Key = null,
        public ?string $Default = null,
        public ?string $Extra = null,
    ) {
    }

    /**
     * @param Iterable<array<string>> $row
     * @return void
     */
    public function fill(Iterable $row): void
    {
        foreach ($row as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
