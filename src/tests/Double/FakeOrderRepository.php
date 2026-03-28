<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Entity\Order;
use App\Repository\OrderRepositoryInterface;

final class FakeOrderRepository implements OrderRepositoryInterface
{
    /** @var array<int, Order> */
    public array $ordersById = [];

    /** @var array<int, array{bucket: string, count: int}> */
    public array $aggregateRows = [];

    public int $bucketCount = 0;

    /**
     * @var array<int, array{
     *   id: int,
     *   name: string,
     *   email: string|null,
     *   description: string|null,
     *   locale: string,
     *   status: int,
     *   createdAt: string
     * }>
     */
    public array $searchRows = [];

    public int $searchCount = 0;

    public function findById(int $id): ?Order
    {
        return $this->ordersById[$id] ?? null;
    }

    public function aggregateByPeriod(string $groupBy, int $page, int $limit): array
    {
        return $this->aggregateRows;
    }

    public function countBucketsByPeriod(string $groupBy): int
    {
        return $this->bucketCount;
    }

    public function findSearchableOrders(int $limit = 5000): array
    {
        return $this->searchRows;
    }

    public function searchByText(string $query, int $page, int $limit): array
    {
        return $this->searchRows;
    }

    public function countSearchByText(string $query): int
    {
        return $this->searchCount;
    }
}
