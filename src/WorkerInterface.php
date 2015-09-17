<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;


interface WorkerInterface {
    /**
     * @return int
     */
    public function getPid();

    /**
     * @return int
     */
    public function getConnectionsCount();
}