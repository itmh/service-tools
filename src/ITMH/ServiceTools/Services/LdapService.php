<?php

namespace ITMH\ServiceTools\Services;

use ErrorException;
use ITMH\ServiceTools\Core\ConfigurationErrorException;
use ITMH\ServiceTools\Core\Response;
use ITMH\ServiceTools\Core\Service;

/**
 * Class LdapService
 * @package ITMH\ServiceTools\Services
 */
class LdapService extends Service
{
    const ERR__HOST_REQUIRED = 'LDAP host is not specified';
    const ERR__LOGIN_REQUIRED = 'LDAP login is not specified';
    const ERR__PASSWORD_REQUIRED = 'LDAP password is not specified';
    const ERR__AD_CONNECT = 'Could not connect to Active Directory';
    const ERR__AD_BIND = 'Could not bind to Active Directory';

    private $port;
    private $readOnly = false;

    /**
     * @var resource
     */
    private $client;

    /**
     * Производит конфигурирование сервиса
     *
     * @param array $config Опции конфигурации
     *
     * @throws ConfigurationErrorException
     * @throws ErrorException
     */
    public function configure(array $config = [])
    {
        if (!array_key_exists('host', $config)) {
            throw new ConfigurationErrorException(self::ERR__HOST_REQUIRED);
        }
        if (array_key_exists('port', $config) && is_numeric($config['port'])) {
            $this->port = $config['port'];
        }

        if (array_key_exists('readOnly', $config)) {
            $this->readOnly = filter_var(
                $config['readOnly'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if (!$this->readOnly) {
            if (!array_key_exists('login', $config)) {
                throw new ConfigurationErrorException(self::ERR__LOGIN_REQUIRED);
            }
            if (!array_key_exists('password', $config)) {
                throw new ConfigurationErrorException(self::ERR__PASSWORD_REQUIRED);
            }
        }

        $this->client = ldap_connect($config['host'], $this->port);

        if (!$this->client) {
            throw new ErrorException(self::ERR__AD_CONNECT);
        }

        if ($this->readOnly) {
            $bind = ldap_bind($this->client);
        } else {
            $bind = ldap_bind(
                $this->client,
                $config['login'],
                $config['password']
            );
        }

        if (!$bind) {
            $this->client = false;
            throw new ErrorException(self::ERR__AD_BIND);
        }

        parent::configure($config);
    }

    /**
     * Реализует конкретное взаимодействие с внешним источником
     *
     * @param string $method Вызываемый метод
     * @param array  $args   Аргументы вызываемого метода
     *
     * @return Response
     */
    protected function implementation($method, array $args = array())
    {
        array_unshift($args, $this->client);
        try {
            $raw = call_user_func_array($method, $args);

            return Response::success($raw);
        } catch (\Exception $e) {
            return Response::failure(null, $e->getMessage());
        }
    }
}
