<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderArticle;
use Doctrine\ORM\EntityManagerInterface;

final class SoapOrderService
{
    private const REQUIRED_ORDER_FIELDS = [
        'hash',
        'token',
        'status',
        'vat_type',
        'pay_type',
        'locale',
        'currency',
        'measure',
        'name',
        'create_date',
        'step',
    ];

    private const REQUIRED_ARTICLE_FIELDS = [
        'amount',
        'price',
        'weight',
        'packaging_count',
        'pallet',
        'packaging',
        'swimming_pool',
    ];

    private const BOOLEAN_FIELDS = [
        'address_equal',
        'bank_transfer_requested',
        'accept_pay',
        'product_review',
        'process',
        'payment_euro',
        'spec_price',
        'show_msg',
        'swimming_pool',
    ];

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @param array<string, mixed> $orderPayload
     * @param array<int, array<string, mixed>> $articlesPayload
     */
    public function createOrderWithArticles(array $orderPayload, array $articlesPayload): int
    {
        $orderPayload = $this->normalizePayloadKeys($orderPayload);

        $this->assertRequiredFields(
            payload: $orderPayload,
            requiredFields: self::REQUIRED_ORDER_FIELDS,
            context: 'order',
        );

        foreach ($articlesPayload as $index => $articlePayload) {
            $normalized = $this->normalizePayloadKeys($articlePayload);
            $this->assertRequiredFields(
                payload: $normalized,
                requiredFields: self::REQUIRED_ARTICLE_FIELDS,
                context: sprintf('orders_article[%d]', $index),
            );
        }

        return $this->em->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($orderPayload, $articlesPayload): int {
            $order = $this->hydrateOrder($orderPayload);
            $entityManager->persist($order);

            foreach ($articlesPayload as $articlePayload) {
                $article = $this->hydrateOrderArticle($this->normalizePayloadKeys($articlePayload), $order);
                $entityManager->persist($article);
            }

            $entityManager->flush();

            return (int) $order->getId();
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateOrder(array $payload): Order
    {
        if (array_key_exists('total', $payload) && !array_key_exists('delivery_price_euro', $payload)) {
            $payload['delivery_price_euro'] = $payload['total'];
        }

        $order = new Order(
            hash: $this->requiredString($payload, 'hash', 'order'),
            token: $this->requiredString($payload, 'token', 'order'),
            payType: $this->requiredInt($payload, 'pay_type', 'order'),
            name: $this->requiredString($payload, 'name', 'order'),
            createDate: $this->requiredDateTime($payload, 'create_date', 'order'),
        );

        $order->setStatus($this->requiredInt($payload, 'status', 'order'));
        $order->setVatType($this->requiredInt($payload, 'vat_type', 'order'));
        $order->setLocale($this->requiredString($payload, 'locale', 'order'));
        $order->setCurrency($this->requiredString($payload, 'currency', 'order'));
        $order->setMeasure($this->requiredString($payload, 'measure', 'order'));

        if (array_key_exists('email', $payload) && $payload['email'] !== '') {
            $order->setEmail((string) $payload['email']);
        }

        if (array_key_exists('description', $payload) && $payload['description'] !== '') {
            $order->setDescription((string) $payload['description']);
        }

        if (array_key_exists('delivery_price_euro', $payload) && $payload['delivery_price_euro'] !== '') {
            $order->setDeliveryPriceEuro($this->requiredFloat($payload, 'delivery_price_euro', 'order'));
        }

        if (array_key_exists('update_date', $payload) && $payload['update_date'] !== '') {
            $order->setUpdateDate($this->requiredDateTime($payload, 'update_date', 'order'));
        }

        return $order;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateOrderArticle(array $payload, Order $order): OrderArticle
    {
        $article = new OrderArticle(
            amount: $this->requiredFloat($payload, 'amount', 'orders_article'),
            price: $this->requiredFloat($payload, 'price', 'orders_article'),
        );

        $article->setOrder($order);
        $article->setWeight($this->requiredFloat($payload, 'weight', 'orders_article'));
        $article->setPackagingCount($this->requiredFloat($payload, 'packaging_count', 'orders_article'));
        $article->setPallet($this->requiredFloat($payload, 'pallet', 'orders_article'));
        $article->setPackaging($this->requiredFloat($payload, 'packaging', 'orders_article'));
        $article->setSwimmingPool($this->requiredBool($payload, 'swimming_pool', 'orders_article'));

        if (array_key_exists('article_id', $payload) && $payload['article_id'] !== '') {
            $article->setArticleId($this->requiredInt($payload, 'article_id', 'orders_article'));
        }

        if (array_key_exists('price_eur', $payload) && $payload['price_eur'] !== '') {
            $article->setPriceEur($this->requiredFloat($payload, 'price_eur', 'orders_article'));
        }

        if (array_key_exists('currency', $payload) && $payload['currency'] !== '') {
            $article->setCurrency((string) $payload['currency']);
        }

        if (array_key_exists('measure', $payload) && $payload['measure'] !== '') {
            $article->setMeasure((string) $payload['measure']);
        }

        return $article;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $requiredFields
     */
    private function assertRequiredFields(array $payload, array $requiredFields, string $context): void
    {
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Missing required %s field(s): %s.',
                $context,
                implode(', ', $missing),
            ));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredString(array $payload, string $field, string $context): string
    {
        $value = trim((string) ($payload[$field] ?? ''));
        if ($value === '') {
            throw new \InvalidArgumentException(sprintf('Field "%s" in %s must be a non-empty string.', $field, $context));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredInt(array $payload, string $field, string $context): int
    {
        $raw = (string) ($payload[$field] ?? '');
        if (filter_var($raw, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException(sprintf('Field "%s" in %s must be an integer.', $field, $context));
        }

        return (int) $raw;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredFloat(array $payload, string $field, string $context): float
    {
        $raw = str_replace(',', '.', (string) ($payload[$field] ?? ''));
        if (!is_numeric($raw)) {
            throw new \InvalidArgumentException(sprintf('Field "%s" in %s must be numeric.', $field, $context));
        }

        return (float) $raw;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredDateTime(array $payload, string $field, string $context): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable((string) ($payload[$field] ?? ''));
        } catch (\Throwable) {
            throw new \InvalidArgumentException(sprintf('Field "%s" in %s must be a valid datetime.', $field, $context));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredBool(array $payload, string $field, string $context): bool
    {
        $raw = strtolower(trim((string) ($payload[$field] ?? '')));
        if (in_array($raw, ['1', 'true', 'yes', 'y', 'on'], true)) {
            return true;
        }

        if (in_array($raw, ['0', 'false', 'no', 'n', 'off'], true)) {
            return false;
        }

        throw new \InvalidArgumentException(sprintf('Field "%s" in %s must be boolean.', $field, $context));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizePayloadKeys(array $payload): array
    {
        $normalized = [];

        foreach ($payload as $key => $value) {
            $snakeKey = strtolower((string) $key);
            $snakeKey = preg_replace('/([a-z])([A-Z])/', '$1_$2', $snakeKey) ?? $snakeKey;
            $snakeKey = str_replace(['-', ' '], '_', $snakeKey);
            $snakeKey = preg_replace('/_+/', '_', $snakeKey) ?? $snakeKey;

            if ($snakeKey === '') {
                continue;
            }

            $normalized[$snakeKey] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }
}
