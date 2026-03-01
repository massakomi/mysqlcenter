<?php


declare(strict_types=1);

namespace service;

final class Yougile
{
    private const string YOUGILE_API_URL = 'https://yougile.com';

    /**
     * @return array{login: string, password: string, companyId: string, projectName: string, columnName: string}
     */
    public function getCredentials(): array
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        $credentials = [
            'login' => '',
            'password' => '',
            'companyId' => '',
            'apiKey' => '',
            'projectName' => '',
            'projectId' => '',
            'boardId' => '',
            'columnName' => '',
        ];
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    continue;
                }
                if (str_starts_with($line, 'YOUGILE_LOGIN=')) {
                    $credentials['login'] = trim(substr($line, strlen('YOUGILE_LOGIN=')));
                }
                if (str_starts_with($line, 'YOUGILE_PASSWORD=')) {
                    $credentials['password'] = trim(substr($line, strlen('YOUGILE_PASSWORD=')));
                }
                if (str_starts_with($line, 'YOUGILE_COMPANY_ID=')) {
                    $credentials['companyId'] = trim(substr($line, strlen('YOUGILE_COMPANY_ID=')));
                }
                if (str_starts_with($line, 'YOUGILE_API_KEY=')) {
                    $credentials['apiKey'] = trim(substr($line, strlen('YOUGILE_API_KEY=')));
                }
                if (str_starts_with($line, 'YOUGILE_PROJECT_NAME=')) {
                    $credentials['projectName'] = trim(substr($line, strlen('YOUGILE_PROJECT_NAME=')));
                }
                if (str_starts_with($line, 'YOUGILE_PROJECT_ID=')) {
                    $credentials['projectId'] = trim(substr($line, strlen('YOUGILE_PROJECT_ID=')));
                }
                if (str_starts_with($line, 'YOUGILE_BOARD_ID=')) {
                    $credentials['boardId'] = trim(substr($line, strlen('YOUGILE_BOARD_ID=')));
                }
                if (str_starts_with($line, 'YOUGILE_COLUMN_NAME=')) {
                    $credentials['columnName'] = trim(substr($line, strlen('YOUGILE_COLUMN_NAME=')));
                }
            }
        }
        
        return $credentials;
    }

    /**
     * Выполнить HTTP запрос к Yougile API
     * @param string $method GET или POST
     * @param string $endpoint API эндпоинт
     * @param array|null $body Тело запроса (для POST)
     * @param bool $useBearerToken Использовать Bearer токен для авторизации
     * @return array{status: int, data: array}
     */
    private function makeRequest(string $method, string $endpoint, ?array $body = null, bool $useBearerToken = false): array
    {
        $url = self::YOUGILE_API_URL . $endpoint;
        
        $ch = curl_init($url);
        
        $headers = ['Content-Type: application/json'];
        
        if ($useBearerToken) {
            $credentials = $this->getCredentials();
            if (!empty($credentials['apiKey'])) {
                $headers[] = 'Authorization: Bearer ' . $credentials['apiKey'];
            }
        }
        
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ];
        
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'status' => 0,
                'data' => [],
                'error' => $error,
            ];
        }

        return [
            'status' => $httpCode,
            'data' => json_decode($response, true) ?? [],
        ];
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function getCompanies(): array
    {
        $credentials = $this->getCredentials();
        
        $result = $this->makeRequest('POST', '/api-v2/auth/companies', [
            'login' => $credentials['login'],
            'password' => $credentials['password'],
        ]);

        if ($result['status'] !== 200) {
            return [];
        }

        $data = $result['data'];
        
        if (isset($data['content']) && is_array($data['content'])) {
            return $data['content'];
        }
        
        return [];
    }

    /**
     * @return array<int, array{id: string, name: string, key: string}>
     */
    public function getApiKeys(): array
    {
        $credentials = $this->getCredentials();
        
        if (empty($credentials['companyId'])) {
            return [];
        }

        $result = $this->makeRequest('POST', '/api-v2/auth/keys/get', [
            'login' => $credentials['login'],
            'password' => $credentials['password'],
            'companyId' => $credentials['companyId'],
        ]);

        if ($result['status'] !== 200) {
            return [];
        }

        return $result['data'];
    }

    /**
     * Создать API ключ
     * @return array{id: string, key: string, name: string}|null
     */
    public function createApiKey(): ?array
    {
        $credentials = $this->getCredentials();
        
        if (empty($credentials['companyId'])) {
            return null;
        }

        $result = $this->makeRequest('POST', '/api-v2/auth/keys', [
            'login' => $credentials['login'],
            'password' => $credentials['password'],
            'companyId' => $credentials['companyId'],
        ]);

        if ($result['status'] !== 201) {
            return null;
        }

        return $result['data'];
    }

    /**
     * Получить список проектов
     * @return array<int, array{id: string, title: string}>
     */
    public function getProjects(): array
    {
        $credentials = $this->getCredentials();
        
        if (empty($credentials['apiKey'])) {
            return [];
        }
        
        $result = $this->makeRequest('GET', '/api-v2/projects', null, true);

        if ($result['status'] !== 200) {
            return [];
        }

        $data = $result['data'];
        
        if (isset($data['content']) && is_array($data['content'])) {
            return $data['content'];
        }
        
        return [];
    }

    /**
     * Получить ID проекта по имени
     */
    public function getProjectIdByName(string $name): ?string
    {
        $projects = $this->getProjects();
        
        foreach ($projects as $project) {
            if ($project['title'] === $name) {
                return $project['id'];
            }
        }
        
        return null;
    }

    /**
     * Получить список досок по projectId
     * @return array<int, array{id: string, title: string}>
     */
    public function getBoards(string $projectId): array
    {
        $credentials = $this->getCredentials();
        
        if (empty($credentials['apiKey'])) {
            return [];
        }
        
        $result = $this->makeRequest('GET', '/api-v2/boards?projectId=' . $projectId, null, true);

        if ($result['status'] !== 200) {
            return [];
        }

        $data = $result['data'];
        
        if (isset($data['content']) && is_array($data['content'])) {
            return $data['content'];
        }
        
        return [];
    }

    /**
     * Получить ID доски по имени
     */
    public function getBoardIdByName(string $projectId, string $name): ?string
    {
        $boards = $this->getBoards($projectId);
        
        foreach ($boards as $board) {
            if ($board['title'] === $name) {
                return $board['id'];
            }
        }
        
        return null;
    }

    /**
     * Получить список колонок
     * @return array<int, array{id: string, title: string}>
     */
    public function getColumns(string $boardId): array
    {
        $credentials = $this->getCredentials();
        
        if (empty($credentials['apiKey'])) {
            return [];
        }
        
        $result = $this->makeRequest('GET', '/api-v2/columns?boardId=' . $boardId, null, true);

        if ($result['status'] !== 200) {
            return [];
        }

        $data = $result['data'];
        
        if (isset($data['content']) && is_array($data['content'])) {
            return $data['content'];
        }
        
        return [];
    }

    /**
     * Получить ID колонки по имени
     */
    public function getColumnIdByName(string $boardId, string $name): ?string
    {
        $columns = $this->getColumns($boardId);
        
        foreach ($columns as $column) {
            if ($column['title'] === $name) {
                return $column['id'];
            }
        }
        
        return null;
    }

    /**
     * Создать задачу
     * @return array{id: string}|null
     */
    public function createTask(string $title, string $description = ''): ?array
    {
        $credentials = $this->getCredentials();
        
        if (empty($credentials['projectName']) || empty($credentials['columnName'])) {
            return null;
        }
        
        if (empty($credentials['apiKey'])) {
            return null;
        }

        if ($credentials['boardId']) {
            $boardId = $credentials['boardId'];
        } else {
            if ($credentials['projectId']) {
                $projectId = $credentials['projectId'];
            } else {
                $projectId = $this->getProjectIdByName($credentials['projectName']);
            }
            if (empty($projectId)) {
                return null;
            }

            // Найти доску (первую) в проекте
            $boards = $this->getBoards($projectId);

            if (empty($boards)) {
                return null;
            }

            $boardId = $boards[0]['id'] ?? null;
        }
        
        if (empty($boardId)) {
            return null;
        }

        // Найти колонку по имени
        $columnId = $this->getColumnIdByName($boardId, $credentials['columnName']);
        
        if (empty($columnId)) {
            return null;
        }

        $result = $this->makeRequest('POST', '/api-v2/tasks', [
            'title' => $title,
            'description' => $description,
            'columnId' => $columnId,
        ], true);

        if ($result['status'] !== 201) {
            return null;
        }

        return $result['data'];
    }

    /**
     * Получить информацию о компании и API ключах
     * @return array{status: string, company_id?: string, company_name?: string, api_keys?: array, message?: string}
     */
    public function getCompanyInfo(): array
    {
        $credentials = $this->getCredentials();
        
        if (empty($credentials['login']) || empty($credentials['password'])) {
            return [
                'status' => 'error',
                'message' => 'Учетные данные не найдены. Проверьте файл .env',
            ];
        }

        if (empty($credentials['companyId'])) {
            return [
                'status' => 'error',
                'message' => 'ID компании не найден. Проверьте YOUGILE_COMPANY_ID в файле .env',
            ];
        }

        $apiKeys = $this->getApiKeys();

        return [
            'status' => 'success',
            'company_id' => $credentials['companyId'],
            'company_name' => '',
            'api_keys' => $apiKeys,
        ];
    }
}
