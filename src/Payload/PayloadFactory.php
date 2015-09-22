<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Payload;

class PayloadFactory implements PayloadFactoryInterface
{
    /**
     * @var string
     */
    private $payloadClass;

    /**
     * @param string $payloadClass
     */
    public function __construct($payloadClass)
    {
        $this->payloadClass = $payloadClass;
    }

    /**
     * @param string $data
     *
     * @return PayloadInterface
     */
    public function create($data)
    {
        return new $this->payloadClass($data);
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public function encode($data)
    {
        return call_user_func([$this->payloadClass, 'encode'], $data);
    }
}