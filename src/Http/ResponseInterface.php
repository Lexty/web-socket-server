<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Http;

interface ResponseInterface extends HttpMessageInterface {
    /**
     * @return int
     */
    public function getStatusCode();

    /**
     * @return string
     */
    public function getStatusMessage();
}