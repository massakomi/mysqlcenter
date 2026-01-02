<?php

namespace service;

use enum\MessageType;

/**
 *
 */
trait Message
{
    final const bool ALLOW_REPEAT_MESSAGE = false;

    /* @var \dto\Message[] */
    public array $messages = [];

    /**
     * @return array
     */
    public function getMessagesData(): array
    {
        return $this->messages;
    }

    /**
     * Ошибка
     *
     * @param string $text
     * @param null $sql
     * @return bool
     */
    public function error(string $text, $sql = null): bool
    {
        return $this->addMessage($text, MessageType::Error, $sql);
    }

    /**
     * Успешная операция
     *
     * @param string $text
     * @param null $sql
     * @return bool
     */
    public function success(string $text, $sql = null): bool
    {
        return $this->addMessage($text, MessageType::Success, $sql);
    }

    /**
     * Ошибка, но не вызывает status error при ajax запросах
     *
     * @param string $text
     * @param null $sql
     * @return bool
     */
    public function notice(string $text, $sql = null): bool
    {
        return $this->addMessage($text, MessageType::Notice, $sql);
    }

    /**
     * Сохраняет важное сообщение о процессе выполнения, которое будет выведено пользователю
     *
     * @param string $text текст сообщения
     * @param MessageType $type сообщения MS_MSG_[SIMPLE SUCCESS FAULT ERROR NOTICE]
     * @param string|null $sql sql запрос
     * @return bool
     */
    private function addMessage(string $text, MessageType $type, ?string $sql): bool
    {
        if (!self::ALLOW_REPEAT_MESSAGE) {
            foreach ($this->messages as $message) {
                if ($message->text == $text && $message->sql == $sql) {
                    return true;
                }
            }
        }
        $this->messages [] = new \dto\Message(
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