<?php


namespace App\Controller;


use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\ReservationType;
use DateInterval;
use DateTimeImmutable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RoomController
 * @Route("/room", name="room_")
 * @IsGranted("ROLE_USER")
 * @package App\Controller
 */
class RoomController extends AbstractController
{
    /**
     * @Route("/", name="showAll")
     */
    public function showAll() {
        $rooms = $this->getDoctrine()->getRepository(Room::class)->findAll();
        return $this->render("room/showAll.html.twig", array(
            'rooms' => $rooms,
        ));
    }

    /**
     * @Route("/{rid}/", name="show", requirements={"rid"="\d+"})
     */
    public function show(int $rid) {
        $room = $this->getDoctrine()->getRepository(Room::class)->find($rid);

        $reservations = $this->getDoctrine()->getRepository(Reservation::class)
            ->findReserved($room);
        return $this->render("room/show.html.twig", array(
            'room' => $room,
            'reservations' => $reservations,
        ));
    }

    /**
     * @Route("/{rid}/reserve", name="reserve", requirements={"rid"="\d+"})
     */
    public function reserve(int $rid, Request $request) {
        $room = $this->getDoctrine()->getRepository(Room::class)->find($rid);

        if (!$room) {
            throw $this->createNotFoundException("No such room.");
        }

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);

        $form->handleRequest($request);
        do {
            if (!$form->isSubmitted() || !$form->isValid()) break;

            /** @noinspection PhpParamsInspection */
            $reservation->setUser($this->getUser());

            $reservation->setRoom($room);

            $date = DateTimeImmutable::createFromMutable($form->get('date')->getData());
            $reservation->setStartTime(
                $date->add(new DateInterval(
                    'P0000-00-00' .
                    $form->get('startTime')->getData()->format('\TH:i:s')
                )));
            $reservation->setEndTime(
                $date->add(new DateInterval(
                    'P0000-00-00' .
                    $form->get('endTime')->getData()->format('\TH:i:s')
                )));

            if ($reservation->getStartTime() >= $reservation->getEndTime()) {
                $form->addError(new FormError('Start time must be before end time.'));
                break;
            }

            if ($reservation->getStartTime() < new \DateTime()) {
                $form->addError(new FormError('Start time must be after current time.'));
                break;
            }

            $reservationRepository = $this->getDoctrine()->getRepository(Reservation::class);
            if ($reservationRepository->countOverlapped($reservation) > 0) {
                $form->addError(new FormError('Time span conflicts.'));
                break;
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Your reservation was saved!'
            );
            return $this->redirectToRoute('room_show', ['rid' => $room->getId()]);
        } while (false);

        return $this->render("room/reserve.html.twig", array(
            'room' => $room,
            'form' => $form->createView(),
        ));
    }
}