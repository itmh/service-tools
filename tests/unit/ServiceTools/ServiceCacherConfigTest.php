<?php

use Codeception\TestCase\Test;
use ITMH\ServiceTools\Core\Service;
use Stash\Driver\Ephemeral;
use Stash\DriverList;
use Stash\Interfaces\PoolInterface;
use Stash\Pool;

class ServiceCacherConfigTest extends Test
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
                    'cacher' => [
                        'drivers' => [
                            [
                                'class' => DriverList::getDriverClass('FileSystem'),
                                'options' => [
                                    'path' => '/tmp/stash'
                                ]
                            ],
                            [
                                'class' => DriverList::getDriverClass('Ephemeral')
                            ]
                        ],
                        'expires' => [
                            'default' => 0
                        ]
                    ]
                ]
            ],
            'config with cacher instance' => [
                [
                    'cacher' => new Pool(new Ephemeral())
                ]
            ]
        ];
    }

    public function testCacherAvailable()
    {
        $cacher = $this->getMockService()->getCacher();
        self::assertTrue($cacher instanceof PoolInterface);
    }

}
