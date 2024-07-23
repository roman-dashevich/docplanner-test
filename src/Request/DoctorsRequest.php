<?php
declare(strict_types=1);

namespace App\Request;

use JsonException;

class DoctorsRequest
{
    public function __construct(private Request $request)
    {
    }

    /**
     * return DoctorDTO[]
     *
     * @throws JsonException
     */
    public function getDoctors(): array
    {
        return array_map(
            fn (array $doctor) => DoctorDTO::fromArray($doctor),
            $this->request->fetchData()
        );
    }
}