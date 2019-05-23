<?php

namespace PN\ContentBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use PN\ContentBundle\Form\Model\PostTypeModel;
use VM5\EntityTranslationsBundle\Form\Type\TranslationsType;

class PostType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $attributes = $options['attributes'];
        $this->createForm($builder, $attributes);

        $builder->add('translations', TranslationsType::class, [
            'entry_type' => Translation\PostTranslationType::class,
            'query_builder' => function(EntityRepository $repo) {
                return $repo->createQueryBuilder('languages');
            }, // optional.
            "label" => false,
            'entry_language_options' => [
                'en' => [
                    'required' => true,
                ]
            ],
        ])
        ;
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
            'data_class' => 'PN\ContentBundle\Entity\Post',
            "label" => false,
            "attributes" => null,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() {
        return 'pn_bundle_cmsbundle_post';
    }

}
