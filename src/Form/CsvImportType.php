<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class CsvImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Fichier CSV',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'text/plain',
                            'text/csv',
                            'text/x-csv',
                            'application/csv',
                            'application/vnd.ms-excel',
                        ],
                        'mimeTypesMessage' => 'Veuillez sélectionner un fichier CSV valide.',
                    ]),
                ],
            ])
            ->add('separator', TextType::class, [
                'label' => 'Séparateur',
                'mapped' => false,
                'required' => false,
                'empty_data' => ';',
                'help' => "Exemples: ';' ou ',' ou '\t'",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}