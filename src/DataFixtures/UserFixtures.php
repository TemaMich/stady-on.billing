<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $hash;

    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->hash = $encoder;
    }
    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@admin.com');
        $admin->setRoles(["ROLE_SUPER_ADMIN"]);
        $password = $this->hash->hashPassword($admin, 'pass_123456');
        $admin->setPassword($password);
        $manager->persist($admin);

        $user = new User();
        $user->setEmail('user@user.com');
        $password = $this->hash->hashPassword($user, 'pass_123456');
        $user->setPassword($password);
        $manager->persist($user);
        $manager->flush();
    }
}