<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

use Lexty\WebSocketServer\Exceptions\ConnectionException;

/**
 * @link https://tools.ietf.org/html/rfc6455 RFC6455
 */
class Server
{
    const POWERED_BY = 'Lexty-WebSocketServer/0.0.2';

    private $host;
    private $port;
    private $pidFile;
    private $handlers;
    private $applications = [];
    private $connectionClass = 'Connection';

    public function __construct($host, $port, $pidFile, $handlers = 1)
    {
        if (false !== strpos($host, ':')) {
            $host = '[' . $host . ']';
        }
        $this->host     = $host;
        $this->port     = $port;
        $this->pidFile  = $pidFile;
        $this->handlers = $handlers;
    }

    /**
     * @param string               $name
     * @param ApplicationInterface $application
     *
     * @return $this
     */
    public function registerApplication($name, ApplicationInterface $application)
    {
        $this->applications[trim($name, '/')][] = $application;
        return $this;
    }

    /**
     * Sets the connection class name.
     *
     * Must implements ConnectionInterface.
     *
     * @param string $class
     */
    public function setConnectionClass($class)
    {
        $this->connectionClass = $class;
    }

    public function run()
    {
        // open server socket
        $server = stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);

        if (false === $server) {
            throw new ConnectionException(sprintf('Could not bind to tcp://%s:%s: %s', $this->host, $this->port, $errstr), $errno);
        }

        list($pid, $master, $handlers) = $this->spawnHandlers();//создаём дочерние процессы

        if ($pid) { // master
            fclose($server);                         // master will not process incoming connections on the main socket
            $WebSocketMaster = new Master($handlers); // he will forward messages between worker`s
            $WebSocketMaster->run();
        } else { // worker
            $WebSocketHandler = new Handler($server, $this->applications, $this->connectionClass);
            $WebSocketHandler->run();
        }
    }

    protected function spawnHandlers()
    {
        $pid      = $master = null;
        $handlers = [];
        for ($i = 0; $i < $this->handlers; $i++) {
            // creating twin sockets, they will be contacted by the master and the worker is
            $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

            $pid = pcntl_fork(); // fork
            if (-1 === $pid) {
                throw new \RuntimeException('Could not fork process');
            } elseif ($pid) { // master
                fclose($pair[0]);
                $handlers[$pid] = $pair[1]; // one of the pair is in the master
            } else { // worker
                fclose($pair[1]);
                $master = $pair[0];         // the second is in the worker
                break;
            }
        }

        return [$pid, $master, $handlers];
    }
}