<?php

declare(strict_types=1);

namespace dto;

final class KeyInfo
{
    public function __construct(
        public ?string $Table = null,
        public ?int $Non_unique = null,
        public ?string $Key_name = null,
        public ?int $Seq_in_index = null,
        public ?string $Column_name = null,
        public ?string $Collation = null,
        public ?string $Cardinality = null,
        public ?string $Sub_part = null,
        public ?string $Packed = null,
        public ?string $Null = null,
        public ?string $Index_type = null,
        public ?string $Comment = null,
        public ?string $Index_comment = null,
    ) {
    }
}
