<?php

namespace App\Form;

use App\Entity\Jeu;
use App\Enum\Pegi;
use App\Enum\Etat;
use App\Enum\JeuStatut;
use App\Enum\Plateforme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JeuEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
            ])
            ->add('plateforme', EnumType::class, [
                'class' => Plateforme::class,
                'label' => 'Plateforme',
                'required' => false,
                'placeholder' => 'Choisir une plateforme',
                'empty_data' => null,
                'choice_label' => fn(Plateforme $choice) => $choice->label(),
            ])
            ->add('pegi', EnumType::class, [
                'class' => Pegi::class,
                'label' => 'PEGI',
                'required' => false,
                'placeholder' => 'Choisir un PEGI',
                'choice_label' => fn(Pegi $choice) => $choice->label(),
                'empty_data' => null,
            ])
            ->add('etat', EnumType::class, [
                'class' => Etat::class,
                'label' => 'État',
                'required' => false,
                'placeholder' => 'Choisir un état',
                'choice_label' => fn(Etat $choice) => $choice->label(),
                'empty_data' => null,
            ])
            ->add('statut', EnumType::class, [
                'class' => JeuStatut::class,
                'label' => 'Statut',
                'required' => false,
                'placeholder' => 'Choisir un statut',
                'empty_data' => null,
                'choices' => [
                    JeuStatut::EN_VENTE,
                    JeuStatut::RETIRE,
                ],
                'choice_label' => fn(JeuStatut $choice) => $choice->label(),
            ])
            ->add('image', UrlType::class, [
                'label' => 'Image (URL)',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'placeholder' => 'https://exemple.com/image.jpg',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Jeu::class,
        ]);
    }
}