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
     * @param PayloadFactoryInterface $payloadFactory
     */
    public function __construct(PayloadFactoryInterface $payloadFactory)
    {
        $this->payloadFactory = $payloadFactory;
    }
    /**
     * @param resource $streamResource
     *
     * @return ConnectionInterface
     */
    public function create($streamResource)
    {
        return new Connection($streamResource, $this->payloadFactory);
    }
}