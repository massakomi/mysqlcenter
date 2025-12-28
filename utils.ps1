Do
{
    $prompt = @(
    "-----------------------------------------:"
    "Select operation:"
    '1      - phpcbf controller'
    '2      - phpcs controller'
    '3      - prettier MysqlCenter'
    "0      - exit "
    ) -join "`n "
    $operation = Read-Host $prompt

    if ($operation -eq '1')
    {
        phpcbf .\controller
    }
    if ($operation -eq '2')
    {
        phpcs .\controller
    }
    if ($operation -eq '3')
    {
        # https://prettier.io/docs/options#semicolons
        # Запустить на всех файлах форматирование
        npx prettier .\js\MysqlCenter.js --write
    }
    if ($operation -eq '0')
    {
        break
    }
}
While (1)


