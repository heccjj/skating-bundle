<?php

namespace Heccjj\SkatingBundle\Entity;

use Beelab\TagBundle\Tag\TagInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class Tag implements TagInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="Node", mappedBy="tags")
     */
    protected $nodes;

    public function __construct()
    {
        $this->nodes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addNode(Node $node)
    {
        $this->nodes[] = $node;
 
        return $this;
    }

    public function removeNode(Node $node)
    {
        $this->nodes->removeElement($node);
    }
 
    public function getNode()
    {
        return $this->nodes;
    }
}

