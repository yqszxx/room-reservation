<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function countOverlapped(Reservation $reservation): int {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere("r.marking = 'approved' OR r.marking = 'pending' OR r.marking is NULL")
            ->andWhere('r.room = :room')
            ->andWhere('r.startTime < :end AND r.endTime > :start')
            ->setParameters([
                'room' => $reservation->getRoom(),
                'start' => $reservation->getStartTime(),
                'end' => $reservation->getEndTime()
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findReserved($room = null, $fromNow = false)
    {
        $query = $this->createQueryBuilder('r')
            ->andWhere("r.marking = 'approved' OR r.marking is NULL OR r.marking = 'pending'")
            ->orderBy('r.startTime');
        if ($room) $query->andWhere($query->expr()->eq('r.room', ':room'))->setParameter('room', $room);
        if ($fromNow) $query->andWhere('r.startTime > CURRENT_TIMESTAMP()');
        return $query->getQuery()->getResult();
    }

    public function findPending()
    {
        $query = $this->createQueryBuilder('r')
            ->andWhere("r.marking = 'pending' OR r.marking is NULL")
            ->orderBy('r.startTime');
        return $query->getQuery()->getResult();
    }

    public function rejectAllFromNow($room)
    {
        $query = $this->createQueryBuilder('r')
            ->update()
            ->set('r.marking', "'rejected'")
            ->andWhere('r.startTime > CURRENT_TIMESTAMP()');
        $query->andWhere($query->expr()->eq('r.room', ':room'))->setParameter('room', $room);
        $query->getQuery()->execute();
    }
}
