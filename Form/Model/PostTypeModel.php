<?php

namespace PN\ContentBundle\Form\Model;

class PostTypeModel {

    private $children = [];

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     */
    public function add($name, $label, array $options = []) {

        $this->children[$name] = [
            "name" => $name,
            "label" => $label,
            "options" => $options,
        ];
    }

    public function getChildren() {
        return $this->children;
    }

}
