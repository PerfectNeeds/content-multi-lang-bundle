<?php

namespace PN\ContentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use PN\ServiceBundle\Service\ContainerParameterService;

class PostService
{
    private string $postClass;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, ContainerParameterService $containerParameter)
    {
        $this->em = $em;
        $this->postClass = $containerParameter->get('pn_content_post_class');
    }


    public function getRelationalEntity($post)
    {
        $entities = $this->getEntitiesWithPostObject();
        $statement = $this->em->createQueryBuilder()
            ->from($this->postClass, "p")
            ->where("p.id=:postId")
            ->setParameter("postId", $post->getId());

        foreach ($entities as $key => $entity) {
            $alias = "t".$key;
            $statement->addSelect($alias);
            $statement->leftJoin($entity['entity'], $alias, "WITH", "p.id=".$alias.".".$entity['columnName']);
        }
        $result = $statement->getQuery()->getResult();
        foreach ($result as $row) {
            if (is_object($row)) {
                return $row;
            }
        }

        return null;
    }

    public function getRelationalEntityId($post)
    {
        $entity = $this->getRelationalEntity($post);
        if (is_object($entity)) {
            return $post->getId();
        }

        return null;
    }

    private function getEntitiesWithPostObject()
    {
        $entities = [];
        $meta = $this->em->getMetadataFactory()->getAllMetadata();
        foreach ($meta as $m) {
            foreach ($m->getAssociationMappings() as $columnName => $associationMapping) {
                if (strpos($associationMapping['targetEntity'], "ContentBundle\Entity\Post") !== false
                    and strpos($m->getName(), "PostTranslation") === false
                    and strpos($m->getName(), "MediaBundle\Entity\Image") === false
                ) {
                    $entities[] = [
                        "columnName" => $columnName,
                        "entity" => $m->getName(),
                    ];
                }
            }
        }

        return $entities;
    }
}