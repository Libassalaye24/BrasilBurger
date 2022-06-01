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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Security("is_granted('ROLE_GESTIONNAIRE')")]
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
    public function allArchiveFoods(Request $request, PaginatorInterface $paginator , SessionInterface $session): Response
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
        if ($session->has('typeSelectedArchive')) {
            $typeSelectedArchive = $session->get('typeSelectedArchive');
            if ($typeSelectedArchive  == 'menu') {
                $allFoods = $menus;
            } elseif ($typeSelectedArchive == 'burger') {
                $allFoods = $burgers;
            } elseif ($typeSelectedArchive == 'complement') {
                $allFoods = $complements;
            }
            $session->remove('typeSelectedArchive');
            return $this->render('food/archive.html.twig', [
                'allFoods' => $allFoods,
                'typeSelectedArchive' => $typeSelectedArchive,
            ]);
        }
        return $this->render('food/archive.html.twig', [
            'allFoods' => $allFoods
        ]);
    }

    #[Route('/food/add', name: 'add_food')]
    #[Route('/food/edit/{id}', name: 'edit_food')]
    public function addFood(Burger $burger = null, Menu $menu = null, Complement $complement = null, Request $request, EntityManagerInterface $manager, FileUploader $fileUploader, Session $session): Response
    {
        $burgers = $this->burgerRepository->findBy(['etat' => false]);
        $complements = $this->complementRepository->findBy(['etat' => false]);
        $method = $request->getMethod();
        $datas = $request->request->all();
        $form = $this->createForm(BurgerType::class, $burger);
        $form->handleRequest($request);
        extract($datas);
        //burger type
        if (!$burger) {
            $burger = new Burger();
        }
        //
        //menu type
        if (!$menu) {
            $menu = new Menu();
        }
        //
        //complement 
        if (!$complement) {
            $complement = new Complement();
        }
        //
        //image 
        $image = new Image();
        $session2 = $request->getSession();
        $url = array_values(explode("/", $request->getrequestUri()));
       // dd($url);
        if ($method == "GET" && $url[2] == "edit") {
          //  dd($complementNom);
            $id  = $url[3];
            $Allburgers = $this->burgerRepository->findBy(['etat' => false]);
            $Allmenus = $this->menuRepository->findBy(['etat' => false]);
            $Allcomplement = $this->complementRepository->findBy(['etat' => false]);
            $newId =  (int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);
            $element = [];
            $catalogue = array_merge($Allburgers, $Allmenus, $Allcomplement);
            foreach ($catalogue as $value) {
                if ($value->getId() == $newId) {
                    if (str_contains($id, "menu")) {
                        $element = $this->menuRepository->find($newId);
                        $complement =  $element->getComplements()->toArray();
                    } elseif (str_contains($id, "burger")) {
                        $element = $this->burgerRepository->find($newId);
                    } elseif (str_contains($id, "complement")) {
                        $element = $this->complementRepository->find($newId);
                    }
                }
            }
           // dd($element);
            return $this->render('food/add.html.twig', [
                'controller_name' => 'FoodController',
                'burgers' => $burgers,
                'complements' => $complements,
                'form' => $form->createView(),
                'element'       => $element,
                'complement'    => $complement
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $session->set("type", $type);
            $session->set("nomFood", $nomFood);
            $session->set("prix", $prix);


            $imageFile = $form->get('image')->get('nom')->getData();
            if ($imageFile) {
                $imageFileName = $fileUploader->upload($imageFile);
                $image->setNom($imageFileName);
            }

            if ($type == 'burger') {

                $form->getData()->setNom($nomFood)
                    ->setPrix($prix)
                    ->setDescription('description')
                    ->setImage($image);
                $manager->persist($form->getData());
            } elseif ($type == 'menu') {
                $oneBurger = $this->burgerRepository->find($burgerNom);
               /*  foreach ($complementNom as $value) {
                    $oneComplement = $this->complementRepository->find($value);
                    $sumPrixComplement[] = $oneComplement->getPrix();
                }
                $prixComp = array_sum($sumPrixComplement);
                $prixMenu = $oneBurger->getPrix() + $prixComp; */

                $menu->setNom($nomFood)
                    ->setBurger($oneBurger)
                    ->setImage($image);
              //  dd($complementNom);
                foreach ($complementNom as $value) {
                    $menu->addComplement($this->complementRepository->find($value));
                }
               
                $manager->persist($menu);
            } elseif ($type == 'complement') {
                $complement->setNom($nomFood)
                    ->setPrix($prix)
                    ->setImage($image);
                $manager->persist($complement);
            }

            $manager->persist($image);
            $manager->flush();
            //$session->getFlashBag()->set('archiveFood', $type . ' ajouté avec succes');
            return $this->redirectToRoute('list_food');
        }
        
        return $this->render('food/add.html.twig', [
            'controller_name' => 'FoodController',
            'burgers' => $burgers,
            'complements' => $complements,
            'form' => $form->createView(),
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
                if ($action == 'archive_food') {
                    return $this->redirectToRoute('list_food');
                }else{
                    return $this->redirectToRoute('list_archive_food');
                }
                
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
    public function showFoodByEtat(
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

    #[Route('/food/byType1', name: 'food_filtre_by_type1')]
    public function showFoodByEtat1(
        CommandeRepository $repoComm,
        SessionInterface $session,
        Request $request
    ): Response {

        if ($request->isXmlHttpRequest()) {
            $type = $request->query->get('type');
            $session->set("typeSelectedArchive", $type);
        }


        return new JsonResponse($this->generateUrl('list_archive_food'));
    }
    
}
