<?php

namespace ITMH\ServiceTools\Helpers;

use ITMH\ServiceTools\Core\Configurable;
use ITMH\ServiceTools\Core\ConfigurationErrorException;

/**
 * Class Pinba
 * @package ITMH\ServiceTools\Core
 */
class Pinba implements Configurable
{
    const ERR__TYPE_REQUIRED = 'Type required';
    const ERR__HOST_REQUIRED = 'Target host name required';

    /**
     * Флаг доступности
     *
     * @var bool
     */
    private $enabled;

    /**
     * Опции конфигурации
     *
     * @var array
     */
    private $config;

    /**
     * Флаг конфигурации
     *
     * @var boolean
     */
    private $configured;

    /**
     * Конструктор без параметров, определяет доступность расширения
     */
    public function __construct()
    {
        $this->enabled = extension_loaded('pinba') && ini_get('pinba.enabled');
    }


    /**
     * Производит конфигурирование сервиса
     *
     * @param array $config опции конфигурации
     *
     * @throws ConfigurationErrorException
     */
    public function configure(array $config = [])
    {
        if (array_key_exists('type', $config)) {
            throw new ConfigurationErrorException(self::ERR__TYPE_REQUIRED);
        }
        if (array_key_exists('target', $config)) {
            throw new ConfigurationErrorException(self::ERR__HOST_REQUIRED);
        }
        $this->config = $config;

        $this->configured = true;
    }

    /**
     * Возвращает состояние конфигурации
     *
     * @return bool
     */
    public function isConfigured()
    {
        return $this->configured;
    }

    /**
     * Запускает таймер и возвращает ресурс
     * https://github.com/tony2001/pinba_engine/wiki/PHP-extension#pinba_timer_start
     *
     * @param array $tags Любые произвольные данные
     *
     * @return resource|null
     */
    public function start(array $tags = [])
    {
        if (!$this->isEnabled()) {
            return null;
        }

        /** @noinspection PhpUndefinedFunctionInspection */

        return pinba_timer_start(array_merge([
            'type' => $this->config['type'],
            'target' => $this->config['target']
        ]), $tags);
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
