<?php

namespace PN\ContentBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use PN\LocaleBundle\Model\LocaleTrait;

trait PostTrait {

    use LocaleTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function getId() {
        return $this->id;
    }

}
