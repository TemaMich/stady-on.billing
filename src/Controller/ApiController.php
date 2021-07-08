<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use App\DTO\UserDto;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerBuilder;
use Firebase\JWT\JWT;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Doctrine\ORM\EntityManagerInterface;

class ApiController extends AbstractController
{
    private $params;

    public function __construct(ContainerBagInterface $params)
    {
        $this->params = $params;
    }

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

        $errorsResponse = [];

        /** @var ConstraintViolation $violation */
        foreach ($validator->validate($dto) as $violation) {
            $errorsResponse[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        $users = $em->getRepository(User::class)->findBy([
            'email' => $dto->getEmail(),
        ]);

        if (count($users) > 0) {
            $errorsResponse[] = sprintf('This email %s are used', $dto->getEmail());
        }

        if (!empty($errorsResponse)) {
            return new JsonResponse($errorsResponse, 500);
        }

        $user = User::fromDto($dto);
        $user->setPassword($hash->hashPassword($user, $dto->getPassword()));
        $em->persist($user);
        $em->flush();
        $response = [
            'token' => $JWTManager->create($user),
        ];

        return new JsonResponse($response['token'], 201);
    }

    /**
     * @Route("/api/v1/users/current", name="user")
     */
    public function getUserByToken(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $extractor = new AuthorizationHeaderTokenExtractor(
                'Bearer',
                'Authorization'
            );
            $token = $extractor->extract($request);

            $dir = $this->container->get('parameter_bag')->get('jwt_public_key');
            $public_key = file_get_contents($dir);

            $algorithm = $this->container->get('parameter_bag')->get('jwt_algorithm');
            try {
                $jwt = (array)JWT::decode(
                    $token,
                    $public_key,
                    [$algorithm]
                );
            } catch (\Exception $exception) {
                return new JsonResponse(["Error" => "JWT not valid"], 400);
            }

            $users = $em->getRepository(User::class)->findOneBy([
                'email' => $jwt['username']
            ]);
            $balance = $users->getBalance();

            return new JsonResponse([
                "username" => $jwt['username'],
                "roles" => $jwt['roles'],
                "balance" => $balance,
            ], 200);
        } catch (\Exception $exception) {
            return new JsonResponse(["Error" => "Server error"], 500);
        }
    }
}
