<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Doctor;
use App\Request\DoctorDTO;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctorsRepository
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $em)
    {
        $this->repository = $em->getRepository(Doctor::class);
    }

    public function findOrCreate(DoctorDTO $doctorDTO): Doctor
    {
        $entity = $this->repository->find($doctorDTO->id())
            ?? new Doctor($doctorDTO->id(), $doctorDTO->name());
        $entity->setName($doctorDTO->name());
        $entity->clearError();
        $this->save($entity);

        return $entity;
    }

    public function markError(Doctor $entity): void
    {
        $entity->markError();
        $this->save($entity);
    }

    private function save(Doctor $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }
}