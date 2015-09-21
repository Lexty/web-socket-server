<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Connection;

use Lexty\WebSocketServer\Payload\PayloadFactoryInterface;

class ConnectionFactory
{
    /**
     * @var PayloadFactoryInterface
     */
    private $payloadFactory;
    /**
     * @var string
     */
    private $connectionClass;

    /**
     * @param PayloadFactoryInterface $payloadFactory
     * @param string                  $connectionClass
     */
    public function __construct(PayloadFactoryInterface $payloadFactory, $connectionClass)
    {
        $this->payloadFactory = $payloadFactory;
        $this->connectionClass = $connectionClass;
    }

    /**
     * @param resource $streamResource
     *
     * @return ConnectionInterface
     */
    public function create($streamResource)
    {
        return new $this->connectionClass($streamResource, $this->payloadFactory);
    }
}