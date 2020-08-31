<?php

namespace App\Form;

use App\Entity\Lead;
use App\Entity\Organization;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LeadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // ->add('fields')
            // ->add('dt')
            // ->add('uuid')
            ->add('organization', EntityType::class, [
                'class' => Organization::class,
                'choice_label' => 'name'
            ])
            // ->add('internalRating')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Lead::class,
        ]);
    }
}
