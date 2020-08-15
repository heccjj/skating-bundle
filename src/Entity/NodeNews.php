<?php

namespace Heccjj\SkatingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class NodeNews extends Node
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

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $photoPic;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    public $photoText;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $source;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $sourceUrl;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    public $innerMemo;

}
