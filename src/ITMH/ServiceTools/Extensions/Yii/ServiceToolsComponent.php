<?php

namespace ITMH\ServiceTools\Extensions\Yii;

use ITMH\ServiceTools\Core\Response;
use ITMH\ServiceTools\Core\Service;

/** @noinspection
 * PhpUndefinedNamespaceInspection
 * PhpUndefinedClassInspection
 * PhpUnnecessaryFullyQualifiedNameInspection
 */
class ServiceToolsComponent extends \yii\base\Component
{
    /**
     * @var string
     */
    public $service;

    /**
     * @var array
     */
    public $options;

    /**
     * @var Service
     */
    protected $instance;

    /**
     * Выполняет пробрасывание метода к экземпляру сервиса
     *
     * @param string $method имя вызываемого метода
     * @param array  $args   аргументы
     *
     * @return Response
     */
    public function __call($method, $args = [])
    {
        if (null === $this->instance) {
            $this->init();
        }

        return call_user_func_array([$this->instance, $method], $args);
    }

    /**
     * Инициализирует компонент
     * @return void
     */
    public function init()
    {
        $this->instance = new $this->service;
        $this->instance->configure($this->options);
    }

}
