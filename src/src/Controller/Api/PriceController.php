<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\PriceRequest;
use App\Exception\ExternalResourceNotFoundException;
use App\Exception\PriceParsingException;
use App\Service\PriceService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Price')]
final class PriceController extends AbstractController
{
    #[OA\Get(
        path: '/api/price',
        summary: 'Get tile price in EUR',
        parameters: [
            new OA\Parameter(name: 'factory', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'collection', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'article', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Price found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 59.99),
                        new OA\Property(property: 'factory', type: 'string', example: 'marca-corona'),
                        new OA\Property(property: 'collection', type: 'string', example: 'arteseta'),
                        new OA\Property(property: 'article', type: 'string', example: 'k263-arteseta-camoscio-s000628660'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 400, description: 'Validation failed'),
            new OA\Response(response: 404, description: 'Tile not found'),
            new OA\Response(response: 422, description: 'Price parsing failed'),
            new OA\Response(response: 502, description: 'Upstream error'),
        ],
    )]
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
