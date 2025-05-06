<?php

namespace App\Form;

use App\Entity\BlogPost;
use App\Entity\Category;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AjoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Marque',/* changer le nom */
            ])
            ->add('title', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['attr' =>['class' => 'Pour Ajoutez une classe'],
                'label' => 'Modele',/* changer le nom */

                ],
        )
            ->add('maxPeople', ChoiceType::class, [
                'choices' => [
                    'Two' => 2,
                    'Four' => 4,
                    'Five' => 5,
                ],
                'label' => 'Max de personnes',
                'choice_value' => function ($value) {
                    return $value;
                },
            ])
            ->add('type' , \Symfony\Component\Form\Extension\Core\Type\TextType::class)
            ->add('consumption')
            ->add('box', ChoiceType::class, [
                'choices' => [
                    'Boîte automatique' => 'auto',
                    'Boîte manuelle' => 'manuel',
                ],
                'label' => 'Choisissez la boîte de vitesses',/* changer le nom */
                'required' => true])
            ->add('years')
            ->add('photo', PhotoType::class)

            ->add('submit', SubmitType::class, [
                'attr' => ['class' => 'btn'],
                'label' => 'Enregistrez', /* changer le nom */
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogPost::class,
        ]);
    }

}
