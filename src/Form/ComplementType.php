<?php

namespace App\Form;

use App\Form\ImageType;
use App\Entity\Complement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ComplementType extends AbstractType
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
            ->add('image',ImageType::class,[
               
                'attr' => [
                     'class' => 'input'
                ]
            ])
            ->add('Valider', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Complement::class,
            'required' => false,
        ]);
    }
}
