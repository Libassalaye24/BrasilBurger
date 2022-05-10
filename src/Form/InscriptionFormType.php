<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\Unique;

class InscriptionFormType extends AbstractType
{
    private  $translator;
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email',TextType::class,[
                'attr' => [
                    'class' => 'input'
                ]
            ])
            ->add('password',PasswordType::class,[
                'attr' => [
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank([
                        'message'=>$this->translator->trans('burger.blank')
                    ])
                ],
            ])
            ->add('confirm',PasswordType::class,[
                'mapped' => false,
                'attr' => [
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank([
                        'message'=>$this->translator->trans('burger.blank')
                    ]),
                   
                ],
            ])
            ->add('prenom',TextType::class,[
                'attr' => [
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank([
                        'message'=>$this->translator->trans('burger.blank')
                    ])
                ],
            ])
            ->add('Nom',TextType::class,[
                'attr' => [
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank([
                        'message'=>$this->translator->trans('burger.blank')
                    ])
                ],
            ])
            ->add('telephone',NumberType::class,[
                'attr' => [
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank([
                        'message'=>$this->translator->trans('burger.blank')
                    ])
                ],
            ])
            ->add('Valider', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
            'required' => false,
            'constraints' => [
                new UniqueEntity(
                    [
                        'fields' => ['email'],
                        'message'=>$this->translator->trans('inscription.email.unique')
                   ])
            ]
        ]);
    }
}
