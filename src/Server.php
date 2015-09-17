<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

use Lexty\WebSocketServer\Exceptions\ConnectionException;

/**
 * @link https://tools.ietf.org/html/rfc6455 RFC6455
 */
class Server {
    const POWERED_BY = 'Lexty-WebSocketServer/0.1';

    private $host;
    private $port;
    private $pidFile;
    private $workers;
    private $applications = [];

    public function __construct($host, $port, $pidFile, $workers = 1) {
        if (false !== strpos($host, ':')) {
            $host = '[' . $host . ']';
        }
        $this->host = $host;
        $this->port = $port;
        $this->pidFile = $pidFile;
        $this->workers = $workers;
    }
    /**
     * @param string               $name
     * @param ApplicationInterface $application
     *
     * @return $this
     */
    public function registerApplication($name, ApplicationInterface $application) {
        $this->applications[trim($name, '/')][] = $application;
        return $this;
    }

    public function run() {
        //открываем серверный сокет
        $server = stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);

        if (false === $server) {
            throw new ConnectionException(sprintf('Could not bind to tcp://%s:%s: %s', $this->host, $this->port, $errstr), $errno);
        }

        list($pid, $master, $workers) = $this->spawnWorkers();//создаём дочерние процессы

        if ($pid) {//мастер
            fclose($server);//мастер не будет обрабатывать входящие соединения на основном сокете
            $WebSocketMaster = new Master($workers);//мастер будет пересылать сообщения между воркерами
            $WebSocketMaster->run();
        } else {//воркер
            $WebSocketHandler = new Worker($server, $this->applications);
            $WebSocketHandler->run();
        }
    }

    protected function spawnWorkers() {
        $master = null;
        $workers = array();
        for ($i = 0; $i < $this->workers; $i++) {
            //создаём парные сокеты, через них будут связываться мастер и воркер
            $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

            $pid = pcntl_fork();//создаём форк
            if (-1 === $pid) {
                throw new \RuntimeException('Could not fork process');
            } elseif ($pid) { //мастер
                fclose($pair[0]);
                $workers[$pid] = $pair[1];//один из пары будет в мастере
            } else { //воркер
                fclose($pair[1]);
                $master = $pair[0];//второй в воркере
                break;
            }
        }

        return [$pid, $master, $workers];
    }
}