<?php

namespace ITMH\ServiceTools\Services;

use ITMH\ServiceTools\Core\ConfigurationErrorException;
use ITMH\ServiceTools\Core\Response;
use ITMH\ServiceTools\Core\Service;
use ITMH\Soap\Client;
use ITMH\Soap\Mapper;
use ITMH\Soap\Exception\InvalidParameterException;
use SoapFault;

/**
 * Class SoapService
 *
 * @package ServiceTools\Services
 */
class SoapService extends Service
{
    const ERR__URL_REQUIRED = 'Soap wsdl url is not specified';
    const ERR__DEFAULT_ARGS_ARRAY = 'Default args must be an array';

    /**
     * Экземпляр клиента
     *
     * @var \ITMH\Soap\Client
     */
    protected $client;

    /**
     * Экземпляр клиента
     *
     * @var \ITMH\Soap\Mapper
     */
    protected $mapper;

    /**
     * Аргументы по умолчанию, которые должны добавляться к каждому запросу
     *
     * @var array
     */
    protected $defaultArgs;

    /**
     * Производит конфигурирование сервиса
     *
     * @param array $config Опции конфигурации
     *
     * @throws ConfigurationErrorException
     */
    public function configure(array $config = [])
    {
        if (!array_key_exists('url', $config)) {
            throw new ConfigurationErrorException(self::ERR__URL_REQUIRED);
        }

        $soapOptions = [];
        if (array_key_exists('soapOptions', $config)) {
            $soapOptions = $config['soapOptions'];
        }

        $this->client = new Client($config['url'], $soapOptions);

        if (array_key_exists('contentType', $config)) {
            $this->client->setContentType($config['contentType']);
        }

        if (array_key_exists('curlOptions', $config)) {
            $this->client->setCurlOptions($config['curlOptions']);
        }

        if (array_key_exists('defaultArgs', $config)) {
            if (!is_array($config['defaultArgs'])) {
                throw new ConfigurationErrorException(
                    self::ERR__DEFAULT_ARGS_ARRAY
                );
            }
            $this->defaultArgs = $config['defaultArgs'];
        }

        $this->mapper = new Mapper();
        if (array_key_exists('mapper', $config)) {
            $this->mapper->setConfig($config['mapper']);
        }

        if (!array_key_exists('pinba', $config)) {
            $config['pinba'] = [
                'type' => 'soap',
                'target' => $config['url']
            ];
        }

        parent::configure($config);
    }

    /**
     * Реализует взаимодействие с внешним сервисом
     *
     * @param string $method Вызываемый метод
     * @param array  $args   Аргументы вызываемого метода
     *
     * @return Response
     * @throws InvalidParameterException
     */
    protected function implementation($method, array $args = array())
    {
        try {
            $raw = call_user_func_array(array($this->client, $method), $args);
            $raw = $this->map($method, $raw);

            return Response::success($raw);
        } catch (SoapFault $fault) {
            return Response::failure(null, $fault->getMessage(), $fault->faultcode);
        }
    }

    /**
     * @param string $method Вызываемый метод
     * @param object $raw
     *
     * @return object
     * @throws InvalidParameterException
     */
    protected function map($method, $raw)
    {
        return $this->mapper->mapMethodResponse($method, $raw);
    }

    /**
     * Дополняет массив аргументов аргументами по умолчанию для каждого запроса
     *
     * @param array $args Массив аргументов
     *
     * @return array
     */
    protected function createArgs(array $args = [])
    {
        $args = parent::createArgs($args);
        if (null !== $this->defaultArgs) {
            if (array_key_exists(0, $args)) {
                $args = $args[0];
            }
            $args = array_merge($this->defaultArgs, $args);
        }

        return $args;
    }
}
