<?php
declare(strict_types=1);

namespace App\Request;

class DoctorDTO
{
    private function __construct(
        private int $id,
        private string $name,
    ) {
    }

    public static function fromArray(array $data): self
    {
        /**
         * Possible to add some validation to avoid exception
         */
        return new self(
            (int) $data['id'],
            self::normalizeName($data['name']),
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }


    private static function normalizeName(string $fullName): string
    {
        [, $surname] = explode(' ', $fullName);

        /** @see https://www.youtube.com/watch?v=PUhU3qCf0Nk */
        if (0 === stripos($surname, "o'")) {
            return ucwords($fullName, ' \'');
        }

        return ucwords($fullName);
    }
}