<?php

namespace App\Entity;

use ArrayAccess;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ImageRepository;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image implements ArrayAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank()]
    #[Assert\File(mimeTypes:["image/jpeg","image/png"])]
    private $nom;

    public function __construct()
    {
        
    }
    public function __toString()
    {
        return $this->nom;
    }
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }
    public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset;
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;

    }
    public function offsetUnset(mixed $offset): void
    {
        unset($this->$offset);
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }
}
