<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Mapper\OrderMapper;
use App\Service\OrderService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Orders')]
final class OrderGetController extends AbstractController
{
    #[OA\Get(
        path: '/api/orders/{id}',
        summary: 'Get single order by id',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order found'),
            new OA\Response(response: 404, description: 'Order not found'),
        ],
    )]
    #[Route('/api/orders/{id}', name: 'api_order_get', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function __invoke(int $id, OrderService $orderService, OrderMapper $orderMapper): JsonResponse
    {
        if ($id < 1) {
            return $this->json([
                'error' => 'validation_failed',
                'fields' => ['id' => 'Order id must be greater than 0.'],
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $order = $orderService->getById($id);
        if ($order === null) {
            return $this->json([
                'error' => 'not_found',
                'message' => 'Order not found.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($orderMapper->toApi($order));
    }
}
