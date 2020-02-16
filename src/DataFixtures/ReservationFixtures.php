<?php

namespace App\DataFixtures;

use App\Entity\Reservation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ReservationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $reservation = new Reservation();
        $reservation->setStartTime(new \DateTime("2020-02-15 14:00:00"));
        $reservation->setEndTime(new \DateTime("2020-02-15 15:00:00"));
        $reservation->setRoom($this->getReference("room1"))->setUser($this->getReference("user1"));
        $reservation->setReason("Some Reason.");
        $manager->persist($reservation);

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            UserFixtures::class,
            RoomFixtures::class,
        );
    }
}
