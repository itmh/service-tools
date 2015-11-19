<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use Codeception\TestCase\Test;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ITMH\ServiceTools\Core\Service;

class ServiceLoggerConfigTest extends Test
{
    /**
     * @dataProvider providerCacheConfig
     *
     * @param $config
     */
    public function testCacheConfig($config)
    {
        $service = $this->getMockService();
        $service->configure($config);
        self::assertTrue($service->isConfigured());
    }

    /**
     * @return \ITMH\ServiceTools\Core\Service|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockService()
    {
        return $this->getMockForAbstractClass(Service::CLASS_NAME);
    }

    public function providerCacheConfig()
    {
        return [
            'config with default params' => [
                [
                    'logger' => [
                        'name' => 'service-tools-test',
                        'handlers' => [
                            [
                                'class' => 'Monolog\Handler\ErrorLogHandler',
                                'options' => [
                                    'level' => Logger::DEBUG
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'config with logger instance' => [
                [
                    'logger' => new Logger('service-tools-test', [
                        new ErrorLogHandler()
                    ])
                ]
            ]
        ];
    }

    public function testLoggerAvailable()
    {
        $logger = $this->getMockService()->getLogger();
        self::assertTrue($logger instanceof LoggerInterface);
    }
}
