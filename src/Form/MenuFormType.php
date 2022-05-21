<?php

namespace App\Form;

use App\Entity\Menu;
use App\Entity\Burger;
use App\Entity\Complement;
use Doctrine\ORM\Mapping\Entity;
use App\Repository\BurgerRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class MenuFormType extends AbstractType
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
           /*  ->add('complements',EntityType::class,[
                'class' => Complement::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                
                'constraints' => [
                    new NotBlank([
                        'message'=>$this->translator->trans('burger.blank')
                    ])
                ],
                
            ]) */
            
            ->add('image',ImageType::class,[
                
            ])
            ->add('Valider', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
            'required' => false,
        ]);
    }
}
