<?php


namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class UserController
 * @Route("/my", name="user_")
 * @IsGranted("ROLE_USER")
 * @package App\Controller
 */
class UserController extends AbstractController
{
    /**
     * @Route("/profile", name="profile")
     */
    public function updateProfile(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        TranslatorInterface $translator
    ) {
        /** @var $user User */
        $user = $this->getUser();

        $formBuilder = $this->createFormBuilder();
        $formBuilder
            ->add('phoneNumber', TextType::class, [
                'attr' => array(
                    'value' => $user->getPhoneNumber()
                )
            ])
            ->add('newPassword', PasswordType::class, [
                'help' => "Leave blank if you don't want to change.",
                "required" => false,
            ])
            ->add('repeatPassword', PasswordType::class, [
                "required" => false,
            ])
            ->add('submit', SubmitType::class);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);
        do {
            if (!$form->isSubmitted() || !$form->isValid()) break;

            $password = $form->get('newPassword')->getData();

            if ($password) {
                if ($password !== $form->get('repeatPassword')->getData()) {
                    $form->addError(new FormError('Password do not match.'));
                    break;
                }

                if (strlen($password) < 8) {
                    $form->addError(new FormError('Password too short.'));
                    break;
                }

                $user->setPassword($passwordEncoder->encodePassword($user, $password));
            }

            $user->setPhoneNumber($form->get('phoneNumber')->getData());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash(
                'success',
                $translator->trans('Your profile was updated!')
            );
            return $this->redirectToRoute('user_profile');
        } while (false);

        return $this->render("user/profile.html.twig", array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/reservation", name="reservation")
     */
    public function showReservations() {
        $reservations = $this->getDoctrine()->getRepository(Reservation::class)
            ->findBy(['user' => $this->getUser()]);

        return $this->render("user/reservation.html.twig", array(
            'reservations' => $reservations,
        ));
    }
}