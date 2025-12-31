<?php

namespace service;

use controller\Base;
use database\MSTable;
use database\Server;

/**
 * Класс для создания страницы
 */
class PageLayout
{
    private ?Base $controller = null;

    public function __construct()
    {
    }

    /**
     * Отображение страницы
     * @throws \Exception
     */
    public function execute(): array
    {
        global $msc;
        $this->returnInitIfAjax();
        MSTable::dbViewStat();
        $this->initController();

        if ($this->controller == null) {
            $msc->page = 'db_list';
            $msc->error('Страница не найдена');
            $this->initController();
        }

        $pageProps = $this->controller->defaultAction();
        if (isAjax()) {
            $data = [
                'page' => $pageProps,
                'messages' => $msc->getMessagesData(),
            ];
            ajaxResult($data);
        }
        return $pageProps;
    }

    /**
     * Для обратной совместимости с mysqlcenter-next, где этот массив нужен
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
     * Определяем обработчик
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
            $class = '\controller\\' . $className;
            if (class_exists($class)) {
                $this->controller = new $class();
                break;
            }
        }
    }
}
