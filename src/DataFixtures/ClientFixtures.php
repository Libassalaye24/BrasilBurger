<?php

namespace App\DataFixtures;

use App\Entity\Client;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientFixtures extends Fixture
{
    private $encoder;
    public function  __construct(UserPasswordHasherInterface $encoder){
          $this->encoder=$encoder;
    }

    public function load(ObjectManager $manager): void
    {
        $roles=["ROLE_CLIENT"];       
         $plainPassword = 'passer@123';
        for ($i = 1; $i <=2; $i++) {
            $user = new Client();
            $pos= rand(0,2);
            $user->setNom('Nom '.$i);
            $user->setPrenom('Prenom '.$i);
            $user->setEmail('client'.$i."@gmail.com");
            $encoded = $this->encoder->hashPassword($user, $plainPassword);
            $user->setPassword($encoded);
            $user->setTelephone('77809876'.$i);
            $user->setRoles($roles);  
            $manager->persist($user);
            $this->addReference("Client".$i, $user);
        }

        $manager->flush();
    }
}
