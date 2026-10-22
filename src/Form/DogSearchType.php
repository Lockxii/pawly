<?php

namespace App\Form;

use App\Enum\DogsStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

class DogSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', TextType::class, [
                'label' => 'Nom, race ou espèce',
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Length(max: 100)],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'placeholder' => 'Tous les statuts',
                'choices' => [
                    'Disponible' => DogsStatus::Disponible->value,
                    'En cours d’adoption' => DogsStatus::EnAttente->value,
                    'Adopté' => DogsStatus::Adopte->value,
                ],
            ]);
    }
}
