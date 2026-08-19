<?php

namespace App\Form;
use App\Entity\Jeu;
use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use App\Enum\Plateforme;
use App\Enum\Pegi;
use App\Enum\Etat;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class JeuForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class)
            ->add('description', TextareaType::class)
            ->add('prix', MoneyType::class)
            ->add('plateforme', EnumType::class, [
                'class' => Plateforme::class,
                'choice_label' => fn (Plateforme $plateforme) => $plateforme->label(),
            ])
            ->add('pegi', EnumType::class, [
                'class' => Pegi::class,
                'choice_label' => fn (Pegi $pegi) => $pegi->label(),
            ])
            ->add('etat', EnumType::class, [
                'class' => Etat::class,
                'choice_label' => fn (Etat $etat) => $etat->label(),
            ])
            ->add('categories', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Catégories',
            ])
            ->add('image', UrlType::class, [
                'required' => false,
                'label' => 'URL de l\'image (optionnel)',
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