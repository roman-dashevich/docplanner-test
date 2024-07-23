<?php

namespace App\Request;

use DateTimeImmutable;
use Exception;

class SlotDTO
{
    private function __construct(
        private int $doctorId,
        private DateTimeImmutable $start,
        private DateTimeImmutable $end,
    ) {
    }

    public static function fromArray(int $doctorId, array $data): self
    {
        /**
         * Possible to add some validation to avoid exceptions
         */
        return new self(
            $doctorId,
            new DateTimeImmutable($data['start']),
            new DateTimeImmutable($data['end'])
        );
    }

    public function doctorId(): int
    {
        return $this->doctorId;
    }

    public function start(): DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): DateTimeImmutable
    {
        return $this->end;
    }
}