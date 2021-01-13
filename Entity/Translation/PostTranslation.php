<?php

namespace PN\ContentBundle\Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use Arxy\EntityTranslationsBundle\Model\EditableTranslation;
use PN\LocaleBundle\Model\TranslationEntity;

/**
 * @ORM\MappedSuperclass
 */
class PostTranslation extends TranslationEntity implements EditableTranslation {

    /**
     * @var array
     *
     * @ORM\Column(name="content", type="json_array")
     */
    protected $content = [
        'brief' => '',
        'description' => '',
    ];

    /**
     * @var Language
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="PN\LocaleBundle\Entity\Language")
     */
    protected $language;

    /**
     * Set content
     *
     * @param array $content
     *
     * @return PostTranslation
     */
    public function setContent($content) {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return array
     */
    public function getContent() {
        return $this->content;
    }

}
