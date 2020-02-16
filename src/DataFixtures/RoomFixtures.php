<?php

namespace App\DataFixtures;

use App\Entity\Room;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class RoomFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= 10; $i++) {
            $room = new Room();
            $room->setName("Room " . $i);

            $this->addReference("room" . $i, $room);
            $manager->persist($room);
        }

        $manager->flush();
    }
}
