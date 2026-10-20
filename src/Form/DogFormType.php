<?php

namespace App\Form;

use App\Entity\Dogs;
use App\Enum\DogsStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DogFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('espece', TextType::class, [
                'label' => 'Espèce',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('race', TextType::class, [
                'label' => 'Race',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('age', IntegerType::class, [
                'label' => 'Âge en années',
                'attr' => ['min' => 0],
                'constraints' => [new Assert\NotNull(), new Assert\PositiveOrZero()],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Disponible à l’adoption' => DogsStatus::Disponible->value,
                    'En cours d’adoption' => DogsStatus::EnAttente->value,
                    'Adopté' => DogsStatus::Adopte->value,
                ],
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('coverImage', FileType::class, [
                'label' => 'Photographie',
                'mapped' => false,
                'required' => false,
                'constraints' => [new Assert\Image(maxSize: '5M', extensions: ['jpg', 'jpeg', 'png', 'webp', 'gif'])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dogs::class,
            'csrf_protection' => true,
        ]);
    }
}
