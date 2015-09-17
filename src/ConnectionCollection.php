<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

class ConnectionCollection {
    /**
     * @var ConnectionInterface[]
     */
    private $connections = [];

    /**
     * @var resource[]
     */
    private $resources = [];

    /**
     * @param ConnectionInterface[] $connections
     */
    public function __construct(array $connections) {
        $this->connections = $connections;

        foreach ($connections as $connection) {
            $this->resources = $connection->getResource();
        }
    }

    public function getResources() {
        return $this->getResources();
    }
}