<?php

use controller\Base;

/**
 * Класс для создания страницы
 */
class PageLayout
{
    private ?Base $controller = null;
    private ?string $handler = null;

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
        $msc->dbViewStat();
        $this->initController();

        if ($this->handler == null && $this->controller == null) {
            $msc->page = 'db_list';
            $msc->notice('Страница не найдена');
            $this->initController();
        }

        $contentMain = $this->getContentByHandler();

        include(MS_DIR_TPL . '_skin1.htm.php');
    }

    /**
     * {}
     */
    private function template($pageProps = []): void
    {
        $action = $this->getPageForTemplate();
        $component = ucfirst($action);
        ?>
        <div id="root"></div>
        <script type="text/babel" src="/pages/<?= $action ?>.js"></script>
        <script type="text/babel">
            let options = <?=json_encode($pageProps)?>;
            ReactDOM.render(
              <<?=$component?> {...options} />,
              document.getElementById('root')
            )
        </script>
        <?php
    }

    /**
     * @return string
     */
    private function getPageForTemplate(): string
    {
        global $msc;
        $page = $msc->page;
        if ($msc->page == 'actions' && !$msc->table) {
            $page = 'actionsdb';
        }
        if ($msc->page == 'search' && $msc->table) {
            $page = 'searchTable';
        }
        if ($msc->page == 'tbl_list' && GET('action') == 'structure') {
            $page = 'tbl_struct_view';
        }
        if ($msc->page == 'tbl_struct' && GET('action') == 'add_key') {
            $page = 'tbl_key_add';
        }
        return $page;
    }

    /**
     * @return string
     */
    private function getContentByHandler(): string
    {
        global $msc;
        $contentMain = null;
        ob_start();
        if ($this->controller) {
            $pageProps = $this->controller->defaultAction();
            $this->template($pageProps);
        } else {
            $pageProps = include $this->handler;
        }
        $contentMain = ob_get_contents();
        ob_clean();
        if (isajax()) {
            $data = [
                'page' => $pageProps,
                'messages' => $msc->getMessagesData(),
            ];
            ajaxResult($data);
        }
        return $contentMain;
    }

    /**
     * Для обратной совместимости с mysqlcenter-next, где этот массив нужен
     */
    private function returnInitIfAjax(): void
    {
        global $msc;
        if (isajax() && array_key_exists('init', $_GET)) {
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
    private function initHandler(): void
    {
        global $msc;
        $handlers = [
            'exportSp' => 'export'
        ];
        if (isset($handlers[$msc->page])) {
            $currentHandler = DIR_MYSQL . $handlers[$msc->page] . '.php';
        } else {
            $currentHandler = DIR_MYSQL . $msc->page . '.php';
        }
        if (file_exists($currentHandler)) {
            $this->handler = $currentHandler;
        }
    }

    /**
     * Определяем обработчик
     * @static
     */
    private function initController(): void
    {
        global $msc;
        $this->initHandler();
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

