Do {
    $prompt = @"
Select command

b  - build webpack
w  - auto build webpack

p  - prettier
es - eslint check all
esh- eslint help

cf - fixer
cs - fixer check

stan  - phpstan analyse
stan# - phpstan analyse level 0-8

nc - node current
nl - node list
n1 - node 15
n2 - node 24

q  - quit
"@
    $operation = Read-Host $prompt
    Clear-Host

    switch -Regex ($operation) {
        "^b$"       { npx webpack }
        "^w$"       { npx webpack --watch }

        "^p$"       { npx prettier .\js\MysqlCenter.js --write }
        "^es$"      { npx eslint }
        "^esh$"     { npx eslint -h }

        "^cf$"      { vendor/bin/php-cs-fixer fix }
        "^cs$"      { vendor/bin/php-cs-fixer check }

        "^stan$"    { phpstan analyse }
        "^stan([0-8])$" { phpstan analyse -l $Matches[1] }

        "^nc$"      { nvm current }
        "^nl$"      { nvm list }
        "^n1$"      { nvm use 15.14.0 }
        "^n2$"      { nvm use 24.12.0 }

        "^q$"       { return }
        default     { Write-Host "Unknown command: $operation" -ForegroundColor Yellow }
    }
}
While ($true)

