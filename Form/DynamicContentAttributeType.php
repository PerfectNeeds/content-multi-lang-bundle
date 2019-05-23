<?php

namespace PN\ContentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use PN\ContentBundle\Entity\DynamicContentAttribute;

class DynamicContentAttributeType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('title')
                ->add('type', ChoiceType::class, [
                    "placeholder" => "Please select",
                    "choices" => DynamicContentAttribute::$types
                ])
                ->add("hint", TextType::class, ["required" => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'PN\ContentBundle\Entity\DynamicContentAttribute'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() {
        return 'pn_bundle_cmsbundle_dynamiccontentattribute';
    }

}
