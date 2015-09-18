<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Http;

/**
 * Interface RequestInterface
 * @property string $method
 * @property string $url
 * @property string $scheme
 * @property string $host
 * @property string $port
 * @property string $path
 * @property string $queryString
 * @property string $fragment
 * @property Query  $query
 */
interface RequestInterface extends HttpMessageInterface
{
    /**
     * @return string
     */
    public function getMethod();

    /**
     * @return string
     */
    public function getUrl();

    /**
     * @return string
     */
    public function getScheme();

    /**
     * @return string
     */
    public function getHost();

    /**
     * @return string
     */
    public function getPort();

    /**
     * @return string
     */
    public function getPath();

    /**
     * @return string
     */
    public function getQueryString();

    /**
     * @return string
     */
    public function getFragment();

    /**
     * @return Query
     */
    public function getQuery();
}