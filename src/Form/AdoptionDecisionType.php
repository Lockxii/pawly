<?php

namespace App\Form;

use App\Enum\AdoptionStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;

class AdoptionDecisionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('status', EnumType::class, [
            'label' => 'Décision',
            'class' => AdoptionStatus::class,
            'choices' => [AdoptionStatus::Accepted, AdoptionStatus::Rejected],
            'choice_label' => fn (AdoptionStatus $status): string => $status->value,
            'placeholder' => 'Choisir une décision',
            'constraints' => [new NotNull(message: 'Choisissez une décision.')],
        ]);
    }
}
