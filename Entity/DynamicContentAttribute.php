<?php

namespace PN\ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Arxy\EntityTranslationsBundle\Model\Translatable;
use PN\LocaleBundle\Model\LocaleTrait;

/**
 * @ORM\Table(name="dynamic_content_attribute")
 * @ORM\Entity()
 */
class DynamicContentAttribute implements Translatable {

    use LocaleTrait;

    CONST TYPE_TEXT = 1;
    CONST TYPE_LONGTEXT = 2;
    CONST TYPE_LINK = 3;
    CONST TYPE_IMAGE = 4;
    CONST TYPE_DOCUMENT = 5;
    CONST TYPE_HTML = 6;

    public static $types = [
        "Text (100 character)" => self::TYPE_TEXT,
        "Long text" => self::TYPE_LONGTEXT,
        "Link" => self::TYPE_LINK,
        "Image" => self::TYPE_IMAGE,
        "Document" => self::TYPE_DOCUMENT,
        "HTML Tags" => self::TYPE_HTML,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="DynamicContent", inversedBy="dynamicContentAttributes")
     */
    protected $dynamicContent;

    /**
     * @ORM\ManyToMany(targetEntity="\PN\MediaBundle\Entity\Image", cascade={"persist", "remove" })
     */
    protected $image;

    /**
     * @ORM\ManyToMany(targetEntity="\PN\MediaBundle\Entity\Document" ,cascade={"persist", "remove"})
     */
    protected $document;

    /**
     * @var string
     * @Assert\NotNull()
     * @ORM\Column(name="title", type="string", length=50)
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    protected $value;

    /**
     * @var string
     * @Assert\NotNull()
     * @ORM\Column(name="type", type="smallint")
     */
    protected $type;

    /**
     * @var string
     * @ORM\Column(name="hint", type="string", nullable=true)
     */
    protected $hint;

    /**
     * @ORM\Column(name="image_width", type="float", nullable=true)
     */
    protected $imageWidth;

    /**
     * @ORM\Column(name="image_height", type="float", nullable=true)
     */
    protected $imageHeight;

    /**
     * @ORM\OneToMany(targetEntity="PN\ContentBundle\Entity\Translation\DynamicContentAttributeTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $translations;

    /**
     * Constructor
     */
    public function __construct() {
        $this->image = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString() {
        return (string) $this->getValue();
    }

    public function getTypeName() {
        return array_search($this->getType(), self::$types);
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return DynamicContentAttribute
     */
    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return DynamicContentAttribute
     */
    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set hint
     *
     * @param string $hint
     *
     * @return DynamicContentAttribute
     */
    public function setHint($hint) {
        $this->hint = $hint;

        return $this;
    }

    /**
     * Get hint
     *
     * @return string
     */
    public function getHint() {
        return $this->hint;
    }

    /**
     * Set dynamicContent
     *
     * @param \PN\ContentBundle\Entity\DynamicContent $dynamicContent
     *
     * @return DynamicContentAttribute
     */
    public function setDynamicContent(\PN\ContentBundle\Entity\DynamicContent $dynamicContent = null) {
        $this->dynamicContent = $dynamicContent;

        return $this;
    }

    /**
     * Get dynamicContent
     *
     * @return \PN\ContentBundle\Entity\DynamicContent
     */
    public function getDynamicContent() {
        return $this->dynamicContent;
    }

    /**
     * Add image
     *
     * @param \PN\MediaBundle\Entity\Image $image
     *
     * @return DynamicContentAttribute
     */
    public function addImage(\PN\MediaBundle\Entity\Image $image) {
        $this->image[] = $image;

        return $this;
    }

    /**
     * Remove image
     *
     * @param \PN\MediaBundle\Entity\Image $image
     */
    public function removeImage(\PN\MediaBundle\Entity\Image $image) {
        $this->image->removeElement($image);
    }

    /**
     * Get image
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getImage() {
        return $this->image->first();
    }

    /**
     * Add document
     *
     * @param \PN\MediaBundle\Entity\Document $document
     *
     * @return DynamicContentAttribute
     */
    public function addDocument(\PN\MediaBundle\Entity\Document $document) {
        $this->document[] = $document;

        return $this;
    }

    /**
     * Remove document
     *
     * @param \PN\MediaBundle\Entity\Document $document
     */
    public function removeDocument(\PN\MediaBundle\Entity\Document $document) {
        $this->document->removeElement($document);
    }

    /**
     * Get document
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocument() {
        return $this->document->first();
    }

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
        return !$this->currentTranslation ? $this->value : $this->currentTranslation->getValue();
    }

    /**
     * Set imageWidth
     *
     * @param float $imageWidth
     * @return DynamicContentAttribute
     */
    public function setImageWidth($imageWidth) {
        $this->imageWidth = $imageWidth;

        return $this;
    }

    /**
     * Get imageWidth
     *
     * @return float
     */
    public function getImageWidth() {
        return $this->imageWidth;
    }

    /**
     * Set imageHeight
     *
     * @param float $imageHeight
     * @return DynamicContentAttribute
     */
    public function setImageHeight($imageHeight) {
        $this->imageHeight = $imageHeight;

        return $this;
    }

    /**
     * Get imageHeight
     *
     * @return float
     */
    public function getImageHeight() {
        return $this->imageHeight;
    }

}
