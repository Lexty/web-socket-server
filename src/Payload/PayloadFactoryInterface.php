<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Payload;

interface PayloadFactoryInterface
{
    /**
     * @param string $data
     *
     * @return PayloadInterface
     */
    public function create($data);

    /**
     * @param string $data
     *
     * @return string
     */
    public function encode($data);
}