<?php

namespace Heccjj\SkatingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class NodeFolder extends Node
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    public $description;
}
