<?php

namespace App\Form\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, ['label' => 'Nom d’utilisateur', 'empty_data' => ''])
            ->add('email', EmailType::class, ['label' => 'Adresse e-mail', 'empty_data' => ''])
            ->add('roles', ChoiceType::class, [
                'label' => 'Droits d’accès',
                'choices' => ['Utilisateur' => 'ROLE_USER', 'Membre' => 'ROLE_EMPLOYEE', 'Administrateur' => 'ROLE_ADMIN'],
                'multiple' => true,
                'expanded' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
