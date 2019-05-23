<?php

namespace PN\ContentBundle\Form\Translation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PostTranslationType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('brief', TextareaType::class, [
                    'label' => 'Brief',
                    'property_path' => 'content[brief]'
                ])
                ->add('description', TextareaType::class, [
                    'label' => 'Description',
                    'property_path' => 'content[description]'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => \PN\ContentBundle\Entity\Translation\PostTranslation::class
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() {
        return 'pn_bundle_cmsbundle_blogger';
    }

}
