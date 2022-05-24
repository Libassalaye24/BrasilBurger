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
use Symfony\Component\HttpFoundation\Session\Session;
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

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(EntityManagerInterface $manager,Request $request,PaginatorInterface $paginatorInterface,CommandeRepository $commandeRepository,MenuRepository $menuRepository,ComplementRepository $complementRepository,BurgerRepository $burgerRepository): Response
    {
        /* $countCommande = $commandeRepository->findByExampleField();
        dd($countCommande); */
        
        // 2. Setup repository of some entity
        
        // 3. Query how many rows are there in the Articles table
        $now = new DateTime();
        $totalCommande = $commandeRepository->createQueryBuilder('c')
            ->select('count(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
        $totalCommandeEncours = $commandeRepository->findBy(['etat' => 'encours','dateCommande' => $now]);
        $totalCommandeAnnule = $commandeRepository->findBy(['etat' => 'annuler','dateCommande' => $now]);
        $totalCommandeValider = $commandeRepository->findBy(['etat' => 'valider','dateCommande' => $now]);
        $totalMenu = $menuRepository->findBy(['etat' => false]);
        $totalBurger = $burgerRepository->findBy(['etat' => false]);
        $totalComplement = $complementRepository->findBy(['etat' => false]);
        $commandeEncours = $totalCommandeEncours;
        return $this->render('menu/dashboard.html.twig', [
           'totalCommande' => $totalCommande,
           'totalCommandeEncours' => count($totalCommandeEncours),
           'commandeEncours' => $commandeEncours,
           'totalCommandeAnnuler' => count($totalCommandeAnnule),
           'totalCommandeValider' => count($totalCommandeValider),
           'totalComplement' => count($totalComplement),
           'totalBurger' => count($totalBurger),
           'totalMenu' => count($totalMenu),

        ]);
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
        $form = $this->createForm(MenuFormType::class,$menu);
        $form->handleRequest($request);
        $complements = $complementRepository->findBy(['etat' => false]);
        if ($form->isSubmitted() && $form->isValid()) {
            if ( $request->get('complement')) {
                $complementAdd = $request->get('complement');
                foreach ($complementAdd as $value) {
                    $oneComp = $complementRepository->find($value);
                    $menu->addComplement($oneComp);
                }
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
            'complements' => $complements
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
