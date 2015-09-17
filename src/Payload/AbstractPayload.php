<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Payload;

abstract class AbstractPayload implements PayloadInterface {
    /**
     * @var string
     */
    protected $type;
    /**
     * @var int
     */
    protected $length;
    /**
     * @var string
     */
    protected $message;
    /**
     * @var string
     */
    protected $error;

    /**
     * {@inheritdoc}
     */
    public function __construct($data) {
        $decoded = $this->decode($data);
        if (is_array($decoded)) {
            $this->type = $decoded['type'];
            $this->length = $decoded['length'];
            $this->message = $decoded['payload'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getType() {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getLength() {
        return $this->length;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getError() {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString() {
        return $this->getMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function checkEncoding($encoding = null) {
        return mb_check_encoding($this->getMessage(), $encoding);
    }

    public static function encode($payload, $type = PayloadInterface::TYPE_TEXT, $masked = false) {
        $frameHead = array();
        $payloadLength = strlen($payload);

        switch ($type) {
            case PayloadInterface::TYPE_TEXT:
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;

            case PayloadInterface::TYPE_CLOSE:
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;

            case PayloadInterface::TYPE_PING:
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;

            case PayloadInterface::TYPE_PONG:
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }

        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0
            if ($frameHead[2] > 127) {
                throw new \RuntimeException('Frame too large', 1004);
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }

        // convert frame-head to string:
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        $mask = [];
        if ($masked === true) {
            // generate a random mask:
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }

            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);

        // append payload to frame:
        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }

    public static function decode($data) {
        $unmaskedPayload = '';
        $decodedData     = [];

        // estimate frame type:
        $firstByteBinary  = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode           = bindec(substr($firstByteBinary, 4, 4));
        $isMasked         = $secondByteBinary[0] == '1';
        $payloadLength    = ord($data[1]) & 127;

        // unmasked frame is received:
        if (!$isMasked) {
            throw new \RuntimeException('Protocol error', 1002);
        }

        switch ($opcode) {
            // text frame:
            case 1:
                $decodedData['type'] = PayloadInterface::TYPE_TEXT;
                break;
            case 2:
                $decodedData['type'] = PayloadInterface::TYPE_BINARY;
                break;
            // connection close frame:
            case 8:
                $decodedData['type'] = PayloadInterface::TYPE_CLOSE;
                break;
            // ping frame:
            case 9:
                $decodedData['type'] = PayloadInterface::TYPE_PING;
                break;
            // pong frame:
            case 10:
                $decodedData['type'] = PayloadInterface::TYPE_PONG;
                break;
            default:
                throw new \RuntimeException('Unknown opcode', 1003);
        }

        if ($payloadLength === 126) {
            $mask = substr($data, 4, 4);
            $payloadOffset = 8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === 127) {
            $mask = substr($data, 10, 4);
            $payloadOffset = 14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        } else {
            $mask = substr($data, 2, 4);
            $payloadOffset = 6;
            $dataLength = $payloadLength + $payloadOffset;
        }

        /**
         * We have to check for large frames here. socket_recv cuts at 1024 bytes
         * so if websocket-frame is > 1024 bytes we have to wait until whole
         * data is transferd.
         */
        if (strlen($data) < $dataLength) {
            return false;
        }

        if ($isMasked) {
            for ($i = $payloadOffset; $i < $dataLength; $i++) {
                $j = $i - $payloadOffset;
                if (isset($data[$i])) {
                    $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
                }
            }

            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset = $payloadOffset - 4;

            $decodedData['payload'] = substr($data, $payloadOffset);
        }

        $decodedData['length'] = $dataLength;

        return $decodedData;
    }
}