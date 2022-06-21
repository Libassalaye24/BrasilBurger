<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use App\Repository\BurgerRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public static function getProducts(BurgerRepository $burgerRepository,MenuRepository $menuRepository):array
    {
        $menus = self::getMenus($menuRepository);
        $burgers = self::getBurgers($burgerRepository);
        return array_merge($menus,$burgers);
    }
    public static function getBurgers(BurgerRepository $burgerRepository):array
    {
        $burgers = $burgerRepository->findBy(['etat' => false]);
        return $burgers;
    }

    public static function getMenus(MenuRepository $menuRepository):array
    {
        $menus = $menuRepository->findBy(['etat' => false]);
        return $menus;
    }

   
    #[Route('/catalogue/details/{id}',name: 'details')]
    public function viewDetails($id,BurgerRepository $burgerRepository,MenuRepository $menuRepository):Response
    {
        if (!$id) {
            throw $this->createNotFoundException(
                'No product found for id '.$id
            );
        }
        $newId =  (int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);
        $products = $this->getProducts($burgerRepository,$menuRepository);
        foreach ($products as $value) {
            if ($value->getId() == $newId) {
                if (str_contains($id, "menu")) {
                    $details = $menuRepository->find($newId);
                } elseif (str_contains($id, "burger")) {
                    $details = $burgerRepository->find($newId);
                }
            }
        }
      /*   foreach ($products as $value) {
            if($value->getId() == $id){
                
                if ($value->getType() == 'menu') {
                    $details = $menuRepository->find($id);
                }elseif ($value->getType() == 'burger') {
                    $details = $burgerRepository->find($id);
                }
            }
        } */
        //$details = $burgerRepository->find($id);
        return $this->render('home/showDetails.html.twig', [
            'details' => $details,
        ]);
        
    }

    #[Route('/', name: 'catalogue')]
    public function catalogue(Request $request,BurgerRepository $burgerRepository,MenuRepository $menuRepository,SessionInterface $session,PaginatorInterface $paginatorInterface): Response
    {
       // $this->denyAccessUnlessGranted("ROLE_CLIENT");
        $burgers = $burgerRepository->findBy(['etat' => false]);
        $menus = $menuRepository->findBy(['etat' => false]);
        $data = $this->getProducts($burgerRepository,$menuRepository);
        if ($session->has('typeSelected')) {
            $typeSelected = $session->get('typeSelected');
            if ($typeSelected == 'menu') {
                $products = $menus;
                $session->remove('typeSelected');
                return $this->render('home/index.html.twig', [
                    'controller_name' => 'HomeController',
                    'products' => $products,
                    'typeSelected' => $typeSelected,
                ]);
            }else {
                $products = $burgers;
                $session->remove('typeSelected');
                return $this->render('home/index.html.twig', [
                    'controller_name' => 'HomeController',
                    'products' => $products,
                    'typeSelected' => $typeSelected,
                ]);
            }
            
        }
       
        $products = $data;
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'products' => $products,
        ]);
    }

    #[Route('/catalogue/type', name: 'catalogue_by_type')]
    public function showCatalogueByType(
                          SessionInterface $session,
                          Request $request ): Response
    {
        if($request->query) {
            $type = $request->query->get('type');
            $session->set("typeSelected", $type);
        }
        
  
        return new JsonResponse($this->generateUrl('catalogue'));
    }

    /* public function addCommande(int $id,Request $request):Response
    {
        if (!$id) {
            throw $this->createNotFoundException(
                'No product found for id '.$id
            );
        }
        return $this->render('home/index.html.twig', [
           
        ]);
    } */
}
