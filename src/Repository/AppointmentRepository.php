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

    public function findByDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.appointmentDate = :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->andWhere('a.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()->getResult();
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

    public function hasOverlapForTherapist(
        int $therapistId,
        \DateTimeInterface $date,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?int $excludeAppointmentId = null
    ): bool {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.therapist = :therapistId')
            ->andWhere('a.appointmentDate = :date')
            ->andWhere('a.status != :cancelled')
            ->andWhere('a.startTime < :endTime')
            ->andWhere('a.endTime > :startTime')
            ->setParameter('therapistId', $therapistId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('startTime', $startTime->format('H:i:s'))
            ->setParameter('endTime', $endTime->format('H:i:s'));

        if ($excludeAppointmentId !== null) {
            $qb->andWhere('a.id != :excludeId')->setParameter('excludeId', $excludeAppointmentId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function hasOverlapForPatient(
        int $patientId,
        \DateTimeInterface $date,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?int $excludeAppointmentId = null
    ): bool {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.patient = :patientId')
            ->andWhere('a.appointmentDate = :date')
            ->andWhere('a.status != :cancelled')
            ->andWhere('a.startTime < :endTime')
            ->andWhere('a.endTime > :startTime')
            ->setParameter('patientId', $patientId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('startTime', $startTime->format('H:i:s'))
            ->setParameter('endTime', $endTime->format('H:i:s'));

        if ($excludeAppointmentId !== null) {
            $qb->andWhere('a.id != :excludeId')->setParameter('excludeId', $excludeAppointmentId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
