<?php

namespace App\Controller;

use App\Entity\Complement;
use App\Entity\Image;
use App\Form\ComplementType;
use App\Repository\ComplementRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ComplementController extends AbstractController
{
    #[Route('/complement', name: 'list_complement')]
    public function index(ComplementRepository $complementRepository): Response
    {
        $complements = $complementRepository->findBy(['etat' => false]);
        return $this->render('complement/index.html.twig', [
            'controller_name' => 'ComplementController',
            'complements' => $complements,
        ]);
    }

    #[Route('/complement/archives', name: 'list_complement_archive')]
    public function archives(ComplementRepository $complementRepository): Response
    {
        $complements = $complementRepository->findBy(['etat' => true]);
        return $this->render('complement/index.html.twig', [
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
}
