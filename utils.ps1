Do
{
    $prompt = @(
        "Select command`n"
        'b - build webpack'
        'w - auto build webpack'
        'p - prettier'
        'cbf - phpcbf'
        'cs - phpcs'
        'css - phpcs summary only'
        "`n"
        'nc - node current'
        'nl - node list'
        'n1 - node 15'
        'n2 - node 24'
    ) -join ' '
    $operation = Read-Host $prompt

    Clear-Host
    if ($operation -eq 'cbf')
    {
        phpcbf .\controller .\src --standard=PSR12 -p
    }
    if ($operation -eq 'cs')
    {
        phpcs .\controller .\src --standard=PSR12
    }
    if ($operation -eq 'css')
    {
        phpcs .\controller .\src --standard=PSR12  -p --report=summary
    }

    if ($operation -eq 'b')
    {
        npx webpack
    }
    if ($operation -eq 'w')
    {
        npx webpack --watch
    }
    if ($operation -eq 'p')
    {
        # https://prettier.io/docs/options#semicolons
        # Запустить на всех файлах форматирование
        # npx prettier . --write
        npx prettier .\js\MysqlCenter.js --write
    }
    if ($operation -eq 'nc')
    {
        nvm current
    }
    if ($operation -eq 'nl')
    {
        nvm list
    }
    if ($operation -eq 'n1')
    {
        nvm use 15.14.0
    }
    if ($operation -eq 'n2')
    {
        nvm use 24.12.0
    }
}
While (1)

