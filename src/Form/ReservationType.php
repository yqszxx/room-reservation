<?php

namespace App\Form;

use App\Entity\Reservation;
use Cassandra\Date;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateType::class, [
                'mapped' => false,
                'placeholder' => '',
                'years' => range(
                    (int)date("Y"),
                    (int)date("Y") + ((int)date("m") > 9 ? 1 : 0)
                ),
            ])
            ->add('startTime', TimeType::class, [
                'mapped' => false,
                'hours' => range(7, 23),
                'minutes' => range(0, 59, 5),
            ])
            ->add('endTime', TimeType::class, [
                'mapped' => false,
                'hours' => range(7, 23),
                'minutes' => range(0, 59, 5),
            ])
            ->add('reason')
            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
