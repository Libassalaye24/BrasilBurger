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
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ComplementController extends AbstractController
{
    #[Route('/complement', name: 'list_complement')]
    public function index(ComplementRepository $complementRepository,Request $request,PaginatorInterface $paginatorInterface): Response
    {
        $data = $complementRepository->findBy(['etat' => false]);
        $complements = $paginatorInterface->paginate(
            $data,$request->query->getInt('page', 1),4
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
    public function addComplement(Complement $complement=null,Request $request,EntityManagerInterface $manager, FileUploader $fileUploader, Session $session): Response
    {
        if (!$complement) {
            $compelment = new Complement();
        }
        $action = $request->attributes->get('_route');
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
            if ($action == 'add_complement') {
                $session->getFlashBag()->set('archiveC', 'Complement Num : ' . $complement->getId() . ' modifé avec succes');
            }else{
                $session->getFlashBag()->set('archiveC', 'complement Num : ' . $compelment->getId() . ' modifé avec succes');
            }
            return $this->redirectToRoute('list_complement');
        }
        return $this->render('complement/add.html.twig', [
            'controller_name' => 'ComplementController',
            'form' => $form->createView(),
        ]);
    }

    #[Route('complement/desarchive/{id}', name: 'archive_complement')]
    #[Route('complement/archive/{id}', name: 'desarchive_complement')]
    public function changeEtat(Request $request, EntityManagerInterface $manager, Session $session, ComplementRepository $complementRepository): Response
    {
        $action = $request->attributes->get('_route');
        $id = $request->attributes->get('id');
        $complement = $complementRepository->find($id);
        if (!$complement) {
            ///not found
        }
      //  dd($complement);
        $etat = $complement->getEtat();

        if ($action == 'archive_complement') {
            if (count($complement->getMenus()) > 0) {
                $session->getFlashBag()->set('errorArchiveC', 'Erreur : Ce complement est deja associé a un menu');
            } else {
                $complement->setEtat(true);
                $session->getFlashBag()->set('archiveC', 'complement archivé avec succes');
            }
        } else {
            $complement->setEtat(false);
            $session->getFlashBag()->set('desarchiveC', 'complement desarchivé avec succes');
        }
        $manager->persist($complement);
        $manager->flush();
        if ($etat == false) {
            return $this->redirectToRoute('list_complement');
        } else {
            return $this->redirectToRoute('list_complement_archive');
        }
    }
}
