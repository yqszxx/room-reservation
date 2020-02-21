<?php


namespace App\Form;


use App\Entity\Room;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name of Room'])
            ->add('enabled', CheckboxType::class, [
                'help' => 'Change from enabled to disabled will reject all reservations of this room (including approved) starts later than current time.'
            ])
            ->add('description')
            ->add('image',  FileType::class, [
                'label' => 'Image (PNG file)',
                'help' => 'Leave blank to use default image (when creating) or preserve original image.',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '500k',
                        'mimeTypes' => [
                            'image/png'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PNG document',
                        'allowPortrait' => false,
                    ])
                ],
            ])
            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
        ]);
    }
}