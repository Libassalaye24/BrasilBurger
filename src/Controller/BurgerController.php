<?php

namespace App\Controller;

use App\Entity\Burger;
use App\Entity\Image;
use App\Form\BurgerType;
use App\Repository\BurgerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class BurgerController extends AbstractController
{
    #[Route('/burger', name: 'list_burger')]
    public function index(BurgerRepository $burgerRepository,Request $request,PaginatorInterface $paginatorInterface): Response
    {
        $data = $burgerRepository->findBy(['etat' => false]);
        $burgers = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),2
        );
        return $this->render('burger/index.html.twig', [
            'controller_name' => 'BurgerController',
            'burgers' => $burgers,
        ]);
    }

    #[Route('/burger/archives', name: 'list_burger_archive')]
    public function archives(BurgerRepository $burgerRepository): Response
    {
        $burgers = $burgerRepository->findBy(['etat' => true]);
        return $this->render('burger/archives.html.twig', [
            'controller_name' => 'BurgerController',
            'burgers' => $burgers,
        ]);
    }

    #[Route('/burger/add', name: 'add_burger')]
    public function addBurger(Request $request,EntityManagerInterface $manager,SluggerInterface $slugger): Response
    {
     
        $burger = new Burger();
        $form = $this->createForm(BurgerType::class,$burger);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $brochureFile = $form->get('image')->get('nom')->getData();
           // dd($brochureFile);
            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move(
                        $this->getParameter('images_avatars'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $image = new Image();
                $image->setNom($newFilename);

                $burger->setImage($image);
                $manager->persist($burger);
                $manager->flush();
                
            }
            $this->redirectToRoute('list_burger');
        }
            return $this->render('burger/add.html.twig', [
                'controller_name' => 'BurgerController',
                'form' => $form->createView(),
            ]);
        


    }
    #[Route('/burger/edit/{id}', name: 'edit_burger')]
    public function editBurger(Burger $burger,Request $request,EntityManagerInterface $manager):Response
    {

        $form = $this->createForm(BurgerType::class,$burger);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            return $this->redirectToRoute('list_burger');
        }
        return $this->render('burger/add.html.twig', [
            'controller_name' => 'BurgerController',
            'form' => $form->createView(),
        ]);
    }
    #[Route('burger/changeEtat/{id}',name:'change_etat')]
    public function changeEtat(Burger $burger,Request $request,EntityManagerInterface $manager):Response
    {
        $etat = $burger->getEtat();
        if ($etat == false) {
            $burger->setEtat(true);
        }else {
            $burger->setEtat(false);
        }
        $manager->persist($burger);
        $manager->flush();
        return $this->redirectToRoute('list_burger');
        /* return $this->render('burger/add.html.twig', [
            'controller_name' => 'BurgerController'
        ]); */
    }

}