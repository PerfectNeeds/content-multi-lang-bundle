<?php

namespace PN\ContentBundle\Model;

use Doctrine\ORM\Mapping as ORM;

trait PostTrait {

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="PN\ContentBundle\Entity\Translation\PostTranslation", mappedBy="translatable", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $translations;

    public function getId() {
        return $this->id;
    }

}
