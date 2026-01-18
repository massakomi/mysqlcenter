<?php

declare(strict_types=1);

namespace enum;

/**
 * Тип выполняемого запроса.
 */
enum FetchType
{
    case Exec;
    case Fetch;
}
