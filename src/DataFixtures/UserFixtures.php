<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $encoder;
    public function  __construct(UserPasswordHasherInterface $encoder){
          $this->encoder=$encoder;
    }

    public function load(ObjectManager $manager): void
    {
        $roles=["ROLE_GESTIONNAIRE"];
       
         $plainPassword = 'passer@123';
        for ($i = 1; $i <=2; $i++) {
            $user = new User();
            $pos= rand(0,1);
            $user->setNom('Nom '.$i);
            $user->setPrenom('Prenom '.$i);
            $user->setEmail("role_admin".$i."@gmail.com");
            $encoded = $this->encoder->hashPassword($user, $plainPassword);
            $user->setPassword($encoded);
            $user->setRoles([$roles[0]]);  
            $manager->persist($user);
            $this->addReference("User".$i, $user);
        }

        $manager->flush();
    }
}
