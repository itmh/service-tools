<?php

namespace ITMH\ServiceTools\Core;

use ITMH\ServiceTools\Helpers\Pinba;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Stash\Driver\Composite;
use Stash\Driver\Ephemeral;
use Stash\Interfaces\DriverInterface;
use Stash\Pool;

/**
 * Class Service
 * @package ITMH\ServiceTools\Core
 */
abstract class Service implements Configurable
{
    const CLASS_NAME = __CLASS__;

    const ERR__NOT_CONFIGURED = 'Service "%s" is not configured';

    /**
     * Экземпляр кэшера
     *
     * @var \Stash\Pool
     */
    protected $cacher;

    /**
     * Карта со сроками жизни результатов
     * по каждому методу
     *
     * @var array
     */
    protected $cacherExpires = [];

    /**
     * Экземпляр логгера
     *
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * Экземпляр логгера времени выполнения
     *
     * @var Pinba
     */
    protected $pinba;

    /**
     * Флаг конфигурации
     *
     * @var boolean
     */
    private $configured;

    /**
     * Конструктор без параметров, поля инициализируются
     * значениями по умолчанию
     */
    public function __construct()
    {
        $this->cacher = new Pool(new Ephemeral());
        $this->logger = new Logger('service-tools', [new ErrorLogHandler()]);
        $this->pinba = new Pinba();
    }

    /**
     * Возвращает рекомендуемую конфигурацию для кэшера,
     * с возможностью перезаписи некоторых параметров
     *
     * @param array $config Значения для перезаписи
     *
     * @return array
     *
     * @codeCoverageIgnore Метод не содержит логики
     */
    public static function getDefaultCacherConfig(array $config = [])
    {
        $defaults = [
            'drivers' => [
                [
                    'class' => 'Stash\Driver\FileSystem',
                    'options' => [
                        'path' => '/tmp/stash'
                    ]
                ]
            ],
            'expires' => [
                'default' => 0
            ]
        ];

        return array_replace_recursive($defaults, $config);
    }

    /**
     * Возвращает рекомендуемую конфигурацию для логгера,
     * с возможностью перезаписи некоторых параметров
     *
     * @param array $config Значения для перезаписи
     *
     * @return array
     *
     * @codeCoverageIgnore There is no logic
     */
    public static function getDefaultLoggerConfig(array $config = [])
    {
        $defaults = [
            'name' => 'service-tools',
            'handlers' => [
                [
                    'class' => 'Monolog\Handler\ErrorLogHandler',
                    'options' => [
                        'level' => Logger::DEBUG
                    ]
                ]
            ]
        ];

        return array_replace_recursive($defaults, $config);
    }

    /**
     * Возвращает экземпляр кэшера
     *
     * @return \Stash\Pool
     */
    public function getCacher()
    {
        return $this->cacher;
    }

    /**
     * Возвращает экземпляр логгера
     *
     * @return \Monolog\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Производит конфигурирование сервиса
     *
     * @param array $config Опции конфигурации
     *
     * @throws ConfigurationErrorException
     */
    public function configure(array $config = [])
    {
        if (null !== $config) {
            $this->configureCacher($config);
            $this->configureLogger($config);
            $this->configurePinba($config);
        }

        $this->configured = true;
    }

    /**
     * Производит конфигурирование кэшера
     *
     * @param array $config Опции конфигурации
     */
    private function configureCacher(array $config = [])
    {
        if (!array_key_exists('cacher', $config)) {
            return;
        }

        $cacherConfig = $config['cacher'];

        if (is_object($cacherConfig)) {
            $this->cacher = $cacherConfig;
        }

        if (is_array($cacherConfig)) {
            if (array_key_exists('drivers', $cacherConfig)) {
                $drivers = [];
                foreach ($cacherConfig['drivers'] as $driverConfig) {
                    /** @var DriverInterface $driver */
                    $driver = new $driverConfig['class']();
                    if (array_key_exists('options', $driverConfig)) {
                        $driver->setOptions($driverConfig['options']);
                    }
                    $drivers[] = $driver;
                }
                $driver = new Composite();
                $driver->setOptions(['drivers' => $drivers]);
                $this->cacher->setDriver($driver);
            }
            if (array_key_exists('expires', $cacherConfig)
                && is_array($cacherConfig['expires'])
            ) {
                $this->cacherExpires = $cacherConfig['expires'];
            }
        }
    }

    /**
     * Производит конфигурирование логгера
     *
     * @param array $config Опции конфигурации
     */
    private function configureLogger(array $config = [])
    {
        if (!array_key_exists('logger', $config)) {
            return;
        }

        $loggerConfig = $config['logger'];

        if (is_object($loggerConfig)
            && $loggerConfig instanceof LoggerInterface
        ) {
            $this->logger = $loggerConfig;
        }

        if (is_array($loggerConfig)) {
            $this->logger = new Logger($loggerConfig['name']);
            if (array_key_exists('handlers', $loggerConfig)) {
                foreach ($loggerConfig['handlers'] as $handlerConfig) {
                    /** @var AbstractHandler $handler */
                    $handler = new $handlerConfig['class'];
                    if (array_key_exists('options', $handlerConfig)
                        && array_key_exists('level', $handlerConfig['options'])
                    ) {
                        $handler->setLevel($handlerConfig['options']['level']);
                    }
                    $this->logger->pushHandler($handler);
                }
            }
        }
    }

    /**
     * Производит конфигурирование pinba
     *
     * @param array $config Опции конфигурации
     *
     * @throws ConfigurationErrorException
     */
    private function configurePinba(array $config = [])
    {
        if (!array_key_exists('pinba', $config)) {
            return;
        }

        $this->pinba->configure($config);
    }

    /**
     * Вызывает соответствующий метод в конкретной реализации,
     * самостоятельно занимаясь кэшированием, логгированием
     * и измерением времени
     *
     * @param string $method Вызываемый метод
     * @param array  $args   Переданные в метод аргументы
     *
     * @return Response
     * @throws ConfigurationErrorException
     * @throws \InvalidArgumentException
     */
    public function __call($method, array $args = [])
    {
        $cacheKey = md5(serialize(func_get_args()));
        $tag = [
            '_' => substr($cacheKey, 0, 6),
            'call' => sprintf('%s->%s', get_class($this), $method)
        ];

        $args = $this->createArgs($args);

        $timer = $this->pinba->start(array_merge($tag, ['args' => $args]));

        $this->logger->info(
            sprintf('%s->%s', get_class($this), $method),
            array_merge($tag, $args)
        );

        $this->checkIsConfigured();

        $item = $this->cacher->getItem($cacheKey);

        $inCache = !$item->isMiss();
        $this->logger->info(sprintf(
            'item found in cache: %s', $inCache ? 'yes' : 'no'
        ), $tag);

        if ($inCache) {
            $this->logger->info('get from cache', $tag);
            $response = $item->get();
        } else {
            $this->logger->info('get from source', $tag);
            $response = $this->implementation($method, $args);

            $expiresDefault = array_key_exists('default', $this->cacherExpires)
                ? $this->cacherExpires['default']
                : 0;
            $expires = array_key_exists($method, $this->cacherExpires)
                ? $this->cacherExpires[$method]
                : $expiresDefault;
            $item->set($response, $expires);

            if (!$response->isOk()) {
                $this->logger->error('error response',
                    array_merge($tag, [$response->getError()])
                );
            } else {
                $this->logger->info('successful response', $tag);
            }
        }

        $this->pinba->stop($timer);
        $this->logger->info('pinba',
            array_merge($tag, [$this->pinba->info($timer)])
        );

        return $response;
    }

    /**
     * Конструирует массив аргументов
     *
     * @param array $args Массив аргументов
     *
     * @return array
     */
    protected function createArgs(array $args = [])
    {
        return $args;
    }

    /**
     * Проверяет, что сервис был корректно сконфигурирован
     *
     * @throws ConfigurationErrorException
     */
    private function checkIsConfigured()
    {
        if (!$this->isConfigured()) {
            $message = sprintf(self::ERR__NOT_CONFIGURED, get_class($this));
            $this->logger->emergency($message);

            throw new ConfigurationErrorException($message);
        }
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
     * Реализует конкретное взаимодействие с внешним источником
     *
     * @param string $method Вызываемый метод
     * @param array  $args   Аргументы вызываемого метода
     *
     * @return Response
     */
    abstract protected function implementation($method, array $args = []);
}
