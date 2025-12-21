Конфигурации подключения? Нужно ли? Да, у меня есть 3 локал базы 5.7, 8.4, докер. Хранится все в простом config. Ну не знаю.

```php
https://www.php.net/manual/ru/book.pdo.php

$msc->execPdo($sql)

$res = $msc->fetchPdo($sql);
foreach ($res as $row)
while ($o = $res->fetchObject($result))
while ($o = $res->fetchColumn($result))

$res->fetch() // получить один

$data = $msc->fetchPdo($sql)->fetchAll(); // сразу получить assoc array БЕЗ ПРОВЕРКИ
$data = $msc->getData('SHOW TABLE STATUS', PDO::FETCH_OBJ); // object array с проверкой

$data = $msc->fetchPdo($sql)->fetchAll(PDO::FETCH_KEY_PAIR); // две колонки объединяются key => value
$data = $msc->getData(($sql, PDO::FETCH_KEY_PAIR) // с проверкой

$data = $msc->fetchPdo($sql)->fetchAll(PDO::FETCH_COLUMN) // колонку
$data = $msc->getData($sql, PDO::FETCH_COLUMN) // колонку с проверкой

$result = $msc->fetchPdo($sql); // проверить что запрос что-то отдал, Null если ничего
if ($result) {
}
```




