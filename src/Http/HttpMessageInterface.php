<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Http;

interface HttpMessageInterface
{
    /**
     * @return string
     */
    public function getProtocol();

    /**
     * @return HeadersCollectionInterface
     */
    public function getHeaders();

    /**
     * @return string
     */
    public function getBody();

    /**
     * @return string
     */
    public function __toString();
}