<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $nom;

    #[ORM\ManyToOne(targetEntity: Burger::class, inversedBy: 'menus')]
    private $burger;

    #[ORM\ManyToMany(targetEntity: Complement::class, inversedBy: 'menus')]
    private $complements;

    #[ORM\OneToOne(targetEntity: Image::class, cascade: ['persist', 'remove'])]
    private $image;

    #[ORM\Column(type: 'boolean')]
    private $etat;

    #[ORM\Column(type: 'string', length: 255)]
    private $type;

    #[ORM\OneToMany(mappedBy: 'menu', targetEntity: CommandesMenus::class)]
    private $commandesMenuses;

    public function __construct()
    {
        $this->complements = new ArrayCollection();
        $this->etat = false;
        $this->type = 'menu';
        $this->commandesMenuses = new ArrayCollection();
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

   

    public function getBurger(): ?Burger
    {
        return $this->burger;
    }

    public function setBurger(?Burger $burger): self
    {
        $this->burger = $burger;

        return $this;
    }

    /**
     * @return Collection<int, Complement>
     */
    public function getComplements(): Collection
    {
        return $this->complements;
    }

    public function addComplement(Complement $complement): self
    {
        if (!$this->complements->contains($complement)) {
            $this->complements[] = $complement;
        }

        return $this;
    }

    public function removeComplement(Complement $complement): self
    {
        $this->complements->removeElement($complement);

        return $this;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getEtat(): ?bool
    {
        return $this->etat;
    }

    public function setEtat(bool $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

   

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

  

    /**
     * @return Collection<int, CommandesMenus>
     */
    public function getCommandesMenuses(): Collection
    {
        return $this->commandesMenuses;
    }

    public function addCommandesMenus(CommandesMenus $commandesMenus): self
    {
        if (!$this->commandesMenuses->contains($commandesMenus)) {
            $this->commandesMenuses[] = $commandesMenus;
            $commandesMenus->setMenu($this);
        }

        return $this;
    }

    public function removeCommandesMenus(CommandesMenus $commandesMenus): self
    {
        if ($this->commandesMenuses->removeElement($commandesMenus)) {
            // set the owning side to null (unless already changed)
            if ($commandesMenus->getMenu() === $this) {
                $commandesMenus->setMenu(null);
            }
        }

        return $this;
    }

   
}
