<?php
namespace App\Support;

class SocketIOParser extends Packet
{
    /**
     * Encode output payload for websocket push.
     *
     * @param string $event
     * @param mixed $data
     *
     * @return mixed
     */
    public static function encode(string $event, $data)
    {
        $packet = Packet::MESSAGE . Packet::EVENT;
        $shouldEncode = is_array($data) || is_object($data);
        $data = $shouldEncode ? json_encode($data) : $data;
        $format = $shouldEncode ? '["%s",%s]' : '["%s","%s"]';

        return $packet . sprintf($format, $event, $data);
    }

    /**
     * Decode message from websocket client.
     *
     * @param string $string
     *
     * @return array
     */
    public static function decode($string)
    {
        $payload = Packet::getPayload($string);

        return [
            'event' => $payload['event'] ?? null,
            'data' => $payload['data'] ?? null,
        ];
    }
}
