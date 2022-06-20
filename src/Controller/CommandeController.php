<?php

namespace App\Controller;

use DateTime;
use Dompdf\Dompdf;
use Dompdf\Options;
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
use App\Repository\UserRepository;
use App\Service\pdf\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use function PHPUnit\Framework\throwException;
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
use Symfony\Component\HttpFoundation\RedirectResponse;

class CommandeController extends AbstractController
{
    public const ENCOURS = 'encours';
    public const VALIDER = 'valider';
    public const ANNULER = 'annuler';
    public const TERMINER = 'terminer';

    #[IsGranted('ROLE_GESTIONNAIRE')]
    #[Route('/commande/list/{page?1}/{nbr?5}', name: 'list_commande')]
    public function index($page, $nbr, CommandeRepository $commandeRepository, Session $session, ClientRepository $clientRepository, PaginatorInterface $paginatorInterface, Request $request): Response
    {
        $clients = $clientRepository->findAll();
        $now = new DateTime();

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
                'now' => $now,
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
                'now' => $now,
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
            'nbr'          => $nbr,
            'now' => $now,

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
    #[Route('/commande/mescommandes', name: 'mes_commandes')]
    public function mesCommandes(CommandeRepository $commandeRepository, ClientRepository $clientRepository, PaginatorInterface $paginatorInterface, Request $request, SessionInterface $session, Session $session2, EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted("ROLE_CLIENT");
        $user = $request->getSession()->get('idUser');
        $email = $request->getSession()->get('email');

        $client = $clientRepository->findOneBy(['email' => $email]);
     //   dd($request->getSession()->get('idUser'));
        $data = $commandeRepository->findBy(['client' => $client, 'etat' => self::ENCOURS, 'dateCommande' => new DateTime()]);
        $commandes = $paginatorInterface->paginate(
            $data,
            $request->query->getInt('page', 1),
            5
        );

        //dd($data);
        if ($session->has('etatSelected')) {
            $etatSelected = $session->get('etatSelected');
            if ($etatSelected == 'encours') {
                $commandes = $commandeRepository->findBy([
                    'client' => $client,
                    'etat' => $etatSelected,
                    'dateCommande' => new DateTime(),
                ]);
            } elseif ($etatSelected == 'valider') {
                $commandes = $commandeRepository->findBy([
                    'client' => $client,
                    'etat' => $etatSelected,
                    'dateCommande' => new DateTime(),
                ]);
            } else {
                $commandes = $commandeRepository->findBy([
                    'client' => $client,
                    'etat' => $etatSelected,

                ]);
            }


            $session->remove('etatSelected');
            return $this->render('commande/mes.commande.html.twig', [
                'controller_name' => 'CommandeController',
                'commandes' => $commandes,
                'etatSelected' => $etatSelected,
            ]);
        }
        $method = $request->getMethod();
        if ($method == "POST") {


            if ($request->get('payer')) {
                if (!$request->get('tabChecks') && !$request->get('isTrue')) {
                    $session2->getFlashBag()->add('invalide_paye', 'Veillez selectionner d\'abord');
                    return $this->render('commande/mes.commande.html.twig', [
                        'controller_name' => 'CommandeController',
                        'commandes' => $data,
                    ]);
                }
                if ($request->get('tabChecks') && !$request->get('isTrue')) {
                    $session2->getFlashBag()->add('invalide_paye', 'Seule les commandes  valides sont autorisées a payer');
                    return $this->render('commande/mes.commande.html.twig', [
                        'controller_name' => 'CommandeController',
                        'commandes' => $data,
                    ]);
                }
                $allCommandes = [];
                $tabsCheck = $request->get('tabChecks');
                /*  if (!$tabsCheck) {
                return $this->render('commande/mes.commande.html.twig', [
                    'controller_name' => 'CommandeController',
                    'commandes' => $data,
                ]);
            } */
                foreach ($tabsCheck as $value) {
                    $OneCommande = $commandeRepository->find($value);
                    $allCommandes[] = $OneCommande;
                }
                // dd($allCommandes);
                $session->set('allCommandes', $tabsCheck);

                return $this->redirectToRoute('payer');
            }
            if ($request->get('details')) {
                $id = $request->get('details');
                //dd($id);
                $commande = $commandeRepository->find($id);
                $roles = $session->get('roles');
                return $this->render('commande/details.html.twig', [
                    'details' => $commande,
                    'roles' => $roles
                ]);
              //  return $this->redirectToRoute("details_commande",["id" => $id]);
            }
        }
        return $this->render('commande/mes.commande.html.twig', [
            'controller_name' => 'CommandeController',
            'commandes' => $data,
        ]);
    }

    #[IsGranted('ROLE_CLIENT')]
    #[Route('/commande/add', name: 'checkOut', methods: ['POST'])]
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
       // dd($panierWithData);

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
            $commande->setMontant($total + $prix2complemts);
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
    public function detailsCommande($id, CommandeRepository $commandeRepository, Session $session): Response
    {
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException(
                'No commande found for id ' . $id
            );
        }

        $roles = $session->get('roles');
        return $this->render('commande/details.html.twig', [
            'details' => $commande,
            'roles' => $roles
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
    // #[Route('/commande/valider', name: 'valider_commande')]
    #[Route('/commande/changeEtat', name: 'valider_commande')]
    public function validerCommande(Request $request, CommandeRepository $commandeRepository, EntityManagerInterface $manager, Session $session): Response
    {
        if ($request->getMethod() == 'POST') {

            $allCommandes = $request->get('commandesAll');
            if ($allCommandes) {
                foreach ($allCommandes as $value) {
                    $oneCommande = $commandeRepository->find($value);
                    if ($request->get('changeEtat') == 'valider') {
                        $oneCommande->setEtat('valider');
                    } else {
                        $oneCommande->setEtat('annuler');
                    }
                    // dd($oneCommande);
                    $manager->persist($oneCommande);
                    $manager->flush();
                }
                $session->getFlashBag()->set('valideCommande', 'Traitement reussi avec succées');
            } else {
                $session->getFlashBag()->set('invalideCommande', 'Pour effectuer une operation, veillez selectionner des champs d\'abord');
            }
        }

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
        if ($roles == 'ROLE_GESTIONNAIRE') {
            return new JsonResponse($this->generateUrl('list_commande'));
        } elseif ($roles == 'ROLE_CLIENT') {
            return new JsonResponse($this->generateUrl('mes_commandes'));
        }
    }

    #[Route('/commande/paiementAll', name: 'commande_paiement')]
    public function complementsCheckOut(
        CommandeRepository $repoComm,
        SessionInterface $session,
        Request $request
    ): Response {

        if ($request->isXmlHttpRequest()) {
            dd($request);
        }

        return new JsonResponse($this->generateUrl('payer'));
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
    public function payer(Request $request, Session $session, CommandeRepository $commandeRepository, EntityManagerInterface $manager, PdfService $pdfService): Response
    {
        $method = $request->getMethod();
        $tabsCheck = $session->get('allCommandes');

        $allCommandes = [];
        $montant = 0;
        //dd($tabsCheck);
        foreach ($tabsCheck as $value) {
            $OneCommande = $commandeRepository->find($value);
            $allCommandes[] = $OneCommande;
        }

        $session->set('factureClient', $tabsCheck);
        //dd($allCommandes);
        if ($method == "POST") {
            if (count($allCommandes) > 0) {

                foreach ($allCommandes as $value) {
                    $paiement = new Paiement();
                    $value->setEtat(self::TERMINER);
                    $paiement->setCommande($value);
                    $paiement->setMontant($value->getMontant());
                    $manager->persist($paiement);
                    $manager->flush();
                }

                $session->set('allCommandes', []);
                $session->getFlashBag()->add('paiement', 'Commande(s) payée(s) avec succès ');
                return $this->redirectToRoute('mes_commandes');
            }
        }


        return $this->render('commande/paiement.html.twig', [
            'allCommandes' =>  $allCommandes
        ]);
    }

    #[Route('/commade/factureClient', name: 'facture')]
    public function factureClient(PdfService $pdfService, Session $session, Request $request, CommandeRepository $commandeRepository): Response
    {

        $tabsCheck = $session->get('factureClient');
        $allCommandes = [];
        foreach ($tabsCheck as $value) {
            $OneCommande = $commandeRepository->find($value);
            $allCommandes[] = $OneCommande;
        }
        // dd($allCommandes[0]->getBurger());
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('commande/facturepdf.html.twig', [
            'title' => "Facture Brasil Burger",
            'commandes' => $allCommandes,
            'date' => new DateTime(),
        ]);

        $pdfService->showPdfFile($html);
        return $this->redirectToRoute('mes_commandes');
    }

    #[Route('/commande/recette', name: 'recette')]
    public function recettePdf(PdfService $pdfService, CommandeRepository $commandeRepository): Response
    {
        $commandeTerminer =  $commandeRepository->findBy(['etat' => self::TERMINER, 'dateCommande' => new DateTime()]);
        $html = $this->renderView('commande/recettePdf.html.twig', [
            'title' => "Recettes Journalieres Brasil Burger",
            'commandes' => $commandeTerminer,
            'date' => new DateTime(),
        ]);

        $pdfService->showPdfFile($html);
        return $this->redirectToRoute('dashbord');
    }
}
