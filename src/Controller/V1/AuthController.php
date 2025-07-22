<?php

namespace App\Controller\V1;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class AuthController extends AbstractController
{
    #[Route('/auth/login', name: 'api_login', methods: ['POST'])]
    #[OA\Tag(name: 'Authentication')]
    #[OA\Post(
        summary: 'Login to get JWT token',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful login, returns JWT token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
    public function login(): JsonResponse
    {
        return $this->json(['message' => 'Handled by LexikJWT'], 401);
    }
}
