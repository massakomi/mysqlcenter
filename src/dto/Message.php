<?php

declare(strict_types=1);

namespace dto;

final class Message
{
    public function __construct(
        public ?string $text = null,
        public ?string $type = null,
        public ?string $color = null,
        public ?string $error = null,
        public ?string $sql = null,
        public ?int $rows = null,
    ) {
    }
}
