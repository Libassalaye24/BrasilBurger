<?php

namespace App\Form;

use App\Entity\Burger;
use App\Form\ImageType;
use App\Repository\BurgerRepository;
use App\Repository\ComplementRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class FoodFormType extends AbstractType
{
    private  $translator;
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    } 
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nom',TextType::class,[
            'attr' => [
                'class' => 'input'
            ],
            'constraints' => [
                new NotBlank([
                    'message'=>$this->translator->trans('burger.blank')
                ])
            ],
        ])
        ->add('prix',NumberType::class,[
            'attr' => [
                 'class' => 'input'
            ],
            'constraints' => [
                new NotBlank([
                    'message'=>$this->translator->trans('burger.blank')
                ])
            ],
        ])
        ->add('type', ChoiceType::class, [
                
            'attr' => [
                'onclick' => 'handleClick(this)'
            ],
            'choices' => [
                'Choisir' => '',
                'Menu' => 'menu',
                'Burger' => 'burger',
                'Complement' => 'complement',
            ]
        ])
        ->add('description',TextType::class,[
            'attr' => [
                 'class' => 'input',
            ],
            'constraints' => [
                new NotBlank([
                    'message'=>$this->translator->trans('burger.blank')
                ])
            ],
        ])
        ->add('burger',EntityType::class,[
            'class' => Burger::class,
            'attr' => [
                'class' => 'input'
            ],
            'choice_label' => 'nom',
            'placeholder' => 'Selectionnez Le burger',
            'query_builder' => function (BurgerRepository $er) {
                return $er->createQueryBuilder('b')
                        ->where('b.etat = :etat')
                        ->setParameter('etat', false);
            },
            'constraints' => [
                new NotBlank([
                    'message'=>$this->translator->trans('burger.blank')
                ])
            ],
        ])
        ->add('complements',EntityType::class,[
            'class' => Complement::class,
            'choice_label' => 'nom',
            'query_builder' => function (ComplementRepository $er) {
                return $er->createQueryBuilder('c')
                        ->where('c.etat = :etat')
                        ->setParameter('etat', false);
            },
            'multiple' => true,
            'expanded' => true,
            
            'constraints' => [
                new NotBlank([
                    'message'=>$this->translator->trans('burger.blank')
                ])
            ],
            
        ])
        ->add('image',ImageType::class,[
            'attr' => [
                'class' => 'input',
           ],
        ])
        ->add('Valider', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'data_class' => Burger::class,
        ]);
    }
}
