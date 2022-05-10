<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use App\Repository\BurgerRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'catalogue')]
    public function catalogue(BurgerRepository $burgerRepository,MenuRepository $menuRepository,SessionInterface $session): Response
    {
        $burgers = $burgerRepository->findBy(['etat' => false]);
        $menus = $menuRepository->findBy(['etat' => false]);
        if ($session->has('typeSelected')) {
            $typeSelected = $session->get('typeSelected');
            if ($typeSelected == 'menus') {
                $session->remove('typeSelected');
                return $this->render('home/index.html.twig', [
                    'controller_name' => 'HomeController',
                    'menus' => $menus,
                    'typeSelected' => $typeSelected,
                ]);

            }elseif ($typeSelected == 'burgers'){
                $session->remove('typeSelected');
                return $this->render('home/index.html.twig', [
                    'controller_name' => 'HomeController',
                    'burgers' => $burgers,
                    'typeSelected' => $typeSelected,
                ]);
            }
        }
       /*  $datas = array_combine(['a','b','c'],[$burgers,$menus]);
        dd($datas); */
        //$catalogue = new Session();
        //$catalogue->set('catalogue',$burgers,$menus);
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'menus' => $menus,
        ]);
    }

    #[Route('/catalogue/type', name: 'catalogue_by_type')]
    public function showCatalogueByType(
                          SessionInterface $session,
                          Request $request ): Response
    {
    // dd(  $request->query->get('id'));
        if($request->query) {
         // dd(  $request->query->get('id'));
               $type = $request->query->get('type');
           
          //  $session->set("commandes", $commandes);
            $session->set("typeSelected", $type);
            //dd( $session->set("etatSelected", $id));
        
        }
        
  
        return new JsonResponse($this->generateUrl('catalogue'));
    }
}
