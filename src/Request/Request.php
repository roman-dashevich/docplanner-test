<?php
declare(strict_types=1);

namespace App\Request;

use JsonException;

class Request
{
    public function __construct(
        private string $endpoint,
        private string $username,
        private string $password,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function fetchData(string $uri = ''): array
    {
        $url = $this->endpoint . $uri;
        $auth = base64_encode(
            sprintf(
                '%s:%s',
                $this->username,
                $this->password,
            ),
        );

        return JsonDecoder::getJsonDecode(
            @file_get_contents(
                filename: $url,
                context: stream_context_create(
                    [
                        'http' => [
                            'header' => 'Authorization: Basic ' . $auth,
                        ],
                    ],
                ),
            )
        );
    }
}
