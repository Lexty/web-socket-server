<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Events;

use Lexty\WebSocketServer\Connection\ConnectionInterface;
use Lexty\WebSocketServer\HandlerInterface;
use Lexty\WebSocketServer\Payload\PayloadInterface;

/**
 * @property PayloadInterface $payload
 */
class MessageEvent extends ConnectionEvent
{
    /**
     * @var PayloadInterface
     */
    private $payload;

    /**
     * {@inheritdoc}
     * @param PayloadInterface $payload
     */
    public function __construct(ConnectionInterface $conn, PayloadInterface $payload, HandlerInterface $handler) {
        parent::__construct($conn, $handler);
        $this->payload = $payload;
    }

    /**
     * @return PayloadInterface
     */
    public function getPayload()
    {
        return $this->payload;
    }
}