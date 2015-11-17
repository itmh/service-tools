<?php
namespace ServiceTools;

use Codeception\TestCase\Test;
use ITMH\ServiceTools\Core\ConfigurationErrorException;
use ITMH\ServiceTools\Core\Response;
use ITMH\ServiceTools\Services\SoapService;

class SoapServiceTest extends Test
{
    public function testWhenNoWsdlThenThrowException()
    {
        $class = get_class(new ConfigurationErrorException());
        $this->setExpectedException($class);

        $service = new SoapService();
        $service->configure();
    }

    public function testWhenConfiguredThenOk()
    {
        $service = new SoapService();
        $service->configure(array(
            'url' => 'http://www.webservicex.net/StockQuote.asmx?WSDL',
            'soapOptions' => array(),
            'contentType' => 'text/xml; charset=utf-8',
            'curlOptions' => array()
        ));

        self::assertTrue($service->isConfigured());

        /** @var Response $response */
        /** @noinspection PhpUndefinedMethodInspection */
        $response = $service->GetQuote();
        self::assertTrue($response->isOk());

        /** @var Response $response */
        /** @noinspection PhpUndefinedMethodInspection */
        $response = $service->ThereIsNoMethod(['Arg' => 'Value']);
        self::assertFalse($response->isOk());
    }
}

