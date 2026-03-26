<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\OrderStatsItem;
use App\Repository\OrderRepositoryInterface;

final class OrderStatsService
{
    public function __construct(private readonly OrderRepositoryInterface $orders)
    {
    }

    /**
     * @return array{
     *   page: int,
     *   limit: int,
     *   total: int,
     *   pages: int,
     *   groupBy: string,
     *   data: array<int, array{bucket: string, count: int}>
     * }
     */
    public function getStats(string $groupBy, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, min(500, $limit));

        $total = $this->orders->countBucketsByPeriod($groupBy);
        $rows = $this->orders->aggregateByPeriod($groupBy, $page, $limit);
        $pages = max(1, (int) ceil($total / $limit));

        $items = array_map(
            static fn (array $row): OrderStatsItem => new OrderStatsItem(
                bucket: (string) $row['bucket'],
                count: (int) $row['count'],
            ),
            $rows,
        );

        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => $pages,
            'groupBy' => $groupBy,
            'data' => array_map(
                static fn (OrderStatsItem $item): array => [
                    'bucket' => $item->bucket,
                    'count' => $item->count,
                ],
                $items,
            ),
        ];
    }
}
