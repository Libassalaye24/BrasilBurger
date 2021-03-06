<?php

namespace App\Form;

use App\Entity\Burger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class BurgerType extends AbstractType
{
    private  $translator;
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /* ->add('nom',TextType::class,[
                'attr' => [
                     'class' => 'input',
                ],
                'constraints' => [
                    new NotBlank([
                        'message'=>$this->translator->trans('burger.blank')
                    ])
                ],
            ])
            ->add('prix',NumberType::class,[
                'attr' => [
                     'class' => 'input',
                ],
                'constraints' => [
                    new NotBlank([
                        'message'=>$this->translator->trans('burger.blank')
                    ])
                ],
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
            ]) */
            ->add('image',ImageType::class,[
                'attr' => [
                    'class' => 'input',
               ],
            ])
           /*  ->add('Valider', SubmitType::class) */
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Burger::class,
            'required' => false,
            'constraints' => [
                new UniqueEntity(
                    [
                        'fields' => ['nom'],
                        'message'=> 'Ce nom existe deja!'
                    ])
            ]
        ]);
    }
}
