<?php
/**
 * CURL helper - the simple PHP-library to do HTTP-requests.
 *
 * @author    Maksim T. <zapalm@yandex.com>
 * @copyright 2018 Maksim T.
 * @license   https://opensource.org/licenses/MIT MIT
 * @link      https://github.com/zapalm/curl-helper GitHub
 */

namespace zapalm\curlHelper;

use LogicException;

/**
 * CURL helper.
 *
 * Example:
 * ~~~
 * $helper = (new CurlHelper())
 *     ->setPost(false)
 *     ->setReturn(true)
 *     ->setHeader(false)
 *     ->setUserAgent('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.79 Safari/537.36 Edge/14.14393')
 *     ->setUrl('https://www.google.com/search?q=hello+world')
 * ;
 * $result = $helper->execute();
 * if (false === $result) {
 *     $errorMessage = $helper->getErrorMessage();
 * }
 * ~~~
 *
 * @author Maksim T. <zapalm@yandex.com>
 */
class CurlHelper
{
    /** @var resource Ресурс CURL. */
    protected $curl;

    /** @var array Опции настройки CURL. */
    protected $options = [];

    /** @var array Параметры CurlHelper. */
    protected $params = [];

    /** @var int|null Параметр CurlHelper - максимальная пауза между запросами (в секундах). См. {@see setSleepMaxSeconds()}.  */
    protected $sleepMaxSeconds;

    /** @var int|null Параметр CurlHelper - минимальная пауза между запросами (в секундах). См. {@see setSleepMinSeconds()}. */
    protected $sleepMinSeconds;

    /** @var bool Параметр CurlHelper - удалить ли UTF-8 BOM (byte-order mark) из контента после запроса. См. {@see setBomRemoving()}. */
    protected $bomRemoving;

    /** @var CurlProgressData Данные о прогрессе загрузки. */
    protected $progressData;

    /**
     * Конструктор.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function __construct()
    {
        $this->curl         = curl_init(); // Никогда не возвращает false, поэтому проверка не нужна: https://github.com/phpstan/phpstan/issues/1274
        $this->progressData = new CurlProgressData();

        $this->setConnectTimeOut(10);
        $this->setTimeOut(30);
        $this->setCaInfo(__DIR__ . '/certificates/Mozilla_CA_certificate.pem');
        $this->setBomRemoving(false);
    }

    /**
     * Деструктор.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function __destruct()
    {
        curl_close($this->curl);
    }

    /**
     * Экспортировать опции настройки CURL.
     *
     * @return array
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function exportOptions()
    {
        return $this->options;
    }

    /**
     * Экспортировать параметры CurlHelper.
     *
     * @return array
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function exportParams()
    {
        return $this->params;
    }

    /**
     * Импортировать опции настройки CURL.
     *
     * @param array $options
     *
     * @return static
     *
     * @throws LogicException
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function importOptions($options)
    {
        if ([] === $options) {
            throw new LogicException();
        }

        $this->options = [];
        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }

        return $this;
    }

    /**
     * Импортировать параметры CurlHelper.
     *
     * @param array $params
     *
     * @return static
     *
     * @throws LogicException
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function importParams($params)
    {
        if ([] === $params) {
            throw new LogicException();
        }

        $this->params = [];
        foreach ($params as $param => $value) {
            if (property_exists($this, $param)) {
                $this->$param         = $value;
                $this->params[$param] = $value;

                continue;
            }

            throw new LogicException('Не удалось установить параметр ' . $param . ' = ' . $value);
        }

        return $this;
    }

    /**
     * Установить опцию CURL.
     *
     * @param int   $option
     * @param mixed $value
     *
     * @throws LogicException
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    protected function setOption($option, $value)
    {
        if (curl_setopt($this->curl, $option, $value)) {
            $this->options[$option] = $value;

            return;
        }

        throw new LogicException('Не удалось установить опцию ' . $option . ' = ' . $value);
    }

    /**
     * Установить кодировку.
     *
     * @param string $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setEncoding($value)
    {
        $this->setOption(CURLOPT_ENCODING, $value);

        return $this;
    }

    /**
     * Установить предел ожидания соединения в секундах.
     *
     * @param int $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setConnectTimeOut($value)
    {
        $this->setOption(CURLOPT_CONNECTTIMEOUT, $value);

        return $this;
    }

    /**
     * Установить предел ожидания ответа на запрос в секундах.
     *
     * @param int $value Таймаут в секундах, который больше или равен нулю (при нуле на ожидание ответа не будет накладываться ограничение).
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setTimeOut($value)
    {
        $this->setOption(CURLOPT_TIMEOUT, $value);
        $this->setOption(CURLOPT_NOPROGRESS, true);               // Отключаем функцию прогресса, если вдруг она была включена ранее
        curl_setopt($this->curl, CURLOPT_PROGRESSFUNCTION, null); // Удаляем функцию прогресса на всякий случай по той же причине (так сразу, возможно, высвободим память)

        return $this;
    }

    /**
     * Установить HTTP-заголовки.
     *
     * @param string[] $value Список заголовков.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setHttpHeader(array $value)
    {
        $this->setOption(CURLOPT_HTTPHEADER, $value);

        return $this;
    }

    /**
     * Установить URL запроса.
     *
     * @param string $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setUrl($value)
    {
        $this->setOption(CURLOPT_URL, $value);

        return $this;
    }

    /**
     * Установить UserAgent.
     *
     * @param string $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setUserAgent($value)
    {
        $this->setOption(CURLOPT_USERAGENT, $value);

        return $this;
    }

    /**
     * Установить список Cookies.
     *
     * @param string $value Строка в формате "CookieName1=CookieValue1; CookieName2=CookieValue2".
     *
     * @return static
     *
     * @see setCookieFile()
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setCookie($value)
    {
        $this->setOption(CURLOPT_COOKIE, $value);

        return $this;
    }

    /**
     * Установить метод запроса POST.
     *
     * @param bool $value Указать true, чтобы установить метод запроса POST, иначе - false, чтобы метод запроса GET.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setPost($value)
    {
        $this->setOption(CURLOPT_CUSTOMREQUEST, null);

        if (false === $value) {
            // Сначала сбрасываем данные POST-запроса, если делается переход с POST на GET
            $this->setPostFields(null);

            $this->setOption(CURLOPT_HTTPGET, true);
            $this->setOption(CURLOPT_POST, false);
        } else {
            $this->setOption(CURLOPT_HTTPGET, false);
            $this->setOption(CURLOPT_POST, true);
        }

        return $this;
    }

    /**
     * Установить параметры запроса, которые отправляются методом POST.
     *
     * Для отправки данных в виде параметризованной строки (например: "param1=value1&param2=value2") предпочтительнее заранее
     * сформировать её с помощью {@see http_build_query()} и лишь затем передать полученную строку в этот метод, и тогда
     * вместе с этим CURL автоматически сформирует нужный заголовок по отправляемому типу данных:
     * "Content-Type: application/x-www-form-urlencoded". Иначе, при передаче массива в параметр метода, CURL автоматически
     * сформирует другой заголовок по отправляемому типу данных: "Content-Type: multipart/form-data; boundary..." и, в этом
     * случае, следует передавать именно ассоциативный массив, например: ['param1' => 'value1', 'param2' => 'value2'].
     *
     * Допускается передавать в параметре метода пустую строку и null (тогда тело запроса будет пустым). И не допускается
     * передавать пустой массив, т.к. скорее всего, такое может быть при логической ошибке (поэтому в метод добавлена
     * проверка для защиты от неё, хотя сам CURL разрешает такое значение), и также, аналогично не допускается, если передан
     * не ассоциативный массив параметров, т.к. в этом мало смысла а, если такое произошло, то скорее всего из-за логической
     * ошибки (хотя сам CURL разрешает такой вид массива).
     *
     * @param string[]|string|null $value Параметры запроса.
     *
     * @return static
     *
     * @see setHttpHeader() Для установки собственного заголовка по отправляемому типу данных.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setPostFields($value)
    {
        if (is_array($value)) {
            if ([] === $value) {
                throw new LogicException('Передан пустой массив параметров.');
            }

            foreach (array_keys($value) as $key) {
                if (false === is_string($key)) {
                    throw new LogicException('Передан не ассоциативный массив параметров.');
                }
            }
        }

        $this->setOption(CURLOPT_POSTFIELDS, $value);

        return $this;
    }

    /**
     * Установить метод запроса DELETE.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setDelete()
    {
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');

        return $this;
    }

    /**
     * Установить обратный адрес.
     *
     * @param string $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setReferer($value)
    {
        $this->setOption(CURLOPT_REFERER, $value);

        return $this;
    }

    /**
     * Установить, чтобы возвращал результат запроса.
     *
     * @param bool $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setReturn($value)
    {
        $this->setOption(CURLOPT_RETURNTRANSFER, $value);

        return $this;
    }

    /**
     * Установить заголовок.
     *
     * @param bool $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setHeader($value)
    {
        $this->setOption(CURLOPT_HEADER, $value);

        return $this;
    }

    /**
     * Установить, чтобы запрос не передавал данные.
     *
     * @param bool $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setNoBody($value)
    {
        $this->setOption(CURLOPT_NOBODY, $value);

        return $this;
    }

    /**
     * Установить, чтобы следовать переадресации.
     *
     * @param bool $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setFollowLocation($value)
    {
        if ('' === trim(ini_get('open_basedir')) && false === in_array(strtolower(ini_get('safe_mode')), array('1', 'on', 'yes', 'true'), true)) {
            $this->setOption(CURLOPT_FOLLOWLOCATION, $value);
        }

        return $this;
    }

    /**
     * Установить адрес прокси.
     *
     * @param string|null $value Строка адреса в формате IP:port или null для сброса значения.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setProxy($value)
    {
        $this->setOption(CURLOPT_PROXY, $value);

        return $this;
    }

    /**
     * Установить тип прокси.
     *
     * @param int|null $value Варианты констант: CURLPROXY_HTTP, CURLPROXY_SOCKS5, CURLPROXY_SOCKS4. Или null для сброса значения.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setProxyType($value)
    {
        $this->setOption(CURLOPT_PROXYTYPE, $value);

        return $this;
    }

    /**
     * Установить пользователя прокси.
     *
     * @param string|null $value Логин или null для сброса значения.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setProxyUser($value)
    {
        $this->setOption(CURLOPT_PROXYUSERNAME, $value);

        return $this;
    }

    /**
     * Установить пароль прокси.
     *
     * @param string|null $value Пароль или null для сброса значения.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setProxyPassword($value)
    {
        $this->setOption(CURLOPT_PROXYPASSWORD, $value);

        return $this;
    }

    /**
     * Установить опции, проверять или нет SSL со стороны прокси-сервера.
     *
     * @param bool $value Указать false, чтобы не проверять SSL, иначе - true.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setProxySslNoVerify($value)
    {
        if (defined('CURLOPT_PROXY_SSL_VERIFYPEER')) {
            $this->setOption(CURLOPT_PROXY_SSL_VERIFYPEER, $value);
        }

        if (defined('CURLOPT_PROXY_SSL_VERIFYHOST')) {
            $this->setOption(CURLOPT_PROXY_SSL_VERIFYHOST, (false === $value ? 0 : 2));
        }

        return $this;
    }

    /**
     * Получить локальный IP-адрес.
     *
     * @return string
     *
     * @see getLocalPort() Для получения локального порта.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getLocalIp()
    {
        return curl_getinfo($this->curl, CURLINFO_LOCAL_IP);
    }

    /**
     * Получить локальный порт.
     *
     * @return string
     *
     * @see getLocalIp() Для получения локального IP-адреса.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getLocalPort()
    {
        return curl_getinfo($this->curl, CURLINFO_LOCAL_PORT);
    }

    /**
     * Получить скорость загрузки.
     *
     * @return float Количество мегабайт.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getDownloadSpeed()
    {
        return round(
            curl_getinfo($this->curl, CURLINFO_SPEED_DOWNLOAD) / 1024 / 1024,
            4
        );
    }

    /**
     * Установить подробный режим (для отладки).
     *
     * @param bool $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setVerbose($value)
    {
        $this->setOption(CURLOPT_VERBOSE, $value);

        return $this;
    }

    /**
     * Установить опции, проверять или нет SSL.
     *
     * @param bool $value Указать false, чтобы не проверять SSL, иначе - true.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setSslNoVerify($value)
    {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, $value);
        $this->setOption(CURLOPT_SSL_VERIFYHOST, (false === $value ? 0 : 2));

        if (defined('CURLOPT_SSL_VERIFYSTATUS')) {
            $this->setOption(CURLOPT_SSL_VERIFYSTATUS, $value); // Эта опция, в настоящее время, поддерживается только бэкэндами OpenSSL, GnuTLS и NSS TLS.
        }

        return $this;
    }

    /**
     * Установить, какой протокол использовать при получении IP по домену из URL запроса.
     *
     * @param int $value Протокол из списка CURL_IPRESOLVE_V4, CURL_IPRESOLVE_V6, CURL_IPRESOLVE_WHATEVER
     *
     * @return static
     *
     * @see https://stackoverflow.com/questions/28366402/failed-to-connect-to-www-googleapis-com-port-443-network-unreachable
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setIpResolve($value)
    {
        $this->setOption(CURLOPT_IPRESOLVE, $value);

        return $this;
    }

    /**
     * Установить сетевой интерфейс.
     *
     * @param string|null $value Интерфейс в формате IP-адреса или наименования интерфейса, например: "eth0". Или null для сброса значения.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setInterface($value)
    {
        $this->setOption(CURLOPT_INTERFACE, $value);

        return $this;
    }

    /**
     * Установить режим автоматической установки referer.
     *
     * @param bool $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setAutoReferer($value)
    {
        $this->setOption(CURLOPT_AUTOREFERER, $value);

        return $this;
    }

    /**
     * Установить максимальную паузу между запросами (в секундах).
     *
     * @param int $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    protected function setSleepMaxSeconds($value)
    {
        $this->sleepMaxSeconds           = $value;
        $this->params['sleepMaxSeconds'] = $value;

        return $this;
    }

    /**
     * Установить минимальную паузу между запросами (в секундах).
     *
     * @param int $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    protected function setSleepMinSeconds($value)
    {
        $this->sleepMinSeconds           = $value;
        $this->params['sleepMinSeconds'] = $value;

        return $this;
    }

    /**
     * Установить паузу между запросами.
     *
     * @param int $min Минимальная пауза.
     * @param int $max Максимальная пауза.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setPause($min, $max)
    {
        $this->setSleepMinSeconds($min);
        $this->setSleepMaxSeconds($max);

        return $this;
    }

    /**
     * Установить, удалить ли UTF-8 BOM (byte-order mark) из контента после запроса.
     *
     * @param bool $value
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setBomRemoving($value)
    {
        $this->bomRemoving           = $value;
        $this->params['bomRemoving'] = $value;

        return $this;
    }

    /**
     * Установить файл для автоматического сохранения Cookies при ответе сервера на запрос.
     *
     * @param string $value Путь к файлу для записи Cookies.
     *
     * @return static
     *
     * @see setCookieFile()
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setCookieJar($value)
    {
        $this->setOption(CURLOPT_COOKIEJAR, $value);

        return $this;
    }

    /**
     * Установить файл со списком Cookies для отправки при запросе.
     *
     * Возможно совместное применение этого метода с методом {@see setCookie()}, но не без нюанса - списки Cookies будут
     * конкатенированы, т.е. одна из Cookie с тем же наименованием, но с другим значением не будет объединена/перезаписана.
     *
     * @param string $value Путь к файлу в формате Netscape, см. {@link https://curl.se/docs/http-cookies.html}.
     *
     * @return static
     *
     * @see setCookieJar()
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setCookieFile($value)
    {
        $this->setOption(CURLOPT_COOKIEFILE, $value);

        return $this;
    }

    /**
     * Установить файл сертификата.
     *
     * @param string $filePath Путь к файлу сертификата.
     *
     * @return static
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setCaInfo($filePath)
    {
        if (false === file_exists($filePath)) {
            throw new LogicException('Файл сертификата не найден: ' . $filePath);
        }

        $this->setOption(CURLOPT_CAINFO, $filePath);

        return $this;
    }

    /**
     * Выполнить запрос.
     *
     * @return bool|string
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function execute()
    {
        if (null !== $this->sleepMinSeconds && null !== $this->sleepMaxSeconds) {
            sleep(rand($this->sleepMinSeconds, $this->sleepMaxSeconds));
        }

        $this->progressData->downloadedBytes   = null;
        $this->progressData->downloadTimeCheck = null;
        $this->progressData->startTime         = microtime(true);

        $result = curl_exec($this->curl);
        if (is_string($result) && $this->bomRemoving) {
            $result = str_replace("\xEF\xBB\xBF", '', $result);
        }

        $this->progressData->endTime = microtime(true);

        return $result;
    }

    /**
     * Получить параметры запроса из URL.
     *
     * @return string
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getQuery()
    {
        $options = $this->exportOptions();
        if ([] !== $options) {
            if (isset($options[CURLOPT_POSTFIELDS])) {
                return (string)$options[CURLOPT_POSTFIELDS];
            }

            if (isset($options[CURLOPT_URL])) {
                return (string)parse_url($options[CURLOPT_URL], PHP_URL_QUERY);
            }
        }

        return '';
    }

    /**
     * Получить время установки соединения.
     *
     * @return float Количество секунд.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getConnectionTime()
    {
        return curl_getinfo($this->curl, CURLINFO_CONNECT_TIME);
    }

    /**
     * Получить количество секунд, которые были затрачены на передачу данных.
     *
     * @return float Количество секунд.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getTotalTime()
    {
        return curl_getinfo($this->curl, CURLINFO_TOTAL_TIME);
    }

    /**
     * Получить код последней ошибки.
     *
     * @return int Код ошибки или 0, если ошибок не было.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getErrorCode()
    {
        return curl_errno($this->curl);
    }

    /**
     * Получить сообщение о последней ошибке.
     *
     * @return string
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getErrorMessage()
    {
        return curl_error($this->curl);
    }

    /**
     * Получить сообщение об ошибке по её коду.
     *
     * @param int $errorCode Код ошибки.
     *
     * @return string|null Вернёт описание ошибки или null, если ошибки нет.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getErrorMessageByCode($errorCode)
    {
        return curl_strerror($errorCode);
    }

    /**
     * Получить код ответа веб-сервера.
     *
     * @return int
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getHttpCode()
    {
        return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    }

    /**
     * Получить время выполнения запроса (в секундах с микросекундами).
     *
     * @return float
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getExecutionTime()
    {
        return (float)($this->progressData->endTime - $this->progressData->startTime);
    }

    /**
     * Извлечь список Cookies из контента заголовка.
     *
     * Метод сделан для удобства работы с методом {@see setCookie()}, поэтому он для него возвращает строку в нужном формате.
     *
     * Если нужен массив, то его можно получить так:
     * ~~~
     * $cookies = explode('; ', CurlHelper::parseCookie($headerContent));
     * ~~~
     * Но это будет нумерованный массив, а не ассоциативный в виде "Наименование Cookie => Значение Cookie" и ещё параметры
     * будут экранированы с помощью {@see urlencode()} при наличии специальных символов, поэтому может потребоваться
     * применить {@see urldecode()}.
     *
     * @param string $headerContent Контент заголовка ответа.
     *
     * @return string|null Строка со списком Cookies или null, если не были найдены в заголовке.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public static function parseCookie($headerContent)
    {
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headerContent, $matches);
        if (isset($matches[1])) {
            $cookies = array();
            foreach ($matches[1] as $item) {
                parse_str($item, $cookie);
                $cookies = array_merge($cookies, $cookie);
            }

            // Собираем заново в одну строку этой функцией, т.к. подходит идеально - разделитель может состоять более, чем из
            // одно символа и он не экранируется; применяется по-умолчанию нужное экранирование параметров (PHP_QUERY_RFC1738).
            return http_build_query($cookies, '', '; ');
        }

        return null;
    }
}