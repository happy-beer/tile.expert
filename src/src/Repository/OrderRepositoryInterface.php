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
}
