<?php

namespace ServiceTools\Core;

use ErrorException;

/**
 * Class Response
 * @package ServiceTools\Core
 */
class Response
{
    const ERR__BODY_NOT_SERIALIZABLE = 'Response body is not serializable';

    /**
     * Флаг успешности запроса
     *
     * @var bool
     */
    private $isSuccess = false;

    /**
     * Содержимое ответа
     *
     * @var mixed
     */
    private $content;

    /**
     * Строка с описанием ошибки
     *
     * @var string
     */
    private $error;

    /**
     * Конструктор экземпляра, используется только во вспомогательных методах
     *
     * @param bool   $isSuccess Флаг успешности
     * @param mixed  $body      Содержимое ответа ответа
     * @param string $error     Строка с описанием ошибки
     *
     * @throws ErrorException
     */
    private function __construct($isSuccess, $body, $error = null)
    {
        if (!$this->isSerializable($body)) {
            throw new ErrorException(self::ERR__BODY_NOT_SERIALIZABLE);
        }
        $this->isSuccess = $isSuccess;
        $this->content = $body;
        $this->error = $error;
    }

    /**
     * Проверка на возможность сериализации
     * содержимого ответа для сохранения в кэше
     *
     * @param mixed $body Содержимое ответа
     *
     * @return bool
     */
    private function isSerializable($body)
    {
        $isSerializable = true;
        $array = [$body];
        array_walk_recursive($array, function ($e) use (&$isSerializable) {
            if (is_object($e) && get_class($e) === 'Closure') {
                $isSerializable = false;
            }
        });

        return $isSerializable;
    }

    /**
     * Возвращает успешный ответ
     *
     * @param mixed $body Содержимое ответа
     *
     * @return Response
     */
    public static function success($body = null)
    {
        return new self(true, $body);
    }

    /**
     * Возвращает неуспешный результат
     *
     * @param mixed  $body  Содержимое ответа
     * @param string $error Строка с описанием ошибки
     *
     * @return Response
     */
    public static function failure($body = null, $error = null)
    {
        return new self(false, $body, $error);
    }

    /**
     * Возвращает флаг успешности
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->isSuccess;
    }

    /**
     * Возвращает содержимое ответа
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Возвращает строку с описанием ошибки
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}
