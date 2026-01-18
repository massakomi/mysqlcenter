<?php

declare(strict_types=1);

namespace controller;

abstract class Base
{
    /**
     * @return array<string>
     */
    abstract public function defaultAction(): array;
}
