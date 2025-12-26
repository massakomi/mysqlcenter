<?php

/**
 * Класс для создания страницы
 */
class PageLayout
{
    private ?string $controller = null;

    public function __construct()
    {
    }

    /**
     * {}
     */
    public function template($pageProps = []): void
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
    public function getPageForTemplate(): string
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
     * Отображение страницы
     */
    public function display(): void
    {
        global $msc;
        $currentPage = $msc->page;
        $currentHandler = $this->getHandler();
        $this->initController();

        if ($currentHandler == null && $this->controller == null) {
            $msc->page = 'db_list';
            $msc->addMessage('Страница не найдена');
            $currentHandler = $this->getHandler();
        }

        if ($msc->connected()) {
            $msc->dbViewStat();
        }

        if (isajax()) {
            $this->ajaxResult($currentHandler);
        }

        $contentMain = $this->getContentByHandler($currentHandler);

        include(MS_DIR_TPL . '_skin1.htm.php');
    }

    /**
     * @param $handler
     * @return string
     */
    public function getContentByHandler($handler): string
    {
        global $msc, $umaker; // нужны в подключаемом хендлере, не везде там прописаны глобалы
        $contentMain = null;
        ob_start();
        if ($this->controller) {
            new $this->controller();
        } else {
            include $handler;
        }
        $contentMain = ob_get_contents();
        ob_clean();
        return $contentMain;
    }

    /**
     * @param $currentHandler
     * @return void
     */
    public function ajaxResult($currentHandler): void
    {
        global $msc;
        if (array_key_exists('init', $_GET)) {
            $data = [
                'messages' => $msc->getMessagesData(),
                'databases' => Server::getDatabasesWithoutHidden(),
                'DB_HOST' => $msc->host,
                'DB_USERNAME' => $msc->user,
            ];
        } else {
            $pageProps = include $currentHandler;
            if (!is_array($pageProps)) {
                $pageProps = [];
            }
            $data = [
                'page' => $pageProps,
                'messages' => $msc->getMessagesData(),
            ];
        }
        ajaxResult($data);
    }

    /**
     * Определяем обработчик
     * @static
     */
    public function getHandler(): ?string
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
            return $currentHandler;
        } else {
            return null;
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
                $this->controller = '\controller\\' . $className;
                break;
            }
        }
    }
}

