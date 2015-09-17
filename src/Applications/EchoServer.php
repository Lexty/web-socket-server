<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Applications;

use Lexty\WebSocketServer\AbstractApplication;
use Lexty\WebSocketServer\ConnectionInterface;
use Lexty\WebSocketServer\Payload\PayloadInterface;
use Lexty\WebSocketServer\WorkerInterface;

class EchoServer extends AbstractApplication {
    public function onMessage(ConnectionInterface $from, PayloadInterface $msg, WorkerInterface $worker) {
        $from->send($msg);
    }
}