<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Applications;

use Lexty\WebSocketServer\BaseApplication;
use Lexty\WebSocketServer\Connection\ConnectionInterface;
use Lexty\WebSocketServer\Payload\PayloadInterface;
use Lexty\WebSocketServer\HandlerInterface;

class EchoServer extends BaseApplication
{
    public function onMessage(ConnectionInterface $from, PayloadInterface $msg, HandlerInterface $handler)
    {
        $from->send($msg);
    }
}