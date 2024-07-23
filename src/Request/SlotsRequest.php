<?php
declare(strict_types=1);

namespace App\Request;

use Psr\Log\LoggerInterface;
use JsonException;
use Exception;

class SlotsRequest
{
    public function __construct(
        private Request $request,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return iterable<SlotDTO|false>
     */
    public function fetchDoctorSlots(int $doctorId): iterable
    {
        try {
            $slots = $this->getSlots($doctorId);
            yield from $this->parseSlots($slots, $doctorId);
        } catch (JsonException) {
            $this->logger->info('Error fetching slots for doctor', ['doctorId' => $doctorId]);
            yield false;
        }
    }

    /**
     * @throws JsonException
     */
    private function getSlots(int $id): array
    {
        return $this->request->fetchData('/' . $id . '/slots');
    }

    /**
     * @return iterable<SlotDTO>
     */
    protected function parseSlots(mixed $slots, int $doctorId): iterable
    {
        foreach ($slots as $slot) {
            yield SlotDTO::fromArray(
                $doctorId,
                $slot,
            );
        }
    }
}
