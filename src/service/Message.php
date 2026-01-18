<?php

declare(strict_types=1);

namespace service;

use enum\MessageType;

trait Message
{
    /**
     * @var \dto\Message[]
     */
    private array $messages = [];

    /**
     * @return array<\dto\Message>
     */
    public function getMessagesData(): array
    {
        return $this->messages;
    }

    /**
     * Ошибка.
     */
    public function error(string $text, ?string $sql = null): bool
    {
        return $this->addMessage($text, MessageType::Error, $sql);
    }

    /**
     * Успешная операция.
     */
    public function success(string $text, ?string $sql = null): bool
    {
        return $this->addMessage($text, MessageType::Success, $sql);
    }

    /**
     * Ошибка, но не вызывает status error при ajax запросах.
     */
    public function notice(string $text, ?string $sql = null): bool
    {
        return $this->addMessage($text, MessageType::Notice, $sql);
    }

    /**
     * Сохраняет важное сообщение о процессе выполнения, которое будет выведено пользователю.
     *
     * @param string $text текст сообщения
     * @param MessageType $type сообщения MS_MSG_[SIMPLE SUCCESS FAULT ERROR NOTICE]
     * @param string|null $sql sql запрос
     */
    private function addMessage(string $text, MessageType $type, ?string $sql): bool
    {
        foreach ($this->messages as $message) {
            if ($message->text == $text && $message->sql == $sql) {
                return true;
            }
        }
        $this->messages[] = new \dto\Message(
            text: $text,
            type: $type->value,
            color: $type->getColor(),
            error: $this->error,
            sql: $sql,
            rows: $this->affectedRows,
        );
        if ($type == MessageType::Error) {
            return false;
        }

        return true;
    }
}
