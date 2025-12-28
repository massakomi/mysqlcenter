<?php

use controller\Base;

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

        $contentMain = $this->getContent();

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
        if ($msc->page == 'export' && GET('action') == 'special') {
            $page = 'exportSp';
        }
        return $page;
    }

    /**
     * @return string
     */
    private function getContent(): string
    {
        global $msc;
        ob_start();
        $pageProps = $this->controller->defaultAction();
        $this->template($pageProps);
        $contentMain = ob_get_contents();
        ob_clean();
        if (isAjax()) {
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

