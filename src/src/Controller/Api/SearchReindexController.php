<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\SearchReindexRequest;
use App\Service\SearchReindexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SearchReindexController extends AbstractController
{
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
