<?php


namespace App\Controller;


use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\User;
use App\Form\ReservationType;
use App\Form\RoomType;
use DateInterval;
use DateTimeImmutable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    public function show(int $rid, TranslatorInterface $t) {
        $room = $this->getDoctrine()->getRepository(Room::class)->find($rid);

        if (!$room->getEnabled() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException($t->trans("No such room."));
        }
        $reservations = $this->getDoctrine()->getRepository(Reservation::class)
            ->findReserved($room, !$this->isGranted("ROLE_ADMIN"));
        return $this->render("room/show.html.twig", array(
            'room' => $room,
            'reservations' => $reservations,
        ));
    }

    /**
     * @Route("/add", name="add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, TranslatorInterface $t) {
        $room = new Room();
        $form = $this->createForm(RoomType::class, $room);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                $image->move(
                    $this->getParameter('room_image_directory'),
                    $newFilename
                );

                $room->setImage($newFilename);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($room);
            $em->flush();

            $this->addFlash('success', $t->trans('New room created!'));
            return $this->redirectToRoute('room_showAll');
        }

        return $this->render("room/add.html.twig", array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{rid}/edit", name="edit", requirements={"rid"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(int $rid, Request $request, TranslatorInterface $t) {
        $room = $this->getDoctrine()->getRepository(Room::class)->find($rid);

        if (!$room) {
            throw $this->createNotFoundException($t->trans("No such room."));
        }

        $form = $this->createForm(RoomType::class, $room);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$room->getEnabled()) {
                $this->getDoctrine()->getRepository(Reservation::class)->rejectAllFromNow($room);
            }

            $image = $form->get('image')->getData();
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                $image->move(
                    $this->getParameter('room_image_directory'),
                    $newFilename
                );

                $room->setImage($newFilename);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($room);
            $em->flush();

            $this->addFlash('success', $t->trans('Room updated!'));
            return $this->redirectToRoute('room_showAll');
        }

        return $this->render("room/edit.html.twig", array(
            'room' => $room,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{rid}/reserve", name="reserve", requirements={"rid"="\d+"})
     */
    public function reserve(int $rid, Request $request, TranslatorInterface $t) {
        $room = $this->getDoctrine()->getRepository(Room::class)->find($rid);

        if (!$room ||
            (!$room->getEnabled() && !$this->isGranted('ROLE_ADMIN'))
        ) {
            throw $this->createNotFoundException($t->trans("No such room."));
        }

        /* @var $user User */
        $user = $this->getUser();
        if ($user->getPhoneNumber() === "") {
            $this->addFlash('danger', $t->trans('You must fill in your phone number before reserving!'));
            return $this->redirectToRoute('user_profile');
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
                $form->addError(new FormError($t->trans('Start time must be before end time!')));
                break;
            }

            if ($reservation->getStartTime() < new \DateTime()) {
                $form->addError(new FormError($t->trans('Start time must be after current time!')));
                break;
            }

            $reservationRepository = $this->getDoctrine()->getRepository(Reservation::class);
            if ($reservationRepository->countOverlapped($reservation) > 0) {
                $form->addError(new FormError($t->trans('Time span conflicts!')));
                break;
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash(
                'success',
                $t->trans('Your reservation was saved!')
            );
            return $this->redirectToRoute('room_show', ['rid' => $room->getId()]);
        } while (false);

        return $this->render("room/reserve.html.twig", array(
            'room' => $room,
            'form' => $form->createView(),
        ));
    }
}