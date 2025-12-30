<?php

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
     * @throws Exception
     */
    public function display(): void
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
        $time = round(round(array_sum(explode(" ", microtime())), 10) - $msc->timer, 5);

        include(MS_DIR_TPL . '_skin1.htm.php');
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
            $path = 'controller/' . $className . '.php';
            if (file_exists($path)) {
                $class = '\controller\\' . $className;
                $this->controller = new $class();
                break;
            }
        }
    }
}

