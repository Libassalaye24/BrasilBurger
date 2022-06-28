<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        //  if ($this->getUser()) {
        //      foreach ($this->getUser()->getRoles() as $role) {
        //          if ($role == 'ROLE_CLIENT') {
        //             return $this->redirectToRoute('catalogue');
        //          }
        //      }
             
        //  }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

      

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): Response
    {
       
        //throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
        return $this->redirectToRoute("catalogue");
    }

    #[Route(path: '/rediriger', name: 'rediriger')]
    public function rediriger(Request $request):Response
    {
        if ($this->getUser()) {
            $role = $this->getUser()->getRoles();
            $idUser = array_values((array)$this->getUser())[0];
            $email =  array_values((array)$this->getUser())[1];
           //dd($this->getUser());
            $session    = $request->getSession();
            $session->set('idUser', $idUser);
            $session->set('roles', $role[0]);
            $session->set('email', $email);

            $targetPath = $session->get('targetPath');

            if($role[0] == "ROLE_GESTIONNAIRE" && $targetPath == null){
                return $this->redirectToRoute('dashboard');
            }elseif($role[0] == "ROLE_CLIENT" && $targetPath == null){
                return $this->redirectToRoute('mes_commandes');
            }else{
              //  dd(true);
                return new RedirectResponse($targetPath);
            }
        }  
    }
}
