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
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

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
            // Photo envoyée depuis l'ordinateur : pas liée à l'entité, le contrôleur la déplace
            ->add('imageFichier', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Photo du jeu (optionnel)',
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Merci d\'envoyer une image JPG, PNG ou WebP.',
                    ),
                ],
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