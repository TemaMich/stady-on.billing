<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use App\DTO\UserDto;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/v1/auth", name="login")
     */
    public function login(): Response
    {

    }

    /**
     * @Route("/api/v1/register", name="register",  methods={"POST"})
     */

    public function register(Request $request, UserPasswordHasherInterface $hash, JWTTokenManagerInterface $JWTManager, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        $serializer = SerializerBuilder::create()->build();
        $dto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');

        $errors = $validator->validate($dto);

        $email = $em->getRepository(User::class)->findBy(['email' => $dto->getEmail()]);

        $errorsResponse = [];

        if (count($email) > 0) {
            $errorsResponse[] = "This email are used";
        }

        if (count($errors) > 0 || count($email) > 0) {
            foreach ($errors as $error) {
                $errorsResponse[] = (string)$error->getMessage();
            }
            return new JsonResponse($errorsResponse, 500);
        }


        $user = User::fromDto($dto, $hash);
        $em->persist($user);
        $em->flush();
        $response = [
            'token' => $JWTManager->create($user),
        ];
        echo "1";
        return new JsonResponse($response['token'], 200);
    }
}
