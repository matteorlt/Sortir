<?php

namespace App\Form;

use App\Entity\Sortie;
use App\Enum\Statut;
use App\Enum\CampusEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomSortie', TextType::class, [
                'label' => 'Nom de la sortie',
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (minutes)',
            ])
            ->add('dateCloture', DateTimeType::class, [
                'label' => 'Date de clôture',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('nbInscriptionMax', IntegerType::class, [
                'label' => "Nombre d'inscriptions max",
            ])
            ->add('descriptionInfos', TextareaType::class, [
                'label' => 'Description',
            ])
            // Champ saisi utilisateur avec autocomplétion (non mappé)
            ->add('adresse', TextType::class, [
                'label' => 'Adresse (France)',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'autocomplete' => 'off',
                    'data-address-input' => 'true',
                ],
            ])
            ->add('categorie', ChoiceType::class, [
                'choices' => [
                    'Culture' => 'Culture',
                    'Sport' => 'Sport',
                    'Loisirs' => 'Loisirs',
                    'Détente' => 'Détente',
                    'Formation' => 'Formation',
                    'Autre' => 'Autre',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner une catégorie',
                'label' => 'Catégorie'
            ])
            // Champs cachés remplis par le JS après sélection
            ->add('adresse_full', HiddenType::class, ['mapped' => false])
            ->add('rue', HiddenType::class, ['mapped' => false])
            ->add('latitude', HiddenType::class, ['mapped' => false])
            ->add('longitude', HiddenType::class, ['mapped' => false])
            ->add('ville_nom', HiddenType::class, ['mapped' => false])
            ->add('code_postal', HiddenType::class, ['mapped' => false])

            // Campus via enum (non mappé -> recherche entité dans le contrôleur)
            ->add('campus', EnumType::class, [
                'label' => 'Campus',
                'class' => CampusEnum::class,
                'mapped' => false,
                'placeholder' => 'Choisir un campus',
                'choice_label' => fn(CampusEnum $c) => $c->value,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Sortie::class]);
    }
}
