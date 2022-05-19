<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'datetime')]
    private $date;

   
    #[ORM\Column(type: 'integer')]
    private $montant;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'commandes')]
    private $client;

    #[ORM\OneToOne(targetEntity: Paiement::class, cascade: ['persist', 'remove'])]
    private $paiement;


    #[ORM\Column(type: 'string', length: 255)]
    private $etat;

    #[ORM\Column(type: 'string', length: 255)]
    private $numero;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandesBurgers::class,cascade: ['persist'])]
    private $commandesBurgers;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandesMenus::class,cascade: ['persist'])]
    private $commandesMenuses;



    public function __construct()
    {
        $this->date = new DateTime();
        $this->commandesBurgers = new ArrayCollection();
        $this->commandesMenuses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

   
    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(int $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): self
    {
        $this->paiement = $paiement;

        return $this;
    }

  
   
  
   

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

  
   
    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * @return Collection<int, CommandesBurgers>
     */
    public function getCommandesBurgers(): Collection
    {
        return $this->commandesBurgers;
    }

    public function addCommandesBurger(CommandesBurgers $commandesBurger): self
    {
        if (!$this->commandesBurgers->contains($commandesBurger)) {
            $this->commandesBurgers[] = $commandesBurger;
            $commandesBurger->setCommande($this);
        }

        return $this;
    }

    public function removeCommandesBurger(CommandesBurgers $commandesBurger): self
    {
        if ($this->commandesBurgers->removeElement($commandesBurger)) {
            // set the owning side to null (unless already changed)
            if ($commandesBurger->getCommande() === $this) {
                $commandesBurger->setCommande(null);
            }
        }

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
            $commandesMenus->setCommande($this);
        }

        return $this;
    }

    public function removeCommandesMenus(CommandesMenus $commandesMenus): self
    {
        if ($this->commandesMenuses->removeElement($commandesMenus)) {
            // set the owning side to null (unless already changed)
            if ($commandesMenus->getCommande() === $this) {
                $commandesMenus->setCommande(null);
            }
        }

        return $this;
    }

  
}
