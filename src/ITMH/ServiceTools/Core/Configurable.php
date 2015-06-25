<?php

namespace ITMH\ServiceTools\Core;

/**
 * Interface Configurable
 * @package ITMH\ServiceTools\Core
 */
interface Configurable
{
    /**
     * Производит конфигурирование сервиса
     *
     * @param array $config опции конфигурации
     */
    public function configure(array $config = []);

    /**
     * Возвращает состояние конфигурации
     *
     * @return bool
     */
    public function isConfigured();
}
