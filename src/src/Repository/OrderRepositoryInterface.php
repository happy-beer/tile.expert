<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    /**
     * @return array<int, array{bucket: string, count: int}>
     */
    public function aggregateByPeriod(string $groupBy, int $page, int $limit): array;

    public function countBucketsByPeriod(string $groupBy): int;

    /**
     * @return array<int, array{
     *   id: int,
     *   name: string,
     *   email: string|null,
     *   description: string|null,
     *   locale: string,
     *   status: int,
     *   createdAt: string
     * }>
     */
    public function findSearchableOrders(int $limit = 5000): array;

    /**
     * @return array<int, array{
     *   id: int,
     *   name: string,
     *   email: string|null,
     *   description: string|null,
     *   locale: string,
     *   status: int,
     *   createdAt: string
     * }>
     */
    public function searchByText(string $query, int $page, int $limit): array;

    public function countSearchByText(string $query): int;
}
