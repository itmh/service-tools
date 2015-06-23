<?php

namespace ServiceTools\Core;

/**
 * Interface Configurable
 * @package ServiceTools\Core
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
