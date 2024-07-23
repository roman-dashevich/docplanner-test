<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="slot")
 */
final class Slot
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $doctorId;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTimeImmutable $start;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTimeImmutable $end;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTimeImmutable $createdAt;

    public function __construct(int $doctorId, DateTimeImmutable $start, DateTimeImmutable $end)
    {
        $this->doctorId = $doctorId;
        $this->start = $start;
        $this->end = $end;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getDoctorId(): int
    {
        return $this->doctorId;
    }

    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    public function setEnd(DateTimeImmutable $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getEnd(): DateTimeImmutable
    {
        return $this->end;
    }

    public function isStale(): bool
    {
        return $this->createdAt < new DateTimeImmutable('5 minutes ago');
    }
}
