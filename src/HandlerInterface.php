<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;


/**
 * Interface HandlerInterface
 *
 * @property int $pid
 * @property int $connectionsCount
 */
interface HandlerInterface
{
    /**
     * @return int
     */
    public function getPid();

    /**
     * @return int
     */
    public function getConnectionsCount();
}