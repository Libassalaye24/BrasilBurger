<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Client;
use App\Form\InscriptionFormType;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    #[Route('/registration', name: 'register')]
    public function registration(Request $request,EntityManagerInterface $manager,ValidatorInterface $validator,UserPasswordHasherInterface $encoder,UserRepository $userRepository): Response
    {
        $user = new User();
        $client = new Client();
        $form = $this->createForm(InscriptionFormType::class,$client);
        $form->handleRequest($request);
        $errorEmail = 'Cet email existe deja!';
      //  $validator->validate($form);
        if ($form->isSubmitted() && $form->isValid()) 
        {
           
            $clientExist = $userRepository->findBy(['email' => $client->getEmail()]);
            if ($clientExist) {
                return $this->render('user/index.html.twig', [
                    'controller_name' => 'UserController',
                    'form' => $form->createView(),
                    'errorEmail' => $errorEmail,
                ]);
            }
           // dump($request);
            $encoded = $encoder->hashPassword($client,$client->getPassword());
            $client->setPassword($encoded);
            $client->setRoles(['ROLE_CLIENT']);
            $manager->persist($client);
            $manager->flush();
            $session = new Session();
            $session->getFlashBag()->set('SuccessIscri','Inscription reussi avec succes');
           return $this->redirectToRoute('login');
        }
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
            'form' => $form->createView(),
        ]);
    }

}
