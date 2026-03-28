<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\SearchRequest;
use App\Service\SearchService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Search')]
final class SearchController extends AbstractController
{
    #[OA\Get(
        path: '/api/search',
        summary: 'Search orders via Manticore with PostgreSQL fallback',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string', minLength: 2)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Search result'),
            new OA\Response(response: 400, description: 'Validation failed'),
        ],
    )]
    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function __invoke(Request $request, SearchService $searchService, ValidatorInterface $validator): JsonResponse
    {
        $dto = new SearchRequest(
            q: trim((string) $request->query->get('q', '')),
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', 20),
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

        $payload = $searchService->search($dto->q, $dto->page, $dto->limit);

        return $this->json($payload);
    }
}
