<?php
namespace App\Service\panier;

use App\Controller\HomeController;
use App\Repository\BurgerRepository;
use App\Repository\MenuRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class PanierService
{

    protected $session;
    protected $home;
    protected $burgerRepository;
    protected $menuRepository;
    public function __construct(SessionInterface $session,HomeController $home,MenuRepository $menuRepository,BurgerRepository $burgerRepository)
    {
        $this->session = $session;
        $this->home = $home;
        $this->menuRepository = $menuRepository;
        $this->burgerRepository = $burgerRepository;
    }

    public function add($id)
    {
        $panier = $this->session->get('panier', []);
        if (!empty($panier[$id])) {
            $panier[$id]++;
        } else {
            $panier[$id] = 1;
        }
        $this->session->set('panier', $panier);
    }
    public function retire($id)
    {
        $panier = $this->session->get('panier', []);
        if (!empty($panier[$id])) {
            if ($panier[$id] > 1) {
                $panier[$id]--;
            }else{
                unset($panier);
            }
        }
        $this->session->set('panier', $panier);
    }
    public function remove($id,SessionInterface $session)
    {
        $panier = $this->session->get('panier', []);
        if (!empty($panier[$id])) {
            if ($panier[$id] > 1) {
                $panier[$id]--;
            }else{
                unset($panier);
            }
        }
        $session->set('panier', $panier);
    }
    public function getPanier():array
    {
        $panier = $this->session->get('panier', []);
        $products = $this->home::getProducts($this->burgerRepository, $this->menuRepository);
        $panierWithData = [];
        foreach ($panier as $id => $quantite) {
            foreach ($products as $value) {
                if ($value->getId() == $id) {

                    if ($value->getType() == 'menu') {
                        $details = $this->menuRepository->find($id);
                    } elseif ($value->getType() == 'burger') {
                        $details = $this->burgerRepository->find($id);
                    }
                }
            }
            $panierWithData[] = [
                'product' => $details,
                'quantite' => $quantite
            ];
        }
        return $panierWithData;
    }

    public function getTotal():float{
        $total = $totalMenu = $montant = $totalBurger = 0;
        $panierWithData = $this->getPanier();
        foreach ($panierWithData as $value) {
            if ($value['product']->getType() == 'burger') {
                $totalBurger += $value['product']->getPrix() * $value['quantite'];
            }else{
                foreach ($value['product']->getComplements() as $complement ) {
                   $totalMenu += $complement->getPrix();
                }
                $montant += $value['product']->getBurger()->getPrix() + $totalMenu * $value['quantite']; 
                
            }
            
        }
        $total = $totalBurger + $montant;
        return $total;
    }
}