<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\SearchReindexRequest;
use App\Service\SearchReindexService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Search')]
final class SearchReindexController extends AbstractController
{
    #[OA\Post(
        path: '/api/search/reindex',
        summary: 'Rebuild Manticore index from PostgreSQL orders',
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10000, maximum: 50000)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reindex completed'),
            new OA\Response(response: 400, description: 'Validation failed'),
            new OA\Response(response: 500, description: 'Reindex failed'),
        ],
    )]
    #[OA\Get(
        path: '/api/search/reindex',
        summary: 'Rebuild Manticore index from PostgreSQL orders (GET compatibility)',
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10000, maximum: 50000)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reindex completed'),
            new OA\Response(response: 400, description: 'Validation failed'),
            new OA\Response(response: 500, description: 'Reindex failed'),
        ],
    )]
    #[Route('/api/search/reindex', name: 'api_search_reindex', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        SearchReindexService $reindexService,
        ValidatorInterface $validator,
    ): JsonResponse {
        $dto = new SearchReindexRequest(
            limit: (int) $request->query->get('limit', 10000),
        );

        $violations = $validator->validate($dto);
        $errors = [];
        foreach ($violations as $violation) {
            $field = (string) $violation->getPropertyPath();
            if (!isset($errors[$field])) {
                $errors[$field] = $violation->getMessage();
            }
        }

        if ($errors !== []) {
            return $this->json([
                'error' => 'validation_failed',
                'fields' => $errors,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $indexed = $reindexService->reindex($dto->limit);

            return $this->json([
                'status' => 'success',
                'indexed' => $indexed,
                'limit' => $dto->limit,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'reindex_failed',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
