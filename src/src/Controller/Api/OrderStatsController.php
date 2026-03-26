<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\OrderStatsRequest;
use App\Service\OrderStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OrderStatsController extends AbstractController
{
    #[Route('/api/orders/stats', name: 'api_orders_stats', methods: ['GET'])]
    public function __invoke(
        Request $request,
        OrderStatsService $orderStatsService,
        ValidatorInterface $validator,
    ): JsonResponse {
        $dto = new OrderStatsRequest(
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', 20),
            groupBy: strtolower(trim((string) $request->query->get('groupBy', 'month'))),
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
            $payload = $orderStatsService->getStats($dto->groupBy, $dto->page, $dto->limit);

            return $this->json($payload);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => 'validation_failed',
                'fields' => ['groupBy' => $e->getMessage()],
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'internal_error',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
