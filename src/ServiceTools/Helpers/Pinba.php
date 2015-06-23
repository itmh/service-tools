<?php

namespace ServiceTools\Helpers;

/**
 * Class Pinba
 * @package ServiceTools\Core
 */
class Pinba
{
    /**
     * Флаг доступности
     *
     * @var bool
     */
    private $enabled;

    /**
     * Конструктор без параметров, определяет доступность расширения
     */
    public function __construct()
    {
        $this->enabled = extension_loaded('pinba') && ini_get('pinba.enabled');
    }

    /**
     * Запускает таймер и возвращает ресурс
     * https://github.com/tony2001/pinba_engine/wiki/PHP-extension#pinba_timer_start
     *
     * @param string $type   Тип измерения (например SoapService)
     * @param string $target Цель измерения (например имя метода)
     * @param array  $more   Любые произвольные данные
     *
     * @return resource|null
     */
    public function start($type, $target, array $more = [])
    {
        if (!$this->isEnabled()) {
            return null;
        }

        /** @noinspection PhpUndefinedFunctionInspection */

        return pinba_timer_start(array_merge([
            'type' => $type,
            'target' => $target
        ]), $more);
    }

    /**
     * Возвращает флаг доступности
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Останавливает таймер
     * https://github.com/tony2001/pinba_engine/wiki/PHP-extension#pinba_timers_stop
     *
     * @param resource $resource Ресурс запущенного таймера
     *
     * @return bool|null
     */
    public function stop($resource)
    {
        if (!is_resource($resource) || !$this->isEnabled()) {
            return true;
        }

        /** @noinspection PhpUndefinedFunctionInspection */

        return pinba_timer_stop($resource);
    }

    /**
     * Возвращает информацию о конкретном измерении
     *
     * @param resource $resource Ресурс запущенного таймера
     *
     * @return array
     */
    public function info($resource)
    {
        if (!is_resource($resource) || !$this->isEnabled()) {
            return array();
        }

        /** @noinspection PhpUndefinedFunctionInspection */

        return pinba_timer_get_info($resource);
    }
}
