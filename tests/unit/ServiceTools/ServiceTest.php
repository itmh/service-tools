<?php

use Codeception\Specify;
use Codeception\TestCase\Test;
use ServiceTools\Core\ConfigurationErrorException;
use ServiceTools\Core\Response;
use ServiceTools\Core\Service;
use ServiceTools\Helpers\Pinba;

class CoreTest extends Test
{
    public function testWhenNotConfiguredThenThrowException()
    {
        $service = $this->getMockService();

        $exception = get_class(new ConfigurationErrorException());
        $this->setExpectedException($exception);

        /** @noinspection ImplicitMagicMethodCallInspection */
        $service->__call('foo', array('bar', 'baz'));
    }

    /**
     * @return \ServiceTools\Core\Service|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockService()
    {
        return $this->getMockForAbstractClass(Service::CLASS_NAME);
    }

    public function testWhenConfiguredThenReturnResponse()
    {
        $service = $this->getMockService();
        $response = Response::success(true);

        /** @noinspection PhpUndefinedMethodInspection */
        $service
            ->method('implementation')
            ->will(self::returnValue($response));
        $service->configure();

        /** @noinspection ImplicitMagicMethodCallInspection */
        $response = $service->__call('foo', array('bar', 'baz'));

        self::assertTrue($response instanceof Response);
    }

    public function testWhenErrorResponse()
    {
        $service = $this->getMockService();
        $response = Response::failure('error');

        /** @noinspection PhpUndefinedMethodInspection */
        $service
            ->method('implementation')
            ->will(self::returnValue($response));
        $service->configure();

        /** @noinspection ImplicitMagicMethodCallInspection */
        $response = $service->__call('foo', array('bar', 'baz'));

        self::assertFalse($response->isOk());
    }

    public function testResponseSuccessHelper()
    {
        $successResponseBody = 'success';
        $successResponse = Response::success($successResponseBody);
        self::assertTrue($successResponse->isOk());
        self::assertNotNull($successResponse->getContent());
    }

    public function testResponseFailureHelper()
    {
        $failureResponseError = 'failure';
        $failureResponse = Response::failure(null, $failureResponseError);
        self::assertFalse($failureResponse->isOk());
        self::assertNotNull($failureResponse->getError());
    }

    public function testResponseCheckSerialization()
    {
        $this->setExpectedException('\ErrorException');
        $body = function () {
            return 'foo';
        };
        Response::success($body);
    }

    public function testPinba()
    {
        /** @var Pinba|PHPUnit_Framework_MockObject_Builder_InvocationMocker $pinba */
        $pinba = $this->getMockBuilder(get_class(new Pinba()))
            ->setMethods(array('isEnabled'))
            ->getMock();
        $pinba
            ->method('isEnabled')
            ->willReturn(false);

        $timer = $pinba->start('foo', 'bar');
        self::assertNull($timer);

        $success = $pinba->stop($timer);
        self::assertTrue($success);

        $info = $pinba->info($timer);
        self::assertTrue(is_array($info));
    }

    public function testWhenDataInCacheThenReturnIt()
    {
        $service = $this->getMockService();
        $responseCached = Response::success('from cache');

        $method = 'foo';
        $args = array('bar', 'baz');
        $key = md5(serialize(array(
            $method,
            $args
        )));
        $service->getCacher()->getItem($key)->set($responseCached, 60);

        /** @noinspection PhpUndefinedMethodInspection */
        $service
            ->method('implementation')
            ->will(self::returnValue($responseCached));
        $service->configure();

        /** @noinspection ImplicitMagicMethodCallInspection */
        $responseCalled = $service->__call($method, $args);

        self::assertEquals($responseCached, $responseCalled);
    }
}
