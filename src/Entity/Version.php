<?php
namespace Heccjj\SkatingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Version
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="text")
     */
    public $node;

    /**
     * @ORM\ManyToOne(targetEntity="Heccjj\SkatingBundle\Entity\User", inversedBy="nodes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    public $createAt;

    public function setNode($node){
        $this->node = \serialize($node);
    }

    public function getNode(){
        return \unserialize($this->node);
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function setCreateAt(\DateTime $value = null){
        $this->createAt =  (($value == null) ? new \DateTime() : $value);

        return $this;
    }
}