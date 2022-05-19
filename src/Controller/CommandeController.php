<?php

namespace App\Controller;

use DateTime;
use App\Entity\Client;
use App\Entity\Panier;
use App\Entity\Commande;
use App\Entity\Paiement;
use App\Service\Validator;
use App\Service\SmsGenerate;
use App\Entity\CommandesMenus;
use App\Entity\CommandesBurgers;
use Doctrine\ORM\Query\Expr\Func;
use App\Repository\MenuRepository;
use App\Repository\BurgerRepository;
use App\Repository\ClientRepository;
use App\Repository\PanierRepository;
use App\Repository\CommandeRepository;
use App\Repository\ComplementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommandeController extends AbstractController
{
    public const ENCOURS = 'encours';
    public const VALIDER = 'valider';
    public const ANNULER = 'annuler';
    public const TERMINER = 'terminer';
    #[IsGranted('ROLE_GESTIONNAIRE')]
    #[Route('/commande', name: 'list_commande')]
    public function index(CommandeRepository $commandeRepository, Session $session, ClientRepository $clientRepository, PaginatorInterface $paginatorInterface, Request $request): Response
    {
        $data = $commandeRepository->findBy(['etat' => self::ENCOURS]);
        $clients = $clientRepository->findAll();
        $commandes = $paginatorInterface->paginate(
            $data,
            $request->query->getInt('page', 1),
            5
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
        if ($session->has('etatSelected')) {
            $etatSelected = $session->get('etatSelected');
            //dd($etatSelected);
            if ($etatSelected == 'encours') {
                $commandes = $commandeRepository->findBy([
                    'etat' => $etatSelected,
                    'date' => new DateTime()
                ]);
            } else {
                $commandes = $commandeRepository->findBy([
                    'etat' => $etatSelected
                ]);
            }

            $session->remove('etatSelected');
            return $this->render('commande/list.html.twig', [
                'controller_name' => 'CommandeController',
                'commandes' => $commandes,
                'etatSelected' => $etatSelected,
            ]);
        }
        return $this->render('commande/list.html.twig', [
            'controller_name' => 'CommandeController',
            'commandes' => $commandes,
            'clients' => $clients
        ]);
    }

    #[IsGranted('ROLE_CLIENT')]
    #[Route('/panier', name: 'list_panier')]
    public function viewPanier(SessionInterface $session, HomeController $home, BurgerRepository $burgerRepository, MenuRepository $menuRepository): Response
    {
        $panier = $session->get('panier', []);
        $products = $home::getProducts($burgerRepository, $menuRepository);
        $panierWithData = [];
        foreach ($panier as $id => $quantite) {
            foreach ($products as $value) {
                if ($value->getId() == $id) {

                    if ($value->getType() == 'menu') {
                        $details = $menuRepository->find($id);
                    } elseif ($value->getType() == 'burger') {
                        $details = $burgerRepository->find($id);
                    }
                }
            }
            $panierWithData[] = [
                'product' => $details,
                'quantite' => $quantite
            ];
        }
        $total = $totalMenu = $montant = $totalBurger = 0;
        foreach ($panierWithData as $value) {
            if ($value['product']->getType() == 'burger') {
                $totalBurger += $value['product']->getPrix() * $value['quantite'];
            } else {
                foreach ($value['product']->getComplements() as $complement) {
                    $totalMenu += $complement->getPrix();
                }
                $montant += ($value['product']->getBurger()->getPrix() + $totalMenu) * $value['quantite'];
            }
        }
        $total = $totalBurger + $montant;
       // dd($panierWithData);

        return $this->render('commande/panier.html.twig', [
            'panierWithData' => $panierWithData,
            'total' => $total,
        ]);
    }

    #[IsGranted('ROLE_CLIENT')]
    #[Route('/panier/add/{id}', name: 'add_panier')]
    public function panier($id, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        if (!empty($panier[$id])) {
            $panier[$id]++;
        } else {
            $panier[$id] = 1;
        }
        $session->set('panier', $panier);
        /* $panierData = new Panier();
        foreach ($panier as $id => $quantite) {
            $panierData->setClient($this->getUser())
                ->setQuantity($quantite)
                ->setProduct($id);
            $manager->persist($panierData);
            $manager->flush();
        } */
        //dd($session->get('panier'));
        $session2 = new Session();
        $session2->getFlashBag()->add('produit', 'Produit Ajoutée avec succès');
        return $this->redirectToRoute('catalogue');
    }

    #[IsGranted('ROLE_CLIENT')]
    #[Route('/panier/add2/{id}', name: 'add_panier2')]
    public function panieradd($id, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        if (!empty($panier[$id])) {
            $panier[$id]++;
        } else {
            $panier[$id] = 1;
        }
        $session->set('panier', $panier);


        $session2 = new Session();
        $session2->getFlashBag()->set('produitremove', 'Produit Ajoutée avec succès');
        return $this->redirectToRoute('list_panier');
    }

    #[Route('/panier/remove/{id}', name: 'remove_panier')]
    public function removePanier($id, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        if (!empty($panier[$id])) {

            unset($panier[$id]);
        }
        $session->set('panier', $panier);

        /* foreach ($panier as $id => $quantite) {
          $panierData = $panierRepository->findBy(['product' => $id]);
            $manager->remove($panierData);
            $manager->flush();
        }
             */

        $session2 = new Session();
        $session2->getFlashBag()->set('produitremove', 'Le produit a été retiré du panier');
        return $this->redirectToRoute('list_panier');
    }
    #[IsGranted('ROLE_CLIENT')]
    #[Route('/panier/retire/{id}', name: 'retire_panier')]
    public function retirePanier($id, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        if (!empty($panier[$id])) {
            if ($panier[$id] > 1) {
                $panier[$id]--;
            } else {
                unset($panier[$id]);
            }
        }
        $session->set('panier', $panier);
        $session2 = new Session();
        $session2->getFlashBag()->set('produitremove', 'Le produit a été retiré du panier');
        return $this->redirectToRoute('list_panier');
    }

    #[IsGranted('ROLE_CLIENT')]
    #[Route('/mes.commandes', name: 'mes_commandes')]
    public function mesCommandes(CommandeRepository $commandeRepository, PaginatorInterface $paginatorInterface, Request $request, SessionInterface $session): Response
    {

        $client = new Client();
        $client = $this->getUser();
        /*  
        if ($client instanceof UserProviderInterface) {
             $repository->refreshUser($user);
        } */
        $data = $commandeRepository->findBy(['client' => $client, 'etat' => self::ENCOURS]);
        $commandes = $paginatorInterface->paginate(
            $data,
            $request->query->getInt('page', 1),
            5
        );
          
        //dd($data);
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
        // dd($commandes);
        return $this->render('commande/mes.commande.html.twig', [
            'controller_name' => 'CommandeController',
            'commandes' => $data,
        ]);
    }

    #[IsGranted('ROLE_CLIENT')]
    #[Route('/commande/add', name: 'checkOut')]
    public function checkOut(SessionInterface $session, EntityManagerInterface $manager, HomeController $home, BurgerRepository $burgerRepository, MenuRepository $menuRepository, Validator $smsGenerate, TexterInterface $texter): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('login');
        }
        $commande = new Commande();
        $client = $this->getUser();
        $panier = $session->get('panier', []);
        $products = $home::getProducts($burgerRepository, $menuRepository);
        $panierWithData = [];
        foreach ($panier as $id => $quantite) {
            foreach ($products as $value) {
                if ($value->getId() == $id) {

                    if ($value->getType() == 'menu') {
                        $details = $menuRepository->find($id);
                    } elseif ($value->getType() == 'burger') {
                        $details = $burgerRepository->find($id);
                    }
                }
            }
            $panierWithData[] = [
                'product' => $details,
                'quantite' => $quantite
            ];
        }
        $total = $totalMenu = $montant = $totalBurger = 0;
        foreach ($panierWithData as $value) {
            if ($value['product']->getType() == 'burger') {
                $totalBurger += $value['product']->getPrix() * $value['quantite'];
            } else {
                foreach ($value['product']->getComplements() as $complement) {
                    $totalMenu += $complement->getPrix();
                }
                $montant += ($value['product']->getBurger()->getPrix() + $totalMenu) * $value['quantite'];
            }
        }
        $total = $totalBurger + $montant;
        //dd($total);
        $commandeBurger = new CommandesBurgers();
        $commandeMenu = new CommandesMenus();
        $commande->setClient($client)
            ->setEtat(self::ENCOURS)
            ->setNumero($smsGenerate->genereNumCommande())
            ->setMontant($total);
        foreach ($panierWithData as $value) {
            if ($value['product']->getType() == 'menu') {
               // dump($value);
                $commandeMenu->setMenu($value['product'])
                            ->setQuantity($value['quantite'])
                            ->setCommande($commande);
                $commande->addCommandesMenus($commandeMenu);
            } else {
                //dump($value);
                $commandeBurger->setBurger($value['product'])
                            ->setQuantity($value['quantite'])
                            ->setCommande($commande);
                $commande->addCommandesBurger($commandeBurger);
                
            }
            $manager->persist($commande);
            $manager->flush();
        }
        //die(true);
      

        $panier = $session->set('panier', []);
        $session2 = new Session();
        $session2->getFlashBag()->add('add_commande', 'Commande Ajoutée avec succès');
        return $this->redirectToRoute('mes_commandes');
    }

    #[IsGranted(['ROLE_CLIENT','ROLE_GESTIONNAIRE'])]
    #[Route('/commande/details/{id}', name: 'details_commande')]
    public function detailsCommande(int $id, CommandeRepository $commandeRepository,SessionInterface $session): Response
    {
        $commande = $commandeRepository->find($id);
        if (!$commande) {
        }
        $roles = $session->get('roles');
        return $this->render('commande/details.html.twig', [
            'details' => $commande,
            'roles' => $roles,
        ]);
    }

    #[IsGranted('ROLE_CLIENT')]
    #[Route('/commande/delete/{id}', name: 'delete_commande')]
    public function delete(int $id, CommandeRepository $commandeRepository, EntityManagerInterface $manager): Response
    {

        $commande = $commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
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

    #[IsGranted('ROLE_GESTIONNAIRE')]
    #[Route('/commande/updateEtat/{id}', name: 'valider_commande')]
    public function validerCommande(int $id, CommandeRepository $commandeRepository, EntityManagerInterface $manager): Response
    {
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
            );
        }
        if ($commande->getEtat() == 'encours') {
            $commande->setEtat('valider');
        } else {
            $commande->setEtat('en cours');
        }
        $manager->persist($commande);
        $manager->flush();
        return $this->redirectToRoute('list_commande');
    }


    #[Route('/commande/byEtat', name: 'commande_filtre_by_etat')]
    public function showCommandeByEtat(
        CommandeRepository $repoComm,
        SessionInterface $session,
        Request $request
    ): Response {

        $roles = $session->get('roles');
        if ($request->query) {
            $etat = $request->query->get('etat');
            $session->set("etatSelected", $etat);
        }
        if ($roles[0] == 'ROLE_GESTIONNAIRE') {
            return new JsonResponse($this->generateUrl('list_commande'));
        } else {
            return new JsonResponse($this->generateUrl('mes_commandes'));
        }
    }


    #[Route('/commande/byClient', name: 'commande_filtre_by_client')]
    public function showCommandeByClient(
        CommandeRepository $repoComm,
        SessionInterface $session,
        Request $request
    ): Response {
        // dd(  $request->query->get('id'));
        if ($request->query) {
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

    #[IsGranted('ROLE_CLIENT')]
    #[Route('/commade/paiement/{id}', name: 'payer')]
    public function payer(int $id, Request $request, Session $session, CommandeRepository $commandeRepository, EntityManagerInterface $manager): Response
    {
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            //page not found
        }
        $paiement = new Paiement();
        $method = $request->getMethod();
        if ($method == "POST") {
            if ($request->request->get('valider')) {
                $montant = $request->request->get('montant');
                if (empty($montant)) {
                    return $this->render('commande/paiement.html.twig', [
                        'erreur' => 'Veillez remplir le champs',
                        'commande' => $commande,
                    ]);
                } else
                if (!is_numeric($montant)) {
                    return $this->render('commande/paiement.html.twig', [
                        'erreur' => 'Veillez saisir des nombres',
                        'commande' => $commande,
                    ]);
                } else
                if ($montant <= 0) {
                    return $this->render('commande/paiement.html.twig', [
                        'erreur' => 'Veillez saisir une valeur positive',
                        'commande' => $commande,

                    ]);
                } else
                if ($commande->getMontant() != $montant) {
                    return $this->render('commande/paiement.html.twig', [
                        'commande' => $commande,
                        'erreur' => 'Le valeur saisie est different du montant',
                    ]);
                }
                if ($commande->getMontant() == $montant) {
                    $paiement->setMontant($commande->getMontant());
                    $commande->setPaiement($paiement);
                    $commande->setEtat(self::TERMINER);
                    $manager->persist($paiement);
                    $manager->flush();
                    $session->getFlashBag()->add('paiement', 'Commande ( Num:' . $commande->getId() . ') Payée avec succès');
                    return $this->redirectToRoute('mes_commandes');
                }
            }
        }



        return $this->render('commande/paiement.html.twig', [
            'commande' => $commande,

        ]);
    }
}
