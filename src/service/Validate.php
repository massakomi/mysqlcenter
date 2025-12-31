<?php

namespace service;

/**
 * Общий класс для валидации
 */
class Validate
{
    /**
     * Проверка наличия в запросе БД, таблицы или чего-то ещё. Ловит ошибку самого высокого уровня, поэтому exit()
     *
     * @param string $database база данных
     * @param string $table таблица
     * @param string $params прочие требуемые параметры (третий, четвертый и другие параметры функции)
     */
    public function queryCheck(string $database = '', string $table = '', $params = ''): bool
    {
        $args = func_get_args();
        if (empty($args[0])) {
            return $this->error('Database name not defined');
        }
        if (count($args) > 1 && empty($args[1])) {
            return $this->error('Table name not defined');
        }
        if (count($args) > 2) {
            foreach ($args as $num => $value) {
                if ($num < 2) {
                    continue;
                }
                if ((is_array($value) && count($value) == 0) || is_null($value)) {
                    return $this->error('Some required query param not defined');
                }
            }
        }
        return true;
    }

    /**
     * Сообщение о серьезной ошибке выводится сразу на печать и exit
     *
     * @param string
     */
    private function error($message)
    {
        global $msc;
        if (isAjax()) {
            ajaxError($message);
        }
        $msc->error($message);
        return false;
    }
}
