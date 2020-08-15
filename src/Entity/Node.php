<?php
namespace Heccjj\SkatingBundle\Entity;

use Beelab\TagBundle\Tag\TaggableInterface;
use Beelab\TagBundle\Tag\TagInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use PhpParser\Node\Expr\Cast\Array_;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="Heccjj\SkatingBundle\Repository\NodeRepository")
 * 
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="nodetype", type="string")
 * 
 * @UniqueEntity(fields={"slug", "dir"})
 */
class Node implements TaggableInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    public $typeName;

    /**
     * @ORM\Column(type="string", length=50)
     */
    public $slug;

    /**
     * @ORM\Column(type="string", length=255, options={"default": "/"})
     */
    public $dir;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    public $memo;

    /**
     * @ORM\ManyToOne(targetEntity="Heccjj\SkatingBundle\Entity\User", inversedBy="nodes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @ORM\Column(type="string", length=50)
     */
    public $status='drafted';

    /**
     * @ORM\Column(type="boolean", options={"default": 0}, nullable=true)
     */
    public $isInner=0;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    public $visitedTimes=0;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    public $praisedTimes=0;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    public $createAt;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    public $updateAt;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"}, nullable=true)
     */
    public $publishAt = null;
    
    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"}, nullable=true)
     */
    public $publishEndAt = null;

    /**
     * @ORM\ManyToMany(targetEntity="Heccjj\SkatingBundle\Entity\Tag", inversedBy="nodes", cascade={"persist"})
     */
    private $tags;

    /**
     * @ORM\Column(type="string", length=250, nullable=true)
     * 
     * 便于检索
     */
    private $tagsText;
    private $timeChanged;

    /**
     *  @ORM\OneToMany(targetEntity="Heccjj\SkatingBundle\Entity\Meta", mappedBy="node", cascade={"persist"})
     */
    public $metas;

    /**
    * @ORM\Column(type="boolean", options={"default": 0})
    */
    private $isDeleted=0;

    /**
     * @ORM\ManyToMany(targetEntity="Heccjj\SkatingBundle\Entity\Version", inversedBy="nodes", cascade={"persist"})
     */
    private $versions;

    public function __construct(){
        $this->tags = new ArrayCollection();
        $this->metas = new ArrayCollection();
        $this->versions = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTypeName()
    {
        return strtolower($this->typeName);
    }

    public function setTypeName(string $typename)
    {
        $this->typeName = strtolower($typename);
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDir(){
        return $this->dir;
    }

    public function setDir($dir = ''){
        if($dir=='')
            $this->dir = '/';
        
        $this->dir = $dir;

        return $this;
    }

    public function setCreateAt(\DateTime $value = null){
        $this->createAt =  (($value == null) ? new \DateTime() : $value);

        return $this;
    }

    public function setUpdateAt(\DateTime $value = null){
        $this->updateAt = (($value == null) ? new \DateTime() : $value);

        return $this;
    }

    public function setPublishAt(\DateTime $value = null){
        $this->publishAt = $value;

        return $this;
    }

    public function setPublishEndAt(\DateTime $value = null){
        $this->publishEndAt = $value;
    }

    public function setStatus($status = null, $context = []){
        if($status == null)
            $this->status ="created";

        $this->status = $status;

        return $this;
    }

    public function getStatus(){
        return $this->status;
    }

    public function getType()
    {
        $type = explode('\\', get_class($this));

        return end($type);
    }

    public function addTag(TagInterface $tag): void
    {
        if (!$this->tags->contains($tag)) {
             $this->tags->add($tag);
        }
    }

    public function removeTag(TagInterface $tag): void
    {
        $this->tags->removeElement($tag);
    }

    public function hasTag(TagInterface $tag): bool
    {
        return $this->tags->contains($tag);
    }

    public function getTags(): iterable
    {
        return $this->tags;
    }

    public function getTagNames(): array
    {
        return empty($this->tagsText) ? [] : \array_map('trim', explode(',', $this->tagsText));
    }

    public function setTagsText(?string $tagsText): void
    {
        $this->tagsText = $tagsText;
        //$this->updateAt = new \DateTimeImmutable();
        $this->timeChanged = new \DateTimeImmutable();
    }
    
    public function getTagsText(): ?string
    {
        $this->tagsText = \implode(',', $this->tags->toArray());

        return $this->tagsText;
    }

    public function addMeta($meta): void
    {
        if (!$this->metas->contains($meta)) {
            $this->metas->add($meta);
            $meta->setNode($this);
        }
    }

    public function removeMeta($meta): void
    {
        $this->metas->removeElement($meta);
        if($meta->getNode === $this){
            $meta->setNode(null);
        }
    }

    public function hasMeta($meta): bool
    {
        return $this->metas->contains($meta);
    }

    public function getMetas(): iterable
    {
        return $this->metas;
    }

    public function setMetasNode()
    {
        $this->metas->forAll(function($key, $mark){
            return $mark->setNode($this);
        });
    }

    public function setIsInner($inner = null){
        if($inner == null)
            $this->isInner = 0;
        else
            $this->isInner = $inner;

        return $this;
    }

    public function getIsDeleted(){
        return $this->isDeleted;
    }

    public function setIsDeleted($is_deleted)
    {
        $this->isDeleted = $is_deleted;
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

    public function addVersion($version): void
    {
        if (!$this->versions->contains($version)) {
            $this->versions->add($version);
        }
    }

    public function removeVersion($version): void
    {
        $this->versions->removeElement($version);
    }

    public function hasVerion($version): bool
    {
        return $this->versions->contains($version);
    }

    public function hasVerions(): bool
    {
        if($this->versions->count()>=1)
            return true;
        else
            return false;
    }

    public function getVersions(): iterable
    {
        return $this->versions;
    }


}
