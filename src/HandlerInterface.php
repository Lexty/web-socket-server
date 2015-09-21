<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Interface HandlerInterface
 *
 * @property int                      $pid
 * @property int                      $connectionsCount
 * @property EventDispatcherInterface $dispatcher
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

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher();

    /**
     * It should allow to run only once.
     *
     * @return void
     */
    public function run();
}