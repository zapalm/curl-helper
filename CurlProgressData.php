<?php
/**
 * CURL helper - это полностью документированная на русском PHP-библиотека для отправки HTTP-запросов.
 *
 * @author    Maksim T. <zapalm@yandex.com>
 * @copyright 2018 Maksim T.
 * @license   https://opensource.org/licenses/MIT MIT
 * @link      https://github.com/zapalm/curl-helper GitHub
 */

namespace zapalm\curlHelper;

/**
 * Модель данных о прогрессе загрузки.
 */
class CurlProgressData
{
    /** @var float|null Начало запроса (метка времени с микросекундами). */
    public $startTime;

    /** @var float|null Окончание запроса (метка времени с микросекундами). */
    public $endTime;

    /** @var int|null Загружено байт. */
    public $downloadedBytes;

    /** @var float|null Метка времени с микросекундами, когда была загружена порция данных. */
    public $downloadTimeCheck;
}