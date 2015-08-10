<?php

namespace ITMH\ServiceTools\Services;

use ITMH\ServiceTools\Core\ConfigurationErrorException;
use ITMH\ServiceTools\Core\Response;
use ITMH\ServiceTools\Core\Service;
use ITMH\Soap\Client;
use ITMH\Soap\Exception\InvalidParameterException;
use SoapFault;

/**
 * Class SoapService
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
     * Массив моделей для автоматического отображения результата
     *
     * @var array
     */
    protected $mapping = [
        // нестрогое приведение результата к экземпляру класса (игнорирует несоответствие полей)
        'mapper' => [],
        // строгое приведение результата к экземпляру класса (бросает исключение при несоответствии полей)
        'strict' => [],
        // приведение результата к обычному массиву
        'array' => [],
        // приведение результата к обычному массиву, с сохранением корневого индекса
        'array_strict' => []
    ];

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

        if (array_key_exists('__mapper', $config)) {
            $this->mapping['mapper'] = $config['__mapper'];
        }
        if (array_key_exists('__mapper_strict', $config)) {
            $this->mapping['strict'] = $config['__mapper_strict'];
        }
        if (array_key_exists('__mapper_array', $config)) {
            $this->mapping['array'] = $config['__mapper_array'];
        }
        if (array_key_exists('__mapper_array_strict', $config)) {
            $this->mapping['array_strict'] = $config['__mapper_array_strict'];
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
            return Response::failure(null, $fault->getMessage());
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
        $classMap = null;
        $asClass = true;
        $asStrictArray = false;

        if (array_key_exists($method, $this->mapping['mapper'])) {
            $classMap = $this->mapping['mapper'];
        }
        if (array_key_exists($method, $this->mapping['strict'])) {
            $classMap = $this->mapping['strict'];
            $this->client->setStrictMapping(true);
        }
        if (array_key_exists($method, $this->mapping['array'])
            || array_key_exists('*', $this->mapping['array'])
        ) {
            $classMap = $this->mapping['array'];
            $asClass = false;
        }
        if (array_key_exists($method, $this->mapping['array_strict'])
            || array_key_exists('*', $this->mapping['array_strict'])
        ) {
            $classMap = $this->mapping['array_strict'];
            $asClass = false;
            $asStrictArray = true;
        }

        if (null === $classMap) {
            return $raw;
        }

        return $asClass === true
            ? $this->client->asClass($raw, $method, $classMap)
            : $this->client->asArray($raw, $asStrictArray);
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
