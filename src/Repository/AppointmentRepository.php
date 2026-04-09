<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function save(Appointment $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(Appointment $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function findByTherapist(int $therapistId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.therapist = :id')->setParameter('id', $therapistId)
            ->orderBy('a.appointmentDate', 'DESC')->getQuery()->getResult();
    }

    public function findByPatient(int $patientId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.patient = :id')->setParameter('id', $patientId)
            ->orderBy('a.appointmentDate', 'DESC')->getQuery()->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')->setParameter('status', $status)
            ->orderBy('a.appointmentDate', 'DESC')->getQuery()->getResult();
    }

    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.appointmentDate >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('a.appointmentDate', 'ASC')->getQuery()->getResult();
    }
}
