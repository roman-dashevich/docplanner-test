<?php
declare(strict_types=1);

namespace App;

use App\Repository\DoctorsRepository;
use App\Repository\SlotsRepository;
use App\Request\DoctorsRequest;
use App\Request\SlotsRequest;

class DoctorSlotsSynchronizer
{
    public function __construct(
        private DoctorsRepository $doctorsRepository,
        private SlotsRepository $slotsRepository,
        private DoctorsRequest $doctorsRequest,
        private SlotsRequest $slotsRequest
    ) {
    }

    public function synchronizeDoctorSlots(): void
    {
        $doctorDTOs = $this->doctorsRequest->getDoctors();

        foreach ($doctorDTOs as $doctorDTO) {
            $doctor = $this->doctorsRepository->findOrCreate($doctorDTO);

            foreach ($this->slotsRequest->fetchDoctorSlots($doctorDTO->id()) as $slotDTO) {
                if (false === $slotDTO) {
                    $this->doctorsRepository->markError($doctor);
                } else {
                    $this->slotsRepository->findOrCreate($slotDTO);
                }
            }
        }
    }
}
