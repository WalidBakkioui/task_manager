<?php

namespace App\Form;

use App\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $o): void
    {
        $b->add('name', TextType::class, [
            'label' => 'Nom du groupe',
            'constraints' => [
                new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
                new Assert\Length(['max' => 64]),
            ],
        ])
            ->add('color', ColorType::class, [
                'label' => 'Couleur (optionnelle)',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (optionnelle)',
                'required' => false,
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    { $resolver->setDefaults(['data_class' => Group::class]); }
}
