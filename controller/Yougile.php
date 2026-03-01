<?php

declare(strict_types=1);

namespace controller;


final class Yougile extends Base
{
    /**
     * @return array<string>
     */
    public function defaultAction(): array
    {

        $yougileService = new \service\Yougile();

        return [
            'xxx' => '111'
        ];
        
        /*return $yougileService->createTask('Test111', 'descr');
        return $yougileService->getBoards('');
        return $yougileService->getProjects();
        return $yougileService->getCompanyInfo();*/
    }
}
