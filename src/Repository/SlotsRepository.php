<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Slot;
use App\Request\SlotDTO;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class SlotsRepository
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $em)
    {
        $this->repository = $em->getRepository(Slot::class);
    }

    public function findOrCreate(SlotDTO $slotDTO): Slot
    {
        $entity = $this->repository->findOneBy(['doctorId' => $slotDTO->doctorId(), 'start' => $slotDTO->start()])
            ?: new Slot($slotDTO->doctorId(), $slotDTO->start(), $slotDTO->end());

        if ($entity->isStale()) {
            $entity->setEnd($slotDTO->end());
        }
        $this->save($entity);

        return $entity;
    }

    private function save(Slot $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }
}