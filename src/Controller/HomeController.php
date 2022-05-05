<?php

namespace App\Controller;

use App\Repository\BurgerRepository;
use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'catalogue')]
    public function catalogue(BurgerRepository $burgerRepository,MenuRepository $menuRepository): Response
    {
        $burgers = $burgerRepository->findBy(['etat' => false]);
        $menus = $menuRepository->findBy(['etat' => false]);
        //$catalogue = new Session();
        //$catalogue->set('catalogue',$burgers,$menus);
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'burgers' => $burgers,
            'menus' => $menus,
        ]);
    }
}
