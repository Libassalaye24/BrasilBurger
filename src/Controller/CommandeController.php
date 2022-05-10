<?php

namespace App\Controller;

use DateTime;
use App\Entity\Client;
use App\Entity\Commande;
use App\Repository\BurgerRepository;
use App\Repository\ClientRepository;
use App\Repository\CommandeRepository;
use App\Repository\ComplementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommandeController extends AbstractController
{
    public const ENCOURS = 'en cours';
    public const VALIDER = 'valider';
    public const ANNULER = 'annuler';
    public const TERMINER = 'terminer';
    #[Route('/commande', name: 'list_commande')]
    public function index(CommandeRepository $commandeRepository,Session $session,ClientRepository $clientRepository,PaginatorInterface $paginatorInterface,Request $request): Response
    {
        $data = $commandeRepository->findAll();
        $clients = $clientRepository->findAll();
        $commandes = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),5
        );
        if ($session->has('clientSelected')) {
            $clientSelected = $session->get('clientSelected');
            //dd($etatSelected);
            $commandes = $commandeRepository->findBy([
                'client' => $clientSelected,
            ]);
            $session->remove('clientSelected');
            return $this->render('commande/list.html.twig', [
                'controller_name' => 'CommandeController',
                'commandes' => $commandes,
                'clients' => $clients,
                'clientSelected' => $clientSelected,
            ]);
        }
        return $this->render('commande/list.html.twig', [
            'controller_name' => 'CommandeController',
            'commandes' => $commandes,
            'clients' => $clients
        ]);
    }

    #[Route('/commande/mescommandes', name: 'mes_commandes')]
    public function mesCommandes(CommandeRepository $commandeRepository,PaginatorInterface $paginatorInterface,Request $request,SessionInterface $session): Response
    {
      
        $client = new Client();
        $client = $this->getUser();
        $data = $commandeRepository->findBy(['client' => $client/* ,'etat' => self::ENCOURS */]);
        $commandes = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),5
        );
        //dd($session->get('etatSelected'));
        if ($session->has('etatSelected')) {
            $etatSelected = $session->get('etatSelected');
            //dd($etatSelected);
            $commandes = $commandeRepository->findBy([
                'client' => $client,
                'etat' => $etatSelected
            ]);
            $session->remove('etatSelected');
            return $this->render('commande/mes.commande.html.twig', [
                'controller_name' => 'CommandeController',
                'commandes' => $commandes,
                'etatSelected' => $etatSelected,
            ]);
        }
      //  dd($commandes);
        return $this->render('commande/mes.commande.html.twig', [
            'controller_name' => 'CommandeController',
            'commandes' => $commandes,
        ]);
    }

    
    #[Route('/commande/commandeburger/add/{id}', name: 'add_commande_burger')]
    public function addCommande(int $id,BurgerRepository $burgerRepository,Request $request,ComplementRepository $complementRepository,EntityManagerInterface $manager, NotifierInterface $notifier): Response
    {
       if (!$this->getUser()) {
           return $this->redirectToRoute('login');
       }
        $commande = new Commande();
        $client = $this->getUser();
        if ($id) {
            $burger = $burgerRepository->find($id);
        }else{
            
        }
        $method = $request->getMethod();
        $complements = $complementRepository->findBy(['etat' => false]);
        if ($method == "POST") {
            $quantite = $request->get('quantite');
            $commande->setDate(new DateTime())
                    ->setClient($client)
                    ->setQuantite($quantite)
                    ->setBurger($burger);
            $prixComplement = $prixBurger = 0;
            if ($request->get('complements')) {
                foreach ($request->get('complements') as $key) {
                    $complement = $complementRepository->find($key);
                    $prixComplement += $complement->getPrix();
                    $burger->addComplement($complement);               
                }
                
            }
            $prixBurger = $burger->getPrix() * $quantite;
            $montant = $prixBurger + $prixComplement;
          //  dd($montant);
            $commande->setMontant($montant);
            $manager->persist($commande);
            $manager->flush();
            $notifier->send(new Notification('Commande ajouter avec succes.', ['browser']));
            return $this->redirectToRoute('mes_commandes');
        }
        // $burger->addComplement();
        // $burger->getComplements();
        return $this->render('commande/c.burger.html.twig', [
            'controller_name' => 'BurgerController',
            'burger' => $burger,
            'complements' => $complements
        ]);
    }

    #[Route('/commande/delete/{id}', name: 'delete_commande')]
    public function delete(int $id,CommandeRepository $commandeRepository,EntityManagerInterface $manager):Response
    {
        
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException(
                'No product found for id '.$id
            );
        }
        $manager->remove($commande);
        $manager->flush();
        $this->addFlash(
            'notice',
            'Commande supprimer avec succes!'
        );
        return $this->redirectToRoute('mes_commandes');
    }

    #[Route('/commande/updateEtat/{id}', name: 'valider_commande')]
    public function validerCommande(int $id,CommandeRepository $commandeRepository,EntityManagerInterface $manager):Response
    {
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException(
                'No product found for id '.$id
            );
        }
        if ($commande->getEtat() == 'en cours') {
           $commande->setEtat('valider');
        }/* else {
            $commande->setEtat('en cours');
        } */
        $manager->persist($commande);
        $manager->flush();
        return $this->redirectToRoute('list_commande');
    }

    #[Route('/commande/byEtat', name: 'commande_filtre_by_etat')]
    public function showCommandeByEtat(
                          CommandeRepository $repoComm,
                          SessionInterface $session,
                          Request $request ): Response
    {
    // dd(  $request->query->get('id'));
        if($request->query) {
         // dd(  $request->query->get('id'));
               $id = $request->query->get('id');
           
          //  $session->set("commandes", $commandes);
            $session->set("etatSelected", $id);
            //dd( $session->set("etatSelected", $id));
        
        }
        
  
        return new JsonResponse($this->generateUrl('mes_commandes'));
    }

    #[Route('/commande/byClient', name: 'commande_filtre_by_client')]
    public function showCommandeByClient(
                          CommandeRepository $repoComm,
                          SessionInterface $session,
                          Request $request ): Response
    {
    // dd(  $request->query->get('id'));
        if($request->query) {
         // dd(  $request->query->get('id'));
               $id = $request->query->get('id');
          //     $etat = $request->query->get('etat');
          //  $session->set("commandes", $commandes);
            $session->set("clientSelected", $id);
          //  $session->set("etat2Selected", $etat);
          //  dd( $session->set("etat2Selected", $id));
        
        }
        
  
        return new JsonResponse($this->generateUrl('list_commande'));
    }
}
