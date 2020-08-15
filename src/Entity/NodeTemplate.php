<?php

namespace Heccjj\SkatingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class NodeTemplate extends Node
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    public $content;
}
