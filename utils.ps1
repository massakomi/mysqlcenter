Do
{
    $prompt = @(
        "Select command`n"
        'b - build webpack'
        'w - auto build webpack'
        "`n"
        'p - prettier'
        'es - eslint check all'
        'esh - eslint help'
        "`n"
        'cbf - phpcbf'
        'cf - php-cs-fixer'
        'cs - phpcs'
        'css - phpcs summary only'
        "`n"
        'stan - phpstan analyse, levels: '
        'stan0 stan1..8'
        "`n"
        'nc - node current'
        'nl - node list'
        'n1 - node 15'
        'n2 - node 24'
    ) -join ' '
    $operation = Read-Host $prompt

    Clear-Host
    switch ($operation) {
    "b"         { npx webpack }
    "w"         { npx webpack --watch }

    "p"         { npx prettier .\js\MysqlCenter.js --write }
    "es"        { npx eslint }
    "esh"       { npx eslint -h }

    "cbf"       { vendor/bin/phpcbf .\controller .\src --standard=PSR12 -p }
    "cf"        { vendor/bin/php-cs-fixer fix }
    "cs"        { vendor/bin/phpcs .\controller .\src --standard=PSR12 }
    "css"       { vendor/bin/phpcs .\controller .\src --standard=PSR12  -p --report=summary }

    "stan"      { phpstan analyse }
    "stan0"     { phpstan analyse -l 0 }
    "stan1"     { phpstan analyse -l 1 }
    "stan2"     { phpstan analyse -l 2 }
    "stan3"     { phpstan analyse -l 3 }
    "stan4"     { phpstan analyse -l 4 }
    "stan5"     { phpstan analyse -l 5 }
    "stan6"     { phpstan analyse -l 6 }
    "stan7"     { phpstan analyse -l 7 }
    "stan8"     { phpstan analyse -l 8 }

    "nc"        { nvm current }
    "nl"        { nvm list }
    "n1"        { nvm use 15.14.0 }
    "n2"        { nvm use 24.12.0 }
    }

}
While (1)

