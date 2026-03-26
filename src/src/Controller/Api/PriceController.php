<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\PriceRequest;
use App\Exception\ExternalResourceNotFoundException;
use App\Exception\PriceParsingException;
use App\Service\PriceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PriceController extends AbstractController
{
    #[Route('/api/price', name: 'api_price', methods: ['GET'])]
    public function __invoke(
        Request $request,
        PriceService $priceService,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $dto = new PriceRequest(
            factory: trim((string) $request->query->get('factory', '')),
            collection: trim((string) $request->query->get('collection', '')),
            article: trim((string) $request->query->get('article', '')),
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
            $result = $priceService->getPrice($dto);

            return $this->json($result->toArray());
        } catch (ExternalResourceNotFoundException $e) {
            return $this->json([
                'error' => 'not_found',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_NOT_FOUND);
        } catch (PriceParsingException $e) {
            return $this->json([
                'error' => 'price_parsing_failed',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'upstream_error',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_GATEWAY);
        }
    }
}
