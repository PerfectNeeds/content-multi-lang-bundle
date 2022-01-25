<?php

namespace PN\ContentBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\TranslationEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="dynamic_content_attribute_translations")
 */
class DynamicContentAttributeTranslation extends TranslationEntity implements EditableTranslation {

    /**
     * @var string
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    protected $value;

    /**
     * @var 
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\ContentBundle\Entity\DynamicContentAttribute", inversedBy="translations")
     */
    protected $translatable;

    /**
     * @var Language
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected $language;

    /**
     * Set value
     *
     * @param string $value
     *
     * @return DynamicContentAttribute
     */
    public function setValue($value) {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

}
