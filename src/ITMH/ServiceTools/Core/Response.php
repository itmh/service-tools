<?php

namespace ITMH\ServiceTools\Core;

use ErrorException;

/**
 * Class Response
 *
 * @package ITMH\ServiceTools\Core
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
    private $errorMessage;

    /**
     * Код ошибки в текстовом представлении
     *
     * @var string
     */
    private $errorCode;

    /**
     * Конструктор экземпляра, используется только во вспомогательных методах
     *
     * @param bool   $isSuccess    Флаг успешности
     * @param mixed  $body         Содержимое ответа ответа
     * @param string $errorMessage Строка с описанием ошибки
     * @param string $errorCode    Код ошибки в текстовом представлении
     *
     * @throws ErrorException
     */
    private function __construct($isSuccess, $body, $errorMessage = null, $errorCode = null)
    {
        if (!$this->isSerializable($body)) {
            throw new ErrorException(self::ERR__BODY_NOT_SERIALIZABLE);
        }

        $this->isSuccess = $isSuccess;
        $this->content = $body;
        $this->errorMessage = $errorMessage;
        $this->errorCode = $errorCode;
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
        array_walk_recursive(
            $array,
            function ($e) use (&$isSerializable) {
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
     * @throws ErrorException
     */
    public static function success($body = null)
    {
        return new self(true, $body);
    }

    /**
     * Возвращает неуспешный результат
     *
     * @param mixed  $body         Содержимое ответа
     * @param string $errorMessage Строка с описанием ошибки
     * @param string $errorCode    Код ошибки в текстовом представлении
     *
     * @return Response
     * @throws ErrorException
     */
    public static function failure($body = null, $errorMessage = null, $errorCode = null)
    {
        return new self(false, $body, $errorMessage, $errorCode);
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
        return $this->errorMessage;
    }

    /**
     * Возвращает faultcode
     *
     * @return mixed
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }


}
