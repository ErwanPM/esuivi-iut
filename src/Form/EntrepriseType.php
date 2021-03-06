<?php

namespace App\Form;

use App\Form\CorrespondantEntrepriseType;
use App\Entity\Entreprise;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;

class EntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('nom',   TextType::class)
            ->add('adresse',   TextType::class)
            ->add('code_postal',   NumberType::class, array(
                'attr' => array('maxlength' => 5)))
            ->add('ville',   TextType::class);
//            ->add('email',   EmailType::class)
//            ->add('CorrespondantEntreprise', CorrespondantEntrepriseType::class, array('label' => false))
//            ->add('enregistrer', SubmitType::class);
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Entreprise::class,
            'validation_groups' => array('ajout_entreprise'),
        ));
    }

}
?>