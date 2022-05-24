<?php

namespace App\Controller;

use DateTime;
use App\Entity\Client;
use App\Entity\Panier;
use App\Entity\Commande;
use App\Entity\Paiement;
use App\Form\PaiementType;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommandeController extends AbstractController
{
    public const ENCOURS = 'encours';
    public const VALIDER = 'valider';
    public const ANNULER = 'annuler';
    public const TERMINER = 'terminer';

    #[IsGranted('ROLE_GESTIONNAIRE')]
    #[Route('/commande/list/{page?1}/{nbr?2}', name: 'list_commande')]
    public function index($page, $nbr, CommandeRepository $commandeRepository, Session $session, ClientRepository $clientRepository, PaginatorInterface $paginatorInterface, Request $request): Response
    {
        $clients = $clientRepository->findAll();


        if ($session->has('clientSelected')) {
            $clientSelected = $session->get('clientSelected');
            $oneClient = $clientRepository->find((int)$clientSelected);
            //  $etatSelected = $session->get('etatSelected');
            $commandes = $commandeRepository->findBy([
                'client' => $oneClient,
            ]);
            //dd($clientSelected);
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
            // dd($etatSelected);
            if ($etatSelected == 'encours') {
                $commandes = $commandeRepository->findBy([
                    'etat' => $etatSelected,
                    'dateCommande' => new DateTime()
                ], ['id' => "DESC"]);
            } else {
                $commandes = $commandeRepository->findBy([
                    'etat' => $etatSelected
                ], ['id' => "DESC"]);
            }

            $session->remove('etatSelected');
            //dd($commandes);
            return $this->render('commande/list.html.twig', [
                'controller_name' => 'CommandeController',
                'commandes' => $commandes,
                'etatSelected' => $etatSelected,
                'clients' => $clients,

            ]);
        }
        $data = $commandeRepository->findBy(['etat' => self::ENCOURS, 'dateCommande' => new DateTime()]);
        $commandes = $commandeRepository->findBy(['etat' => self::ENCOURS, 'dateCommande' => new DateTime()], ['id' => "DESC"], $nbr, ($page - 1) * $nbr);
        $nbrComandes = count($data);
        $nbrPage = ceil($nbrComandes / $nbr);
        // dump($nbr);
        return $this->render('commande/list.html.twig', [
            'controller_name' => 'CommandeController',
            'commandes' => $commandes,
            'clients' => $clients,
            'isPaginated'  => true,
            'nbrPage'      => $nbrPage,
            'page'         => $page,
            'nbr'          => $nbr

        ]);
    }

    #[IsGranted('ROLE_CLIENT')]
    #[Route('/panier', name: 'list_panier')]
    public function viewPanier(SessionInterface $session, HomeController $home, BurgerRepository $burgerRepository, MenuRepository $menuRepository, ComplementRepository $complementRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        $panier = $session->get('panier', []);
        $session->set('panierRestor', []);
        $products = $home::getProducts($burgerRepository, $menuRepository);
        $panierWithData = [];
        foreach ($panier as $id => $quantite) {
            //dd($id);
            foreach ($products as $value) {
                if (str_contains($id, 'burger')) {
                    if ($value->getId() == filter_var($id, FILTER_SANITIZE_NUMBER_INT)) {
                        $details = $burgerRepository->find(filter_var($id, FILTER_SANITIZE_NUMBER_INT));
                    }
                } elseif (str_contains($id, 'menu')) {
                    if ($value->getId() == filter_var($id, FILTER_SANITIZE_NUMBER_INT)) {
                        $details = $menuRepository->find(filter_var($id, FILTER_SANITIZE_NUMBER_INT));
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
        $complements = $complementRepository->findBy(['etat' => false]);
        return $this->render('commande/panier.html.twig', [
            'panierWithData' => $panierWithData,
            'total' => $total,
            'complements' => $complements
        ]);
    }

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
        // dd($panier);
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
        // dd($panier);
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
        $data = $commandeRepository->findBy(['client' => $client, 'etat' => self::ENCOURS, 'dateCommande' => new DateTime()]);
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
                'etat' => $etatSelected,
                'dateCommande' => new DateTime()
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
    #[Route('/commande/add', name: 'checkOut',methods:['POST'])]
    public function checkOut(SessionInterface $session, EntityManagerInterface $manager, HomeController $home, BurgerRepository $burgerRepository, MenuRepository $menuRepository, Validator $smsGenerate, TexterInterface $texter, Request $request, ComplementRepository $complementRepository): Response
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
                if (str_contains($id, 'burger')) {
                    if ($value->getId() == filter_var($id, FILTER_SANITIZE_NUMBER_INT)) {
                        $details = $burgerRepository->find(filter_var($id, FILTER_SANITIZE_NUMBER_INT));
                    }
                } elseif (str_contains($id, 'menu')) {
                    if ($value->getId() == filter_var($id, FILTER_SANITIZE_NUMBER_INT)) {
                        $details = $menuRepository->find(filter_var($id, FILTER_SANITIZE_NUMBER_INT));
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
        $method = $request->getMethod();
        if ($method == "POST") {
            //dd($request->request);
            $complement = $request->get('complement');
            $commande->setClient($client)
                ->setEtat(self::ENCOURS)
                ->setNumero($smsGenerate->genereNumCommande())
                ->setDateCommande(new DateTime());
                $prix2complemts = 0;
                if ($complement) {
                    
                    foreach ($complement as  $value) {
                        $compl = $complementRepository->find($value);
                        $prix2complemts = $prix2complemts + $compl->getPrix();  
                        
                        
                        $commande->addComplement($complementRepository->find($value));
                    }
                }
            $commande->setMontant($total+$prix2complemts);
            foreach ($panierWithData as $value) {
                if ($value['product']->getType() == 'menu') {

                    $commande->addMenu($value['product']);
                } else {

                    $commande->addBurger($value['product']);
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
    }

    #[Route('/commande/details/{id}', name: 'details_commande')]
    public function detailsCommande(int $id, CommandeRepository $commandeRepository, SessionInterface $session): Response
    {
        $commande = $commandeRepository->find($id);
        if (!$commande) {
        }
        $user = $this->getUser();
        $role = $user->getRoles();
        $roles = $session->get('roles');
        return $this->render('commande/details.html.twig', [
            'details' => $commande,
            'roles' => $roles,
            'role' => $role,
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
    #[Route('/commande/valider/{id}', name: 'valider_commande')]
    #[Route('/commande/annuler/{id}', name: 'annuler_commande')]
    public function validerCommande(Request $request, CommandeRepository $commandeRepository, EntityManagerInterface $manager, Session $session): Response
    {
        $action = $request->attributes->get('_route');
        $id = $request->attributes->get('id');
        ///   dd($action);
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException(
                'No product found for id ' . $id
            );
        }
        if ($action == 'valider_commande') {
            $commande->setEtat('valider');
            $session->getFlashBag()->set('valideCommande', 'Commande ' . 'Num: ' . $commande->getNumero() . ' Validée avec succées');
        } elseif ($action == 'annuler_commande') {
            $session->getFlashBag()->set('valideCommande', 'Commande ' . 'Num: ' . $commande->getNumero() . ' annulée avec succées');
            $commande->setEtat('annuler');
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
        if ($request->isXmlHttpRequest()) {
            $etat = $request->query->get('etat');
            $session->set("etatSelected", $etat);
        }
        // dd($roles);
        if ($roles[0] == 'ROLE_GESTIONNAIRE') {
            return new JsonResponse($this->generateUrl('list_commande'));
        } elseif ($roles[0] == 'ROLE_CLIENT') {
            return new JsonResponse($this->generateUrl('mes_commandes'));
        }
    }

    #[Route('/commande/checkOutComplements', name: 'checkOutComplements')]
    public function complementsCheckOut(
        CommandeRepository $repoComm,
        SessionInterface $session,
        Request $request
    ): Response {

        /* $roles = $session->get('roles');
        if ($request->query) {
            $etat = $request->query->get('etat');
            $session->set("etatSelected", $etat);
        }
       // dd($roles);
        if ($roles[0] == 'ROLE_GESTIONNAIRE') {
            return new JsonResponse($this->generateUrl('list_commande'));
        } else {
            return new JsonResponse($this->generateUrl('mes_commandes'));
        } */
        return new JsonResponse($this->generateUrl('jjjjj'));
    }


    #[Route('/commande/byClient', name: 'commande_filtre_by_client')]
    public function showCommandeByClient(
        CommandeRepository $repoComm,
        SessionInterface $session,
        Request $request
    ): Response {

        if ($request->query) {
            $client = $request->query->get('client');
            $session->set("clientSelected", $client);
        }


        return new JsonResponse($this->generateUrl('list_commande'));
    }

    #[IsGranted('ROLE_CLIENT')]
    #[Route('/commade/paiement', name: 'payer')]
    public function payer(Request $request, Session $session, CommandeRepository $commandeRepository, EntityManagerInterface $manager): Response
    {
        $commande = $commandeRepository->findBy(['client' => $this->getUser(), 'paiement' => null, 'etat' => self::VALIDER]);

        $paiement = new Paiement();
        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $numero = $request->get('numero');
            if ($numero == '0') {
                return $this->render('commande/paiement.html.twig', [
                    'error' => 'Champs obligatoire',
                    'form' => $form->createView(),
                    'commande' => $commande,
                ]);
            }
            $montant = $request->get('paiement')['montant'];

            //die('l');
            $commandePaye = $commandeRepository->find($numero);
            if ($commandePaye->getMontant() != $montant) {
                return $this->render('commande/paiement.html.twig', [
                    'errorMontant' => 'Le montant saisie est different de la commande selectionee',
                    'form' => $form->createView(),
                    'commande' => $commande,
                    'isSelected' => $numero,
                ]);
            }
            $paiement->setMontant($commandePaye->getMontant());
            $commandePaye->setPaiement($paiement);
            $commandePaye->setEtat(self::TERMINER);
            $manager->persist($paiement);
            $manager->flush();
            $session->getFlashBag()->add('paiement', 'Commande ( Num:' . $commandePaye->getNumero() . ') Payée avec succès');
            return $this->redirectToRoute('mes_commandes');
        }

        return $this->render('commande/paiement.html.twig', [
            'form' => $form->createView(),
            'commande' => $commande,
        ]);
    }
}
