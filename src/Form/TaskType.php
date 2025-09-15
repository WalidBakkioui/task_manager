<?php

namespace App\Form;

use App\Entity\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Group;
use Doctrine\ORM\EntityRepository;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new Assert\Length([
                        'max' => 30,
                        'maxMessage' => 'Le titre ne peut pas dépasser 30 caractères.',
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 150,
                        'maxMessage' => 'La description ne peut pas dépasser 150 caractères.',
                    ])
                ]
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Date limite',
                'widget' => 'single_text',
                'required' => true, //
                'attr' => [
                    'min' => (new \DateTime('today'))->format('Y-m-d'), // empêche dates passées côté navigateur
                ],
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Faible' => 'faible',
                    'Moyenne' => 'moyenne',
                    'Élevée' => 'élevée',
                ],
            ])
            ->add('group', EntityType::class, [
                'class' => Group::class,
                'choice_label' => 'name',
                'placeholder' => '— Sélectionner un groupe —',
                'required' => false,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('g')
                        ->where('g.user = :u')
                        ->setParameter('u', $options['user'])
                        ->orderBy('g.name', 'ASC');

                    // 👇 masque "Sans groupe" si demandé
                    if ($options['hide_default_group'] === true) {
                        $qb->andWhere('g.name <> :def')->setParameter('def', 'Sans groupe');
                    }
                    return $qb;
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'user' => null,
            'hide_default_group' => false,
        ]);
    }
}