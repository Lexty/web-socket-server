<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Payload;

class PayloadFactory implements PayloadFactoryInterface
{
    /**
     * @param string $data
     *
     * @return PayloadInterface
     */
    public function create($data) {
        return new Payload((string) $data);
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public function encode($data) {
        return Payload::encode((string) $data);
    }
}