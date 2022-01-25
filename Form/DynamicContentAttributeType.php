<?php

namespace PN\ContentBundle\Form;

use PN\ContentBundle\Entity\DynamicContentAttribute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DynamicContentAttributeType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('type', ChoiceType::class, [
                "placeholder" => "Please select",
                "choices" => DynamicContentAttribute::$types,
            ])
            ->add("hint", TextType::class, ["required" => false])
            ->add("imageWidth", NumberType::class, ["required" => false])
            ->add("imageHeight", NumberType::class, ["required" => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => DynamicContentAttribute::class,
        ));
    }

}
