<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\EntityManagerInterface;


class UserDto
{
    /**
     * @Assert\NotBlank(message="Name is mandatory")
     * @Assert\Email( message="Invalid email address" )
     */
    public string $username;

    /**
     * @Assert\NotBlank(message="Password is mandatory")
     * @Assert\Length(
     *      min = 6,
     *      max = 50,
     *      minMessage = "The password has to be at least {{ limit }} chars long",
     *      maxMessage = "The password must be no longer than {{ limit }} more"
     * )
     */
    public string $password;

    public function getEmail(): ?string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}