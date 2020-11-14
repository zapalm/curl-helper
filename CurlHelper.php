<?php
/**
 * CURL helper - the simple PHP-library to do HTTP-requests.
 *
 * @author    Maksim T. <zapalm@yandex.com>
 * @copyright 2018 Maksim T.
 * @license   https://opensource.org/licenses/MIT MIT
 * @link      https://github.com/zapalm/CurlHelper GitHub
 */

namespace zapalm\curlHelper;

use InvalidArgumentException;

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
 * @version 0.39.0
 *
 * @author Maksim T. <zapalm@yandex.com>
 */
class CurlHelper
{
    /** @var resource Ресурс curl */
    private $curl;

    /** @var string[] Опции настройки cUrl */
    private $options = [];

    /** @var string[] Параметры запроса */
    private $params = [];

    /** @var int Максимальная пауза между запросами (в секундах) */
    private $sleepMaxSeconds;

    /** @var int Минимальная пауза между запросами (в секундах) */
    private $sleepMinSeconds;

    /** @var float Начало запроса */
    private $startTime;

    /** @var float Окончание запроса */
    private $endTime;

    /**
     * Конструктор.
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function __construct()
    {
        $this->curl = curl_init(); // Никогда не возвращает false, поэтому проверка не нужна: https://github.com/phpstan/phpstan/issues/1274

        $this->setConnectTimeOut(10);
        $this->setTimeOut(30);
        $this->setCaInfo(__DIR__ . '/certificates/Mozilla_CA_certificate.pem');
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
     * Экспортировать опции настройки cUrl.
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
     * Экспортировать параметры запроса.
     *
     * @return string[]
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function exportParams()
    {
        return $this->params;
    }

    /**
     * Импортировать опции настройки cUrl.
     *
     * @param string[] $options
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function importOptions($options)
    {
        $this->options = [];
        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }
    }

    /**
     * Импортировать параметры запроса.
     *
     * @param string[] $params
     *
     * @throws InvalidArgumentException
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function importParams($params)
    {
        $this->params = [];
        foreach ($params as $param => $value) {
            if (property_exists($this, $param)) {
                $this->$param         = $value;
                $this->params[$param] = $value;

                continue;
            }

            throw new InvalidArgumentException('Не удалось установить параметр ' . $param . ' = ' . $value);
        }
    }

    /**
     * Установить опцию.
     *
     * @param int             $option
     * @param string|string[] $value
     *
     * @throws InvalidArgumentException
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    private function setOption($option, $value)
    {
        if (curl_setopt($this->curl, $option, $value)) {
            $this->options[$option] = $value;

            return;
        }

        throw new InvalidArgumentException('Не удалось установить опцию ' . $option . ' = ' . print_r($value, true));
    }

    /**
     * Установить кодировку.
     *
     * @param string $value
     *
     * @return $this
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
     * @return $this
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
     * @param int $value
     *
     * @return $this
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setTimeOut($value)
    {
        $this->setOption(CURLOPT_TIMEOUT, $value);

        return $this;
    }

    /**
     * Установить HTTP-заголовок.
     *
     * @param string[] $value
     *
     * @return $this
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
     * @return $this
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
     * @return $this
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setUserAgent($value)
    {
        $this->setOption(CURLOPT_USERAGENT, $value);

        return $this;
    }

    /**
     * Установить Cookie.
     *
     * @param string $value
     *
     * @return $this
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
     * @param bool $value
     *
     * @return $this
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setPost($value)
    {
        $this->setOption(CURLOPT_POST, $value);

        return $this;
    }

    /**
     * Установить параметры запроса, который отправляется методом POST.
     *
     * @param string[]|string $value Параметры запроса.
     *
     * @return $this
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setPostFields($value)
    {
        $this->setOption(CURLOPT_POSTFIELDS, $value);

        return $this;
    }

    /**
     * Установить обратный адрес.
     *
     * @param string $value
     *
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * Установить proxy.
     *
     * @param string $value Строка адреса в формате IP:port
     *
     * @return $this
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setProxy($value)
    {
        $this->setOption(CURLOPT_PROXY, $value);

        return $this;
    }

    /**
     * Установить тип прокси (CURLPROXY_HTTP, CURLPROXY_SOCKS5, CURLPROXY_SOCKS4).
     *
     * @param int $value
     *
     * @return $this
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setProxyType($value)
    {
        $this->setOption(CURLOPT_PROXYTYPE, $value);

        return $this;
    }

    /**
     * Установить подробный режим (для отладки).
     *
     * @param bool $value
     *
     * @return $this
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
     * @return $this
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setSslNoVerify($value)
    {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, $value);
        $this->setOption(CURLOPT_SSL_VERIFYHOST, (false === $value ? 0 : 2));

        if (defined('CURLOPT_SSL_VERIFYSTATUS')) {
            $this->setOption(CURLOPT_SSL_VERIFYSTATUS, $value); // This option is currently only supported by the OpenSSL, GnuTLS and NSS TLS backends.
        }

        return $this;
    }

    /**
     * Установить, какой протокол использовать при получении IP по домену из URL запроса.
     *
     * @param int $value Протокол из списка CURL_IPRESOLVE_V4, CURL_IPRESOLVE_V6, CURL_IPRESOLVE_WHATEVER
     *
     * @return $this
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
     * Установить режим автоматической установки referer.
     *
     * @param bool $value
     *
     * @return $this
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
     * @return $this
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    private function setSleepMaxSeconds($value)
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
     * @return $this
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    private function setSleepMinSeconds($value)
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
     * @return $this
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
     * Установить файл для хранения Cookie.
     *
     * @param string $value
     *
     * @return $this
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
     * Установить файл для хранения Cookie.
     *
     * @param string $value
     *
     * @return $this
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
     * @return $this
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function setCaInfo($filePath)
    {
        if (false === file_exists($filePath)) {
            throw new InvalidArgumentException('Файл сертификата не найден: ' . $filePath);
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

        $this->startTime = microtime(true);

        $result = curl_exec($this->curl);
        if (is_string($result)) {
            $result = str_replace("\xEF\xBB\xBF", '', $result); // Removing UTF BOM (byte-order mark)
        }

        $this->endTime   = microtime(true);

        return $result;
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
     * Получить время выполнения запроса (в секундах).
     *
     * @return int
     *
     * @author Maksim T. <zapalm@yandex.com>
     */
    public function getExecutionTime()
    {
        return ($this->endTime - $this->startTime);
    }

    /**
     * Разобрать cookie.
     *
     * @param string $headerContent Контент заголовка ответа.
     *
     * @return string|null Строка с куками или null, если не найдены в заголовке.
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

            return http_build_query($cookies, null, ';');
        }

        return null;
    }
}