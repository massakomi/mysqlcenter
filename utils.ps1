Do
{
    $prompt = @(
    "-----------------------------------------:"
    "Select operation:"
    'cs - phpcs'
    'cbf - phpcbf autofix'
    "0      - exit "
    ) -join "`n "
    $operation = Read-Host $prompt

    if ($operation -eq 'cs')
    {
        phpcs includes --standard=PSR12
    }
    if ($operation -eq '2')
    {
        phpcbf includes --standard=PSR12
    }

    if ($operation -eq '0')
    {
        break
    }
}
While (1)


