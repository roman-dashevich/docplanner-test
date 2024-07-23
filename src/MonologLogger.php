<?php

namespace App;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use DateTime;

class MonologLogger extends Logger
{
    public function __construct(string $logFile = 'php://stderr')
    {
        parent::__construct('logger', [new StreamHandler($logFile)]);
    }

    public function info($message, array $context = []): void
    {
        if (!$this->shouldReportErrors()) {
            return;
        }

        parent::info($message, $context);
    }

    private function shouldReportErrors(): bool
    {
        return (new DateTime())->format('D') !== 'Sun';
    }
}
