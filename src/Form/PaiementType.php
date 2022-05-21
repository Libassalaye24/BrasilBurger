<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Paiement;
use App\Repository\CommandeRepository;
use App\Repository\PaiementRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaiementType extends AbstractType
{
    private  $translator;
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           /*  ->add('commande',EntityType::class,[
                'class' => Commande::class,
                'attr' => [
                    'class' => 'input'
                ],
                'choice_label' => 'numero',
                'placeholder' => 'Selectionnez Le burger',
                'query_builder' => function (CommandeRepository $er) {
                    return $er->createQueryBuilder('c')
                            ->where('p.client = :client')
                            ->setParameter('client', $this->getUser());
                },
                'constraints' => [
                    new NotBlank([
                        'message'=>$this->translator->trans('burger.blank')
                    ])
                ],
            ]) */
            ->add('montant',NumberType::class,[
                'attr' => [
                    'class' => 'input'
                ],
                'constraints' => [
                    new NotBlank([
                        'message'=>$this->translator->trans('burger.blank')
                    ])
                ],

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Paiement::class,
            'required' => false
        ]);
    }
}
