<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;


class Master {
    protected $workers = [];
    protected $clients = [];

    public function __construct($workers) {
        $this->clients = $this->workers = $workers;
    }

    public function run() {
        while (true) {
            //подготавливаем массив всех сокетов, которые нужно обработать
            $read = $this->clients;

            stream_select($read, $write, $except, null);//обновляем массив сокетов, которые можно обработать

            if ($read) {//пришли данные от подключенных клиентов
                foreach ($read as $client) {
                    if (!is_resource($client)) exit();
                    $data = fread($client, 1000);

                    if (!strlen($data)) { //соединение было закрыто
                        unset($this->clients[intval($client)]);
                        @fclose($client);
                        continue;
                    }

                    foreach ($this->workers as $worker) {//пересылаем данные во все воркеры
                        if ($worker !== $client) {
                            fwrite($worker, $data);
                        }
                    }
                }
            }
        }
    }
}