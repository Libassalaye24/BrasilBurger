<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\Image;
use App\Entity\Burger;
use App\Form\ImageType;
use App\Form\BurgerType;
use App\Entity\Complement;
use App\Form\FoodFormType;
use App\Form\MenuFormType;
use App\Form\ComplementType;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FoodController extends AbstractController
{
    private $manager;
    private $menuRepository;
    private $burgerRepository;
    private $complementRepository;

    public function __construct(EntityManagerInterface $manager, MenuRepository $menuRepository, BurgerRepository $burgerRepository, ComplementRepository $complementRepository)
    {
        $this->manager = $manager;
        $this->menuRepository = $menuRepository;
        $this->burgerRepository = $burgerRepository;
        $this->complementRepository = $complementRepository;
    }


    #[Route('/food', name: 'list_food')]
    public function allFoods(Request $request, PaginatorInterface $paginator, SessionInterface $session): Response
    {
        $menus = $this->menuRepository->findBy(['etat' => false], ['id' => 'DESC']);
        $burgers = $this->burgerRepository->findBy(['etat' => false], ['id' => 'DESC']);
        $complements = $this->complementRepository->findBy(['etat' => false], ['id' => 'DESC']);
        $data = array_merge($menus, $burgers, $complements);
        $allFoods = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            4
        );
        if ($session->has('typeSelected')) {
            $typeSelected = $session->get('typeSelected');
            if ($typeSelected  == 'menu') {
                $allFoods = $menus;
            } elseif ($typeSelected == 'burger') {
                $allFoods = $burgers;
            } elseif ($typeSelected == 'complement') {
                $allFoods = $complements;
            }
            $session->remove('typeSelected');
            return $this->render('food/index.html.twig', [
                'allFoods' => $allFoods,
                'typeSelected' => $typeSelected,
            ]);
        }
        return $this->render('food/index.html.twig', [
            'allFoods' => $allFoods
        ]);
    }
    #[Route('/food/archives', name: 'list_archive_food')]
    public function allArchiveFoods(Request $request, PaginatorInterface $paginator): Response
    {
        $menus = $this->menuRepository->findBy(['etat' => true]);
        $burgers = $this->burgerRepository->findBy(['etat' => true]);
        $complements = $this->complementRepository->findBy(['etat' => true]);
        $data = array_merge($menus, $burgers, $complements);
        $allFoods = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            4
        );
        return $this->render('food/archive.html.twig', [
            'allFoods' => $allFoods
        ]);
    }

    #[Route('/food/add', name: 'add_food')]
    public function addFood(Request $request, EntityManagerInterface $manager, FileUploader $fileUploader, Session $session): Response
    {
        $burgers = $this->burgerRepository->findBy(['etat' => false]);
        $complements = $this->complementRepository->findBy(['etat' => false]);
        //burger type
        $burger = new Burger();
        //
        //menu type
        $menu = new Menu();
        //
        //complement 
        $complement = new Complement();
        //
        //image form
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);
        //
        if ($request->getMethod() == "POST") {
            // dd($request);
            $type = $request->get('type');
            if ($type == 'menu') {
                $typeAdd = "menu";
                $nomFood = $request->get('nomFood');
                $tabComplement = $request->get('complements');
                $menu->setNom($nomFood);
                $menu->setBurger($this->burgerRepository->find($request->get('burger')));
                foreach ($tabComplement as $value) {
                    $menu->addComplement($this->complementRepository->find($value));
                }

                $imageFile = $form->get('nom')->getData();
                if ($imageFile) {
                    $imageFileName = $fileUploader->upload($imageFile);
                    $image->setNom($imageFileName);
                }
                $menu->setImage($image);
                $manager->persist($menu);
                $manager->flush();
            } elseif ($type == "burger") {
                $typeAdd = "burger";
                $nomFood = $request->get('nomFood');
                $burger->setNom($nomFood)
                    ->setPrix($request->get('prix'))
                    ->setDescription($request->get('description'));
                $imageFile = $form->get('nom')->getData();
                if ($imageFile) {
                    $imageFileName = $fileUploader->upload($imageFile);
                    $image->setNom($imageFileName);
                }
                $burger->setImage($image);
                $manager->persist($burger);
                $manager->flush();
            } elseif ($type == "complement") {
               
                $typeAdd = "complement";
                $nomFood = $request->get('nomFood');
                $complement->setNom($nomFood)
                    ->setPrix($request->get('prix'));
                $imageFile = $form->get('nom')->getData();
                if ($imageFile) {
                    $imageFileName = $fileUploader->upload($imageFile);
                    $image->setNom($imageFileName);
                }
                $complement->setImage($image);
                $manager->persist($complement);
                $manager->flush();
            }
            $session->getFlashBag()->set('archiveFood', $typeAdd . ' ajouté avec succes');
            return $this->redirectToRoute('list_food');
        }
        return $this->render('food/add.html.twig', [
            'controller_name' => 'FoodController',
            'burgers' => $burgers,
            'complements' => $complements,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/food/edit/{id}', name: 'edit_food')]
    public function editFood(Request $request, EntityManagerInterface $manager, FileUploader $fileUploader, Session $session): Response
    {
        $typeId = $request->attributes->get('id');
        $id = filter_var($typeId,FILTER_SANITIZE_NUMBER_INT);
        if (str_contains($typeId , 'menu')) {
            $restor = $this->menuRepository->find($id);
        }elseif (str_contains($typeId , 'burger')){
            $restor = $this->burgerRepository->find($id);
        }elseif (str_contains($typeId , 'complement')){
            $restor = $this->complementRepository->find($id);
        }
        
       // dd($restor);
        $burgers = $this->burgerRepository->findBy(['etat' => false]);
        $complements = $this->complementRepository->findBy(['etat' => false]);

        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        return $this->render('food/add.html.twig', [
            'controller_name' => 'FoodController',
            'burgers' => $burgers,
            'complements' => $complements,
            'form' => $form->createView(),
            'restor' => $restor,
            'mode' => 'update'
        ]);
    }


    #[Route('/food/archive', name: 'archive_food')]
    #[Route('/food/desarchive', name: 'desarchive_food')]
    public function archiveOrDesarchiveFood(Request $request, Session $session): Response
    {
        $action = $request->attributes->get('_route');
        //$id = $request->attributes->get('id');

        if ($request->getMethod() == 'POST') {
            if ($request->get('archivesAll')) {

                $tabAllFoods = $request->get('archivesAll');
                foreach ($tabAllFoods as $value) {
                    if (str_contains($value, 'menu')) {
                        $id = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                        $this->archiveOrDesarchiveMenu($id);
                    } elseif (str_contains($value, 'burger')) {
                        $id = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                        $this->archiveOrDesarchiveBurger($id);
                    } elseif (str_contains($value, 'complement')) {
                        $id = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                        $this->archiveOrDesarchiveComplement($id);
                    }
                }
                if ($action == 'archive_food') {
                    $session->getFlashBag()->set('archiveFood', 'Operation d\'archivage reussi avec avec succes');
                    return $this->redirectToRoute('list_food');
                } else {
                    $session->getFlashBag()->set('archiveFood', 'Operation de desarchivage reussi avec avec succes');
                    return $this->redirectToRoute('list_archive_food');
                }
            } else {
                $session->getFlashBag()->set('archiveErrorFood', 'Veillez selectionner d\'abord');
                return $this->redirectToRoute('list_food');
            }
        }

        return $this->render('food/index.html.twig', [
            'controller_name' => 'FoodController',
        ]);
    }

    public function archiveOrDesarchiveMenu($id)
    {
        $menu = $this->menuRepository->find($id);
        if ($menu->getEtat() == false) {
            $menu->setEtat(true);
        } else {
            $menu->setEtat(false);
        }
        $this->manager->persist($menu);
        $this->manager->flush();
    }
    public function archiveOrDesarchiveBurger($id)
    {
        $burger = $this->burgerRepository->find($id);
        if ($burger->getEtat() == false) {
            $burger->setEtat(true);
        } else {
            $burger->setEtat(false);
        }
        $this->manager->persist($burger);
        $this->manager->flush();
    }
    public function archiveOrDesarchiveComplement($id)
    {
        $complement = $this->complementRepository->find($id);
        if ($complement->getEtat() == false) {
            $complement->setEtat(true);
        } else {
            $complement->setEtat(false);
        }
        $this->manager->persist($complement);
        $this->manager->flush();
    }

    #[Route('/food/byType', name: 'food_filtre_by_type')]
    public function showCommandeByClient(
        CommandeRepository $repoComm,
        SessionInterface $session,
        Request $request
    ): Response {

        if ($request->isXmlHttpRequest()) {
            $type = $request->query->get('type');
            $session->set("typeSelected", $type);
        }


        return new JsonResponse($this->generateUrl('list_food'));
    }
}
