<?php

namespace PN\ContentBundle\Form\Translation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PostTranslationType extends AbstractType {

    protected $postTranslationClass;

    public function __construct(ContainerInterface $container) {
        $this->postTranslationClass = $container->getParameter("pn_content_post_translation_class");
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $attributes = $options['attributes'];
        $this->createForm($builder, $attributes);
    }

    private function createForm(FormBuilderInterface $builder, $attributes) {

        if ($attributes == null) { // Default attributes
            $builder->add('brief', TextareaType::class, [
                        'label' => 'Brief',
                        'property_path' => 'content[brief]'
                    ])
                    ->add('description', TextareaType::class, [
                        'label' => 'Description',
                        'property_path' => 'content[description]'
            ]);
        } else if ($attributes instanceof PostTypeModel) {
            $children = $attributes->getChildren();
            foreach ($children as $child) {
                $name = $child['name'];
                $label = $child['label'];
                $options = $child['options'];

                $fieldOptions = [
                    'label' => $label,
                    'property_path' => 'content[' . $name . ']'
                ];

                if (count($options) > 0) {
                    $options = array_merge($fieldOptions, $options);
                }
                $builder->add($name, TextareaType::class, $options);
            }
        } else {
            throw new Exception('Invalid $attributes value passed to PostType');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => $this->postTranslationClass,
            "attributes" => null
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() {
        return 'pn_bundle_cmsbundle_blogger';
    }

}
