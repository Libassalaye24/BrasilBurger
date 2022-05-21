<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Complement;
use App\Form\ComplementType;
use App\Service\FileUploader;
use App\Repository\ComplementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ComplementController extends AbstractController
{
    #[Route('/complement', name: 'list_complement')]
    public function index(ComplementRepository $complementRepository,Request $request,PaginatorInterface $paginatorInterface): Response
    {
        $data = $complementRepository->findBy(['etat' => false]);
        $complements = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),5
        );
        return $this->render('complement/index.html.twig', [
            'controller_name' => 'ComplementController',
            'complements' => $complements,
        ]);
    }

    #[Route('/complement/archives', name: 'list_complement_archive')]
    public function archives(ComplementRepository $complementRepository,Request $request,PaginatorInterface $paginatorInterface): Response
    {
        $data = $complementRepository->findBy(['etat' => true]);
        $complements = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),5
        );
        return $this->render('complement/archives.html.twig', [
            'controller_name' => 'ComplementController',
            'complements' => $complements,
        ]);
    }

    #[Route('/complement/add', name: 'add_complement')]
    #[Route('/complement/edit/{id}', name: 'edit_complement')]
    public function addComplement(Complement $complement=null,Request $request,EntityManagerInterface $manager, FileUploader $fileUploader): Response
    {
        if (!$complement) {
            $compelment = new Complement();
        }
       
        $form = $this->createForm(ComplementType::class,$compelment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->get('nom')->getData();
            $image = new Image();
          
            if ($imageFile) {
                $imageFileName = $fileUploader->upload($imageFile);
                $image->setNom($imageFileName);
            }
            $compelment->setImage($image);
            $manager->persist($compelment);
            $manager->flush();
            return $this->redirectToRoute('list_complement');
        }
        return $this->render('complement/add.html.twig', [
            'controller_name' => 'ComplementController',
            'form' => $form->createView(),
        ]);
    }

    #[Route('complement/changeEtat/{id}',name:'change_etat_cmpl')]
    public function changeEtat(Complement $complement,Request $request,EntityManagerInterface $manager):Response
    {
        $etat = $complement->getEtat();
        if ($etat == false) {
            $complement->setEtat(true);
        }else {
            $complement->setEtat(false);
        }
        $manager->persist($complement);
        $manager->flush();
        return $this->redirectToRoute('list_complement');
        
    }
}
