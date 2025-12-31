<?php

namespace enum;

/**
 * Тип сообщения
 */
enum MessageType: string
{
    // успешная операция
    case Success = 'success';
    // операция не удалась
    case Error = 'error';
    // непонятная ситуация, замечание
    case Notice = 'notice';

    /**
     * @return string
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Success => 'green',
            self::Error => 'red',
            self::Notice => 'blue',
        };
    }
}
