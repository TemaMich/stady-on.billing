<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\ApiController;
use App\Entity\Course;
use App\DTO\CourseDto;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CourseController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService){
        $this->userService = $userService;
    }

    /**
     *  @OA\Parameter(
     *     name="token",
     *     in="header",
     *     description="The field used to order rewards"
     * )
     *  @OA\Parameter(
     *     name="filter[type]",
     *     in="query",
     *     required=false,
     *     description="The field used to order rewards"
     * )
     *  @OA\Parameter(
     *     name="filter[course_code]",
     *     in="query",
     *     required=false,
     *     description="The field used to order rewards"
     * )
     *  @OA\Parameter(
     *     name="filter[skip_expired]",
     *     in="query",
     *     required=false,
     *     description="The field used to order rewards"
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/transactions", name="transactions_api", methods = "GET")
     */
    public function getTransactions(Request $request, EntityManagerInterface $em): Response
    {
        $token = $request->headers->get("token");
        $filters = $request->query->get("filter");

        $response = $this->userService->UserByToken($token);
        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        $query = [];

        $array = ['billing_user' => $response];

        if($filters !== null){
            if(isset($filters['type'])){
                $array['operationType'] = $filters['type'];
            }
            if(isset($filters['course_code'])){
                $course = $em->getRepository(Course::class)->findOneBy([
                    'code' => $filters['course_code'],
                ]);
                $array['course'] = $course;
            }
            if(isset($filters['skip_expired'])){
                $date = new \DateTime('now');
                $query = $em->getRepository(Transaction::class)->findByDate($date);
            }
            else{
                $query = $em->getRepository(Transaction::class)->findBy($array);
            }
        }
        else{
            $query = $em->getRepository(Transaction::class)->findBy($array);
        }



        //$transactions = $em->getRepository(Transaction::class)->findByDate($date);
        //$transactions = $em->getRepository(Transaction::class)->findBy($array);

            $courseForRequest = [];
            foreach($query as $key => $transaction){
                $courseForRequest[$key] = [
                    "id" => $transaction->getId(),
                    "created_at" => $transaction->getCreatedAt(),
                    "type" => $transaction->getOperationType(),
                    "amount" => $transaction->getValue(),
                    ];
                if($transaction->getOperationType() == 0){
                    $courseForRequest[$key]['course_code'] = $transaction->getCourse()->getCode();
                }
            }
            return new JsonResponse($courseForRequest, 200);
    }

    /**
     *  @OA\Parameter(
     *     name="token",
     *     in="header",
     *     description="The field used to order rewards"
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/courses", name="courses_api", methods = "GET")
     */
    public function showCourses(Request $request, EntityManagerInterface $em): Response
    {
        $token = $request->headers->get("token");

        $response = $this->userService->UserByToken($token);
        if(is_array($response)){
            return new JsonResponse($response, 500);
        }
        if ($response->getEmail() !== null){
            $courses = $em->getRepository(Course::class)->findAll();
            $courseForRequest = [];
            foreach($courses as $course){
                $courseForRequest[] = [
                    "code" => $course->getCode(),
                    "type" => $course->getType(),
                    "price" => $course->getPrice(),
                ];
            }
            return new JsonResponse($courseForRequest, 200);
        }

        return new JsonResponse(["Error" => "Неизвестная ошибка"], 500);
    }


    /**
     *  @OA\Parameter(
     *     name="token",
     *     in="header",
     *     description="The field used to order rewards"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Course show",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="code", type="string"),
     *        @OA\Property(property="type", type="smallint"),
     *        @OA\Property(property="price", type="float"),
     *     )
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/courses/{code}", name="courses_by_code", methods = "GET")
     */
    public function showCoursesByCode(Request $request, EntityManagerInterface $em): Response
    {
        $token = $request->headers->get("token");
        //$code = $request->headers->get("code");
        $code =  $request->attributes->get(['_route_params'][0])['code'];
        $response = $this->userService->UserByToken($token);
        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        if ($response->getEmail() !== null){
            $courses = $em->getRepository(Course::class)->findBy(['code' => $code]);
            $courseForRequest = [];
            foreach($courses as $course){
                $courseForRequest[] = [
                    "code" => $course->getCode(),
                    "type" => $course->getType(),
                    "price" => $course->getPrice(),
                ];
            }
            return new JsonResponse($courseForRequest, 200);
        }

        return new Response($token);
    }



    /**
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *        @OA\Property(property="code", type="string"),
     *        @OA\Property(property="type", type="smallint"),
     *        @OA\Property(property="price", type="float"),
     *     ),
     * ),
     *
     * @OA\Response(
     *     response=201,
     *     description="Course created",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="message", type="string"),
     *     )
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/courses/create", name="course_create",  methods={"POST"})
     */
    public function createCourse(Request $request, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        $serializer = SerializerBuilder::create()->build();
        $dto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');

        $errorsResponse = [];

        /** @var ConstraintViolation $violation */
        foreach ($validator->validate($dto) as $violation) {
            $errorsResponse[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        $course = $em->getRepository(Course::class)->findBy([
            'code' => $dto->getCode(),
        ]);

        $token = $dto->getToken();

        $response = $this->userService->UserByToken($token);
        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        if (count($course) > 0) {
            $errorsResponse['message'] = sprintf('Курс с таким кодом %s уже существует', $dto->getCode());
        }

        if (!empty($errorsResponse)) {
            return new JsonResponse($errorsResponse, 500);
        }



        $course = Course::fromDto($dto);

        $em->persist($course);
        $em->flush();

        $response = [
            'message' => 'Create course',
        ];

        return new JsonResponse($response, 201);
    }

    /**
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *     ),
     * ),
     *
     * @OA\Response(
     *     response=201,
     *     description="Course created",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="message", type="string"),
     *     )
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/courses/pay/{code}", name="course_pay",  methods={"POST"})
     */
    public function payCourse(Request $request, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        $trans = json_decode($request->getContent(), true);
        $code =  $request->attributes->get(['_route_params'][0])['code'];
        $course = $em->getRepository(Course::class)->findOneBy([
            'code' => $code,
        ]);


        $response = $this->userService->UserByToken($trans['token']);
        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        $alreadyBy = $em->getRepository(Transaction::class)->findOneBy([
            'course' => $course,
            'billing_user' => $response,
        ]);



        if($alreadyBy !== null){
            if($alreadyBy->getPayTime() > new \DateTime('now')){
                return new JsonResponse(["Error"=> "Срок аренды еще не истек!"], 406);
            }
        }

        if($course->getPrice() > $response->getBalance()){
            return new JsonResponse(["Error"=> "Денег нет"], 406);
        }

        $transaction = new Transaction();
        $transaction->setOperationType(0);
        $transaction->setCreatedAt(new \DateTime('now'));
        $transaction->setCourse($course);
        $transaction->setBillingUser($response);
        $transaction->setPayTime((new \DateTime('now'))->modify('+1 month'));
        $transaction->setValue($course->getPrice());


        $em->persist($transaction);
        $newBalance = $response->getBalance() - $transaction->getValue();
        $response->setBalance($newBalance);
        $em->persist($response);
        $em->flush();

        /*
        $response = [
            'type' => $transaction->getOperationType(),
            'course' => $transaction->getCourse()->getCode(),
            'user' => $transaction->getBillingUser()->getEmail(),
            'created_at' => $transaction->getCreatedAt(),
            'payTime' => $transaction->getPayTime(),
            'price' => $transaction->getValue(),
        ];
        */
        $response = [
            'success' => 'true',
            'course_type' => 'rent',
            'expires_at' => $transaction->getPayTime(),
        ];

        return new JsonResponse($response, 200);
    }

    /**
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *        @OA\Property(property="among", type="integer"),
     *     ),
     * ),
     *
     * @OA\Response(
     *     response=201,
     *     description="Course created",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="message", type="string"),
     *     )
     * )
     * @OA\Tag(name="user")
     * @Route("/api/v1/deposite", name="deposite",  methods={"POST"})
     */
    public function deposite(Request $request, EntityManagerInterface $em): Response
    {
        $token = json_decode($request->getContent(), true)["token"];
        $among =  json_decode($request->getContent(), true)["among"];

        $response = $this->userService->UserByToken($token);

        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        if ($response->getEmail() !== null){

            $transaction = new Transaction();
            $transaction->setBillingUser($response);
            $transaction->setValue($among);
            $transaction->setOperationType(1);
            $transaction->setCreatedAt(new \DateTime('now'));
            $em->persist($transaction);

            $newBalance = $response->getBalance() + $among;
            $response->setBalance($newBalance);
            $em->persist($response);

            $em->flush();

            $response = [
                'success' => 'true',
                'course_type' => 'depos',
                'created_at' => new \DateTime('now')
            ];

            return new JsonResponse($response, 200);
        }

        return new JsonResponse(["Error" => "Error"], 200);
    }
}
