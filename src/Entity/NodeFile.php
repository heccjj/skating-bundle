<?php

namespace Heccjj\SkatingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class NodeFile extends Node
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $fileName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $fileMime;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $fileSubPath;

    public function getFileName(){
        return $this->fileName;
    }

    public function setFileName($filename){
        $this->fileName =  $filename;
    }

    public function setFileMime($mime){
        $this->fileMime = $mime;
    }

    public function getFileSubPath(){
        return $this->fileSubPath;
    }

    public function setFileSubPath($fileSubPath){
        $this->fileSubPath = $fileSubPath;
    }
}
