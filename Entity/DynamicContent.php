<?php

namespace PN\ContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="dynamic_content")
 * @ORM\Entity(repositoryClass="PN\ContentBundle\Repository\DynamicContentRepository")
 */
class DynamicContent {

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Assert\NotNull()
     * @ORM\Column(name="title", type="string", length=50)
     */
    protected $title;

    /**
     * @ORM\OneToMany(targetEntity="DynamicContentAttribute", mappedBy="dynamicContent", cascade={"all"})
     */
    protected $dynamicContentAttributes;

    /**
     * Constructor
     */
    public function __construct() {
        $this->dynamicContentAttributes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return DynamicContent
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
     * Add dynamicContentAttribute
     *
     * @param \PN\ContentBundle\Entity\DynamicContentAttribute $dynamicContentAttribute
     *
     * @return DynamicContent
     */
    public function addDynamicContentAttribute(\PN\ContentBundle\Entity\DynamicContentAttribute $dynamicContentAttribute) {
        $this->dynamicContentAttributes[] = $dynamicContentAttribute;

        return $this;
    }

    /**
     * Remove dynamicContentAttribute
     *
     * @param \PN\ContentBundle\Entity\DynamicContentAttribute $dynamicContentAttribute
     */
    public function removeDynamicContentAttribute(\PN\ContentBundle\Entity\DynamicContentAttribute $dynamicContentAttribute) {
        $this->dynamicContentAttributes->removeElement($dynamicContentAttribute);
    }

    /**
     * Get dynamicContentAttributes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDynamicContentAttributes() {
        return $this->dynamicContentAttributes;
    }

}
