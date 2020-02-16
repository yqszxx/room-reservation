<?php


namespace App\Controller;

use App\Entity\Reservation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;

/**
 * Class ReservationController
 * @Route("/reservation", name="reservation_")
 * @IsGranted("ROLE_USER")
 * @package App\Controller
 */
class ReservationController extends AbstractController
{
    private Registry $workflowRegistry;

    public function __construct(Registry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @Route("/{id}/cancel", name="cancel", requirements={"id"="\d+"})
     */
    public function cancel(int $id) {
        $reservation = $this->getDoctrine()->getRepository(Reservation::class)
            ->findOneBy(['id' => $id, 'user' => $this->getUser()]);

        if (!$reservation) {
            throw $this->createNotFoundException('No such reservation.');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($reservation);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Your reservation was cancelled!'
        );

        return $this->redirectToRoute('user_reservation');
    }

    /**
     * @Route("/pending", name="pending")
     * @IsGranted("ROLE_ADMIN")
     */
    public function showPendingReservations() {
        $reservations = $this->getDoctrine()->getRepository(Reservation::class)
            ->findPending();

        return $this->render("reservation/pending.html.twig", array(
            'reservations' => $reservations,
        ));
    }

    /**
     * @Route("/{id}/approve", name="approve", requirements={"id"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function approve(int $id) {
        $reservation = $this->getDoctrine()->getRepository(Reservation::class)
            ->findOneBy(['id' => $id]);

        $workflow = $this->workflowRegistry->get($reservation);
        $workflow->apply($reservation, 'approve');

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Reservation approved!'
        );

        return $this->redirectToRoute('reservation_pending');
    }

    /**
     * @Route("/{id}/reject", name="reject", requirements={"id"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function reject(int $id) {
        $reservation = $this->getDoctrine()->getRepository(Reservation::class)
            ->findOneBy(['id' => $id]);

        $workflow = $this->workflowRegistry->get($reservation);
        $workflow->apply($reservation, 'reject');

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Reservation rejected!'
        );

        return $this->redirectToRoute('reservation_pending');
    }
}