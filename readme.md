# CURL helper
`CURL helper` - это полностью документированная на русском PHP-библиотека для отправки HTTP-запросов, которая обладает характеристиками:
- Простая в применении.
- Код легко переиспользовать и расширить.
- Включает специальные настройки и функции.
- Работает на старых и новых версиях PHP (начиная с PHP 5.5 и до последней актуальной PHP 8).
- Протестирована временем и работает в реальных проектах с 2018 года. 

## Примеры
Каждый метод полностью документирован в виде PHPDoc, поэтому вот несколько примеров для ознакомления с библиотекой.

### Узнаём свой внешний и локальный IP (GET-запрос)
```php
$curlHelper = (new CurlHelper())
    ->setUrl('https://canhazip.com')
;

$myExternalIp = trim($curlHelper->execute()); // Ответ от сервера в виде обычного текста: 188.114.99.224
$myLocalIp    = $curlHelper->getLocalIp();    // Результат: 192.168.0.3
```

### Устанавливаем свой User-Agent и убеждаемся, что именно он отправляется (GET-запрос) 
```php
$myUserAgentInJson = (new CurlHelper())
    ->setUrl('https://httpbin.org/user-agent')
    ->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36')
    ->execute()
; // Ответ от сервера в виде JSON-строки: {"user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36"}
```

### Отправляем POST-запрос
```php
$postResultInJson = (new CurlHelper())
   ->setUrl('https://httpbin.org/post')
   ->setPost(true)
   ->setPostFields('MyNameIs=CurlHelper&YourNameIs=HttpBin')
   ->execute()
; // Ответ от сервера в виде JSON-строки с данными об отправленном ему запросе (длинная строка, поэтому не приведена)
```

### Получаем скорость загрузки данных (GET-запрос)
```php
$curlHelper = (new CurlHelper())
   ->setUrl('https://httpbin.org/bytes/' . (100 * 1024)) // Запращиваем 100 килобайт случайных бинарных данных
;

$curlHelper->execute();

$downloadSpeed   = $curlHelper->getDownloadSpeed();   // Скорость загрузки мегабайт/сек.: 0.0459
$downloadedBytes = $curlHelper->getDownloadedBytes(); // Загружено байт: 102400 (сколько запросили - столько получили)
```

### Пример проверки ошибки HTTP и DELETE-запроса
```php
$curlHelper = (new CurlHelper())
   ->setUrl('https://httpbin.org/status/302') // Запрашиваем у сервера, чтобы вернул ошибку HTTP 302
   ->setDelete()
;

$curlHelper->execute();

if (302 === $curlHelper->getHttpCode()) {
   // Ожидаем этот вывод, т.е.: "Ошибка с кодом HTTP 302"
   echo 'Ошибка с кодом HTTP ' . $curlHelper->getHttpCode() . PHP_EOL;
} else {
   echo 'Неожиданный ответ от сервера с кодом HTTP ' . $curlHelper->getHttpCode() . PHP_EOL;
}
```

### Пример проверки ошибки CURL (любой тип запроса)
```php
$result = (new CurlHelper())
   ->setUrl('https://__^__') // Указываем некорректный URL
   ->execute()
;

if (false === $result) {
   // Ожидаем ошибку "Could not resolve host: __^__"
   echo 'Ошибка CURL: ' . $curlHelper->getErrorMessage() . PHP_EOL;
} else {
   echo 'Ожидали ошибку CURL, но получили в результате: ' . var_export($result, true) . PHP_EOL;
}
```

## Установка
Проще всего добавить библиотеку в ваш проект через `Composer`. Пример описания подключения через редактирование файла `composer.json`:
```json
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/zapalm/curl-helper"
  }
],
"require": {
  "php": ">=5.5",
  "zapalm/curl-helper": "dev-master"
},
```

## Как помочь проекту расти и обновляться
Подарите **звезду** проекту. Вот и все! :)

## Инструкция, если вы захотите поучаствовать в разработке кода
- Форкнуть репозиторий.
- Переключиться на ветку `dev`. Т.к. код в этой ветке может быть неактуальный, то предварительно проверьте через слияние ветки `master` в `dev`. 
- Внести изменения в код, который вы собираетесь отправить в основной репозиторий. Если вы создали новый метод, то добавьте
  в PHPDoc тэг `author` в точности по примеру, как сделано в существующем коде (также этот тэг нужно добавить в конец списка
  других таких же тэгов, если вы добавили примерно 30% или более нового кода в уже существующий метод). Следуйте гайдлайнам 
  PSR (следуйте стилю программирования, как сделано в существующем коде). 
- Сделайте пул-запрос в основной репозиторий.
- Ожидайте ревью.
