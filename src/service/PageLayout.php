<?php

declare(strict_types=1);

namespace service;

use controller\Base;
use database\MSTable;
use database\Server;

/**
 * Класс для создания страницы.
 */
class PageLayout
{
    private Base $controller;

    public function __construct()
    {
    }

    /**
     * Отображение страницы.
     *
     * @return array<string>
     *
     * @throws \Exception
     */
    public function execute(): array
    {
        global $msc;
        $this->returnInitIfAjax();
        MSTable::dbViewStat();
        $this->initController();

        $method = $this->getMethod();
        $pageProps = $this->controller->$method();

        if (isAjax()) {
            // Если метод контроллера ничего не возвращает, то генерим простой ответ из сообщений
            if (!is_array($pageProps)) {
                ajaxResultWithMessages();
            }
            $data = [
                'page' => $pageProps,
                'messages' => $msc->getMessagesData(),
            ];
            ajaxResult($data);
        }

        return $pageProps;
    }

    /**
     * Текущий метод контроллера.
     */
    private function getMethod(): string
    {
        $method = 'defaultAction';
        $action = POST('mode', GET('mode'));
        if ($action) {
            $action = preg_replace_callback('~_([a-z])~i', function ($match) {
                return strtoupper($match[1]);
            }, $action);
            $actionMethod = $action.'Action';
            if (method_exists($this->controller, $actionMethod)) {
                $method = $actionMethod;
            }
        }

        return $method;
    }

    /**
     * Для обратной совместимости с mysqlcenter-next, где этот массив нужен.
     */
    private function returnInitIfAjax(): void
    {
        global $msc;
        if (isAjax() && array_key_exists('init', $_GET)) {
            $data = [
                'messages' => $msc->getMessagesData(),
                'databases' => Server::getDatabasesWithoutHidden(),
                'DB_HOST' => $msc->host,
                'DB_USERNAME' => $msc->user,
            ];
            ajaxResult($data);
        }
    }

    /**
     * Определяем обработчик.
     *
     * @static
     */
    private function initController(): void
    {
        global $msc;
        $classNames = [
            ucfirst($msc->page),
            ucfirst(preg_replace_callback('~_([a-z])~i', function ($match) {
                return strtoupper($match[1]);
            }, $msc->page)),
        ];
        foreach ($classNames as $className) {
            $class = '\controller\\'.$className;
            if (class_exists($class)) {
                $object = new $class();
                if ($object instanceof Base) {
                    $this->controller = $object;

                    return;
                }
            }
        }
        $msc->page = 'db_list';
        $msc->error('Страница не найдена');
        $this->initController();
    }
}
