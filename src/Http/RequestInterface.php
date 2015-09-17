<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Http;

interface RequestInterface extends HttpMessageInterface {
    /**
     * @return string
     */
    public function getMethod();
    /**
     * @return string
     */
    public function getPath();
}