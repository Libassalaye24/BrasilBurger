<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\Image;
use App\Form\MenuFormType;
use App\Service\FileUploader;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MenuController extends AbstractController
{
    #[Route('/menu', name: 'list_menu')]
    public function index(MenuRepository $menuRepository,Request $request,PaginatorInterface $paginatorInterface): Response
    {
        $data = $menuRepository->findBy(['etat' => false]);
        $menus = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),5
        );
        return $this->render('menu/index.html.twig', [
            'controller_name' => 'MenuController',
            'menus' => $menus,
        ]);
    }

    #[Route('/menu/archives', name: 'list_menu_archive')]
    public function archives(MenuRepository $menuRepository,Request $request,PaginatorInterface $paginatorInterface): Response
    {
        $data = $menuRepository->findBy(['etat' => true]);
        $menus = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),5
        );
        return $this->render('menu/index.html.twig', [
            'controller_name' => 'MenuController',
            'menus' => $menus,
        ]);
    }

    #[Route('/menu/add', name: 'add_menu')]
    #[Route('/menu/edit/{id}', name: 'edit_menu')]
    public function addMenu(Menu $menu=null,Request $request,EntityManagerInterface $manager, FileUploader $fileUploader): Response
    {
        if (!$menu) {
            $menu = new Menu();
        }
        $form = $this->createForm(MenuFormType::class,$menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('image')->get('nom')->getData();
            $image = new Image();
            
            if ($imageFile) {
                $imageFileName = $fileUploader->upload($imageFile);
                $image->setNom($imageFileName);
            }
            $menu->setImage($image);
            $manager->persist($menu);
            $manager->flush();
            return $this->redirectToRoute('list_menu');
        }
        return $this->render('menu/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('menu/changeEtat/{id}',name:'change_etat_menu')]
    public function changeEtat(Menu $menu,Request $request,EntityManagerInterface $manager):Response
    {
        $etat = $menu->getEtat();
        if ($etat == false) {
            $menu->setEtat(true);
        }else {
            $menu->setEtat(false);
        }
        $manager->persist($menu);
        $manager->flush();
        return $this->redirectToRoute('list_menu');
        
    }

  
}
