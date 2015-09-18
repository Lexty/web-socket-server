<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;


class Master
{
    protected $handlers = [];
    protected $clients  = [];

    public function __construct($handlers)
    {
        $this->clients = $this->handlers = $handlers;
    }

    public function run()
    {
        while (true) {
            // preparing an array of sockets that need to be processed
            $read = $this->clients;

            stream_select($read, $write, $except, null); // update an array of sockets that can be processed

            if ($read) { // data came from the connected clients
                foreach ($read as $client) {
                    if (!is_resource($client)) exit();
                    $data = fread($client, 1000);

                    if (!strlen($data)) { // connection has been closed
                        unset($this->clients[intval($client)]);
                        @fclose($client);
                        continue;
                    }

                    foreach ($this->handlers as $handler) { // forwards the data to all worker`s
                        if ($handler !== $client) {
                            fwrite($handler, $data);
                        }
                    }
                }
            }
        }
    }
}