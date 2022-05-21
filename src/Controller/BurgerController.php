<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Burger;
use App\Form\BurgerType;
use App\Service\FileUploader;
use App\Repository\BurgerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Session\Session;

class BurgerController extends AbstractController
{
    #[Route('/burger', name: 'list_burger')]
    public function index(BurgerRepository $burgerRepository,Request $request,PaginatorInterface $paginatorInterface): Response
    {
        $data = $burgerRepository->findBy(['etat' => false]);
        $burgers = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),5
        );
        return $this->render('burger/index.html.twig', [
            'controller_name' => 'BurgerController',
            'burgers' => $burgers,
        ]);
    }


    #[Route('/burger/details/{id}', name: 'burger_details')]
    public function burgerDetails(int $id,Request $request,BurgerRepository $burgerRepository):Response
    {
        if ($id) {
            $burger = $burgerRepository->find($id);
        }else{

        }
        return $this->render('burger/details.html.twig', [
            'burger' => $burger,
        ]);
    }

    #[Route('/burger/archives', name: 'list_burger_archive')]
    public function archives(BurgerRepository $burgerRepository,Request $request,PaginatorInterface $paginatorInterface): Response
    {
       
        $data = $burgerRepository->findBy(['etat' => true]);
        $burgers = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),5
        );
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
           return $this->redirectToRoute('list_burger');
        }
            return $this->render('burger/add.html.twig', [
                'controller_name' => 'BurgerController',
                'form' => $form->createView(),
            ]);
        


    }
    #[Route('/burger/edit/{id}', name: 'edit_burger')]
    public function editBurger(int $id,Request $request, FileUploader $fileUploader,EntityManagerInterface $manager,BurgerRepository $burgerRepository):Response
    {
        if (!$id) {
           
        }else {
            $burger = $burgerRepository->find($id);
        }
       
        $form = $this->createForm(BurgerType::class,$burger);
       // $image = new Image();
            //dd( $form->get('image')->get('nom')->getData());
          //  $image->setNom(new File($this->getParameter('images_avatars').'/'.$form->get('image')->get('nom')->getData()));
           // $pictureFile = new File($oldFileNamePath);
          //  $burger->setImage($image);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->get('nom')->getData();
            $image = new Image();
          
            if ($imageFile) {
                $imageFileName = $fileUploader->upload($imageFile);
                $image->setNom($imageFileName);
            }
            $burger->setImage($image);
            $manager->persist($burger);
            $manager->flush();
    
            return $this->redirectToRoute('list_burger');
        }
        return $this->render('burger/add.html.twig', [
            'controller_name' => 'BurgerController',
            'form' => $form->createView(),
        ]);
    }
    #[Route('burger/changeEtat/{id}',name:'change_etat')]
    public function changeEtat(Burger $burger,Request $request,EntityManagerInterface $manager,Session $session):Response
    {
        $etat = $burger->getEtat();
        
        if ($etat == false) {
            $burger->setEtat(true);
            $session->getFlashBag()->set('archive', 'Burger archivé avec succes');
        }else {
            $burger->setEtat(false);
            $session->getFlashBag()->set('desarchive', 'Burger desarchivé avec succes');
        }
        $manager->persist($burger);
        $manager->flush();
        if ($etat == false) {
            return $this->redirectToRoute('list_burger');
        }else{
            return $this->redirectToRoute('list_burger_archive');
        }
        
        
    }

}