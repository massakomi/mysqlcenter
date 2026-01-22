<?php

declare(strict_types=1);

namespace dto;

final class ConnectConfig
{
    public function __construct(
        public ?string $host = null,
        public ?string $port = null,
        public ?string $database = null,
        public ?string $user = null,
        public ?string $password = null,
        public ?string $driver = null,
        public ?string $binPath = null,
    ) {
    }
}
