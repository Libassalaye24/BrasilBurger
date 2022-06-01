<?php

namespace App\Controller;

use DateTime;
use App\Entity\Menu;
use App\Entity\Image;
use App\Entity\Commande;
use App\Form\MenuFormType;
use App\Service\FileUploader;
use App\Repository\MenuRepository;
use App\Repository\BurgerRepository;
use App\Repository\CommandeRepository;
use App\Repository\ComplementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MenuController extends AbstractController
{
    #[Route('/menu', name: 'list_menu')]
    public function index(MenuRepository $menuRepository,Request $request,PaginatorInterface $paginatorInterface): Response
    {
        $data = $menuRepository->findBy(['etat' => false]);
        $menus = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),4
        );
        return $this->render('menu/index.html.twig', [
            'controller_name' => 'MenuController',
            'menus' => $menus,
        ]);
    }

  
    #[Route('/dashboard/{page?1}/{nbr?5}', name: 'dashboard')]
    public function dashboard($page,$nbr,EntityManagerInterface $manager,Request $request,PaginatorInterface $paginatorInterface,CommandeRepository $commandeRepository,MenuRepository $menuRepository,ComplementRepository $complementRepository,BurgerRepository $burgerRepository,Session $session): Response
    {
       
        $now = new DateTime();
        $totalMenu = $menuRepository->findBy(['etat' => false]);
        $totalCommande = $commandeRepository->createQueryBuilder('c')
            ->select('count(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
        //$topSelecting= $commandeRepository->findBy(['etat' => 'encours']);
      /*   $tab = [];
        foreach ($topSelecting as $value) {
           $tab[] = $value->getMenu();
        } */
        /* $count = 0;
        foreach ($topSelecting as $value) {
           // dd($value->getMenu());
            foreach ($value->getMenu() as $key ) {
               
                foreach ($totalMenu as $menu) {
                    if ($key->getBurger() == $menu->getBurger()) {
                       $count++;
                    }
                    if ($count == 4) {
                        dd($key->getBurger());
                     }
                   
                }
               
            }
        } */
        $totalCommandeEncours = $commandeRepository->findBy(['etat' => 'encours','dateCommande' => $now]);
        $totalCommandeAnnule = $commandeRepository->findBy(['etat' => 'annuler']);
        $totalCommandeValider = $commandeRepository->findBy(['etat' => 'valider','dateCommande' => $now]);
       
        $totalBurger = $burgerRepository->findBy(['etat' => false]);
        $totalComplement = $complementRepository->findBy(['etat' => false]);
        $data =$totalCommandeEncours;
        $commandeEncours = $commandeRepository->findBy(['etat' => 'encours','dateCommande' => $now],['id' => 'DESC'], $nbr, ($page - 1) * $nbr);
        $nbrComandes = count($data);
        $nbrPage = ceil($nbrComandes / $nbr);

        //filter
        if ($session->has('selectEtat')) {
            $etatSelected = $session->get('selectEtat');
            //dd($etatSelected);
            $commandeEncours = $commandeRepository->findBy(['etat' => $etatSelected,'dateCommande' => $now],['id' => 'DESC']);
            $session->remove('selectEtat');
            return $this->render('menu/dashboard.html.twig', [
                'totalCommande' => $totalCommande,
                'totalCommandeEncours' => count($totalCommandeEncours),
                'commandeEncours' => $commandeEncours,
                'totalCommandeAnnuler' => count($totalCommandeAnnule),
                'totalCommandeValider' => count($totalCommandeValider),
                'totalComplement' => count($totalComplement),
                'totalBurger' => count($totalBurger),
                'totalMenu' => count($totalMenu),
                'isPaginated'  => false,
                'nbrPage'      => $nbrPage,
                'page'         => $page,
                'nbr'          => $nbr,
                'etatSelected' => $etatSelected,
     
             ]);
         }
 
         //
        return $this->render('menu/dashboard.html.twig', [
           'totalCommande' => $totalCommande,
           'totalCommandeEncours' => count($totalCommandeEncours),
           'commandeEncours' => $commandeEncours,
           'totalCommandeAnnuler' => count($totalCommandeAnnule),
           'totalCommandeValider' => count($totalCommandeValider),
           'totalComplement' => count($totalComplement),
           'totalBurger' => count($totalBurger),
           'totalMenu' => count($totalMenu),
           'isPaginated'  => true,
           'nbrPage'      => $nbrPage,
           'page'         => $page,
           'nbr'          => $nbr

        ]);
    }

    #[Route('/menu/dashboard/etat', name: 'commande_dashboard_by_etat')]
    public function CommandeByEtat(
        CommandeRepository $repoComm,
        SessionInterface $session,
        Request $request
    ): Response {

      //  dd( $request->query->get('etat'));
        if ($request->isXmlHttpRequest()) {
            $etat = $request->query->get('etat');
            $session->set("selectEtat", $etat);
        }
        
        return new JsonResponse($this->generateUrl('dashboard'));
        
    }
    #[Route('/menu/archives', name: 'list_menu_archive')]
    public function archives(MenuRepository $menuRepository,Request $request,PaginatorInterface $paginatorInterface): Response
    {
        $data = $menuRepository->findBy(['etat' => true]);
        $menus = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),4
        );
        return $this->render('menu/archives.html.twig', [
            'controller_name' => 'MenuController',
            'menus' => $menus,
        ]);
    }

    #[Route('/menu/add', name: 'add_menu')]
    #[Route('/menu/edit/{id}', name: 'edit_menu')]
    public function addMenu(Menu $menu=null,Request $request,EntityManagerInterface $manager, FileUploader $fileUploader,ComplementRepository $complementRepository, Session $session): Response
    {
        if (!$menu) {
            $menu = new Menu();
        }

        $action = $request->attributes->get('_route');
        $restor = [];
        if ($action == 'edit_menu') {
           $restor []= $menu;
        }
        $form = $this->createForm(MenuFormType::class,$menu);
        $form->handleRequest($request);
        $complements = $complementRepository->findBy(['etat' => false]);
        if ($form->isSubmitted() && $form->isValid()) {
            //dd($request->get('menu_form')['complement']);
           // dd($form);
            if ( $request->get('menu_form')['complement'] ) {
               
            }else{
                //errrorComplements
                return $this->render('menu/add.html.twig', [
                    'form' => $form->createView(),
                    'complements' => $complements,
                    'errorComplement' => "Veillez selectionner un complement",
                ]);
            }
           // dd(false);
            $imageFile = $form->get('image')->get('nom')->getData();
            $image = new Image();
          
            if ($imageFile) {
                $imageFileName = $fileUploader->upload($imageFile);
                $image->setNom($imageFileName);
            }
            $menu->setImage($image);
            $manager->persist($menu);
            $manager->flush();
            if ($action == 'add_menu') {
                $session->getFlashBag()->set('archiveM', 'Menu Num : ' . $menu->getId() . ' ajouté avec succes');
            }else{
                $session->getFlashBag()->set('archiveM', 'Menu Num : ' . $menu->getId() . ' modifé avec succes');
            }
            return $this->redirectToRoute('list_menu');
        }
        return $this->render('menu/add.html.twig', [
            'form' => $form->createView(),
            'complements' => $complements,
            'restor' => $restor,
            'action' => $action,
        ]);
    }
    #[Route('menu/desarchive/{id}', name: 'desarchive_menu')]
    #[Route('menu/archive/{id}', name: 'archive_menu')]
    public function changeEtat(Request $request, EntityManagerInterface $manager, Session $session, MenuRepository $menuRepository): Response
    {
        $action = $request->attributes->get('_route');
        $id = $request->attributes->get('id');
        $menu = $menuRepository->find((int)$id);
       
        if (!$menu) {
            ///not found
        }
        $etat = $menu->getEtat();

        if ($action == 'archive_menu') {
           
            $menu->setEtat(true);
            $session->getFlashBag()->set('archiveM', 'Succes : Menu archivé avec succes');
            
        } else {
            $menu->setEtat(false);
            $session->getFlashBag()->set('desarchiveM', 'Succes : Menu desarchivé avec succes');
        }
        $manager->persist($menu);
        $manager->flush();
        if ($etat == false) {
            return $this->redirectToRoute('list_menu');
        } else {
            return $this->redirectToRoute('list_menu_archive');
        }
    }
  
}
