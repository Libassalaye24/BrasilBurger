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

    #[ORM\Column(type: 'string', length: 255)]
    private $etat;

    #[ORM\Column(type: 'string', length: 255)]
    private $numero;

  

    #[ORM\Column(type: 'date')]
    private $dateCommande;

    #[ORM\ManyToMany(targetEntity: Burger::class, inversedBy: 'commandes')]
    private $burger;

    #[ORM\ManyToMany(targetEntity: Menu::class, inversedBy: 'commandes')]
    private $menu;

    #[ORM\ManyToMany(targetEntity: Complement::class, inversedBy: 'commandes')]
    private $complement;

    #[ORM\OneToOne(mappedBy: 'commande', targetEntity: Paiement::class, cascade: ['persist', 'remove'])]
    private $paiement;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandesBurger::class)]
    private $commandesBurgers;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandesMenu::class)]
    private $commandesMenus;

    
 




    public function __construct()
    {
        $this->date = new DateTime();
        $this->burger = new ArrayCollection();
        $this->menu = new ArrayCollection();
        $this->complement = new ArrayCollection();
        $this->commandesBurgers = new ArrayCollection();
        $this->commandesMenus = new ArrayCollection();
      
    }
    public function __toString()
    {
        return $this->montant;
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

    
    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeInterface $dateCommande): self
    {
        $this->dateCommande = $dateCommande;

        return $this;
    }

    /**
     * @return Collection<int, Burger>
     */
    public function getBurger(): Collection
    {
        return $this->burger;
    }

    public function addBurger(Burger $burger): self
    {
        if (!$this->burger->contains($burger)) {
            $this->burger[] = $burger;
        }

        return $this;
    }

    public function removeBurger(Burger $burger): self
    {
        $this->burger->removeElement($burger);

        return $this;
    }

    /**
     * @return Collection<int, Menu>
     */
    public function getMenu(): Collection
    {
        return $this->menu;
    }

    public function addMenu(Menu $menu): self
    {
        if (!$this->menu->contains($menu)) {
            $this->menu[] = $menu;
        }

        return $this;
    }

    public function removeMenu(Menu $menu): self
    {
        $this->menu->removeElement($menu);

        return $this;
    }

    /**
     * @return Collection<int, Complement>
     */
    public function getComplement(): Collection
    {
        return $this->complement;
    }

    public function addComplement(Complement $complement): self
    {
        if (!$this->complement->contains($complement)) {
            $this->complement[] = $complement;
        }

        return $this;
    }

    public function removeComplement(Complement $complement): self
    {
        $this->complement->removeElement($complement);

        return $this;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): self
    {
        // unset the owning side of the relation if necessary
        if ($paiement === null && $this->paiement !== null) {
            $this->paiement->setCommande(null);
        }

        // set the owning side of the relation if necessary
        if ($paiement !== null && $paiement->getCommande() !== $this) {
            $paiement->setCommande($this);
        }

        $this->paiement = $paiement;

        return $this;
    }

    /**
     * @return Collection<int, CommandesBurger>
     */
    public function getCommandesBurgers(): Collection
    {
        return $this->commandesBurgers;
    }

    public function addCommandesBurger(CommandesBurger $commandesBurger): self
    {
        if (!$this->commandesBurgers->contains($commandesBurger)) {
            $this->commandesBurgers[] = $commandesBurger;
            $commandesBurger->setCommande($this);
        }

        return $this;
    }

    public function removeCommandesBurger(CommandesBurger $commandesBurger): self
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
     * @return Collection<int, CommandesMenu>
     */
    public function getCommandesMenus(): Collection
    {
        return $this->commandesMenus;
    }

    public function addCommandesMenu(CommandesMenu $commandesMenu): self
    {
        if (!$this->commandesMenus->contains($commandesMenu)) {
            $this->commandesMenus[] = $commandesMenu;
            $commandesMenu->setCommande($this);
        }

        return $this;
    }

    public function removeCommandesMenu(CommandesMenu $commandesMenu): self
    {
        if ($this->commandesMenus->removeElement($commandesMenu)) {
            // set the owning side to null (unless already changed)
            if ($commandesMenu->getCommande() === $this) {
                $commandesMenu->setCommande(null);
            }
        }

        return $this;
    }

  

  

  

  
}
