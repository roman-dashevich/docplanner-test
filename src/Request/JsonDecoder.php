<?php

namespace App\Request;

use JsonException;

class JsonDecoder
{
    /**
     * @throws JsonException
     */
    public static function getJsonDecode(string|bool $json): mixed
    {
        return json_decode(
            json: false === $json ? '' : $json,
            associative: true,
            depth: 16,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}