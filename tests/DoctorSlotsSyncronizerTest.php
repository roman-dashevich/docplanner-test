<?php
declare(strict_types=1);

use App\DoctorSlotsSynchronizer;
use App\Entity\Doctor;
use App\Entity\Slot;
use App\Repository\DoctorsRepository;
use App\Repository\SlotsRepository;
use App\Request\JsonDecoder;
use App\Request\Request;
use App\Request\DoctorsRequest;
use App\Request\SlotsRequest;
use App\Request\SlotDTO;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class DoctorSlotsSyncronizerTest extends TestCase
{
    private DateTimeImmutable $now;
    /** @var Doctor[] */
    private array $doctors = [];
    /** @var Slot[] */
    private array $slots = [];

    /**
     * This test covers following cases of business logic
     * - Create new doctor
     * - Update existing doctor
     * - Create new slot for doctor
     * - Update existing slot for doctor if it is stale
     * - Add an error to doctor entity if slot configuration is unreadable
     *
     * All test cases are written with JSON configuration files,
     *  this file contains only necessary infrastructure.
     * JSON configs are easy to maintain and edit.
     *
     * Out of scope
     * - JSON data sources and possible format errors
     */
    public function testDoctorSlotsSyncronizer(): void
    {
        /**
         * Creating a fixed moment of time as 'NOW' to avoid possible errors.
         * All dates inside test will be relative to this moment.
         */
        $this->now = new DateTimeImmutable();

        /**
         * DoctorRepository
         * Entity manager and entity repository are stubbed and working as local storage
         */
        $repositoryStubDoctors = $this->createMock(EntityRepository::class);
        $repositoryStubDoctors->method('find')->willReturnCallback(
            function (int $doctorId): ?Doctor {
                return $this->doctors[$doctorId] ?? null;
            }
        );

        $emStubDoctors = $this->createStub(EntityManagerInterface::class);
        $emStubDoctors->method('getRepository')->willReturn($repositoryStubDoctors);
        $emStubDoctors->method('persist')->willReturnCallback(
            function (Doctor $doctor): void {
                $this->doctors[$doctor->getId()] = $doctor;
            }
        );

        $doctorsRepository = new DoctorsRepository($emStubDoctors);

        /**
         * Slots repository
         * Entity manager and entity repository are stubbed and working as local storage
         */
        $repositoryStubSlots = $this->createMock(EntityRepository::class);
        $repositoryStubSlots->method('findOneBy')->willReturnCallback(
            function (array $params): ?Slot {
                return $this->slots[
                    $params['doctorId'] . '-' .
                    $params['start']->format(DATE_ATOM)
                ] ?? null;
            }
        );

        $emStubSlots = $this->createStub(EntityManagerInterface::class);
        $emStubSlots->method('getRepository')->willReturn($repositoryStubSlots);
        $emStubSlots->method('persist')->willReturnCallback(
            function (Slot $slot): void {
                $this->slots[$slot->getDoctorId() . '-' . $slot->getStart()->format(DATE_ATOM)] = $slot;
            }
        );

        $slotsRepository = new SlotsRepository($emStubSlots);

        /**
         * Slots Fixtures
         * To ensure that there will be some stale slots and some not stale.
         * For testing purposes the int numbers are used for 'start' and 'end' properties,
         *  which are converted into same amount of minutes from 'NOW'.
         * 'stale' parameter affecting fixture createdAt param.
         */
        $slotsFixture = JsonDecoder::getJsonDecode(file_get_contents(__DIR__ . '/slots_fixture.json'));
        $slotsFixture = $this->convertTime($slotsFixture);
        foreach ($slotsFixture as $fixture) {
            $slotsRepository->findOrCreate(SlotDTO::fromArray(
                $fixture['doctorId'],
                [
                    'start' => $fixture['start'],
                    'end' => $fixture['end'],
                ]
            ));
            if ($fixture['stale']) {
                $slot = $this->slots[$fixture['doctorId'] . '-' . $fixture['start']];
                $reflection = new ReflectionClass($slot);
                $property = $reflection->getProperty('createdAt');
                $property->setAccessible(true);
                $property->setValue($slot, new DateTimeImmutable('-6 minutes'));
            }
        }

        /**
         * Doctors JSON data
         */
        $request = $this->createMock(Request::class);
        $request->method('fetchData')->willReturn(
            JsonDecoder::getJsonDecode(file_get_contents(__DIR__ . '/doctors_input.json'))
        );
        $doctorsRequest = new DoctorsRequest($request);

        /**
         * Slots JSON data.
         * The files named slots_input_<doctorId>.json are loaded.
         * If file not exists JsonException will be thrown causing doctor to have 'error' property set to true.
         * For testing purposes the int numbers are used for 'start' and 'end' properties,
         *  which are converted into same amount of minutes from 'NOW'.
         */
        $request = $this->createMock(Request::class);
        $request->method('fetchData')->willReturnCallback(
            fn (string $uri): mixed => $this->loadSlotsData($uri)
        );

        $logger = $this->createMock(LoggerInterface::class);

        $slotsRequest = new SLotsRequest($request, $logger);

        /**
         * Syncronizer
         */
        $syncronizer = new DoctorSlotsSynchronizer(
            $doctorsRepository,
            $slotsRepository,
            $doctorsRequest,
            $slotsRequest
        );

        $syncronizer->synchronizeDoctorSlots();

        /**
         * Asserts
         */
        $this->assertEquals(
            JsonDecoder::getJsonDecode(file_get_contents(__DIR__ . '/doctors_output.json')),
            $this->doctorsToArray()
        );

        $this->assertEquals(
            $this->convertTime(
                JsonDecoder::getJsonDecode(file_get_contents(__DIR__ . '/slots_output.json'))
            ),
            $this->slotsToArray()
        );
    }

    private function loadSlotsData(string $uri): mixed
    {
        [, $id] = explode('/', $uri);
        $fileName = sprintf('%s/slots_input_%s.json', __DIR__, $id);
        try {
            $content = file_get_contents($fileName);
        } catch (Throwable $e) {
            $content = false;
        }

        $content = JsonDecoder::getJsonDecode($content);

        return $this->convertTime($content);
    }

    private function doctorsToArray(): array
    {
        return array_values(array_map(
            fn (Doctor $doctor) => [
                'id' => $doctor->getId(),
                'name' => $doctor->getName(),
                'error' => $doctor->hasError(),
            ],
            $this->doctors
        ));
    }

    private function slotsToArray(): array
    {
        return array_values(array_map(
            fn (Slot $slot) => [
                'doctorId' => $slot->getDoctorId(),
                'start' => $slot->getStart()->format(DATE_ATOM),
                'end' => $slot->getEnd()->format(DATE_ATOM),
            ],
            $this->slots
        ));
    }

    private function convertTime(array $data): array
    {
        return array_map(
            function (array $input): array {
                $output = [];
                foreach ($input as $key => $value) {
                    $output[$key] = ('start' === $key || 'end' === $key)
                        ? $this->now->modify($value . ' minutes')->format(DATE_ATOM)
                        : $value;
                }

                return $output;
            },
            $data
        );
    }
}
