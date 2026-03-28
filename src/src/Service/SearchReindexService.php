<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\OrderRepositoryInterface;
use App\Search\ManticoreClient;

final class SearchReindexService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ManticoreClient $manticore,
    ) {
    }

    public function reindex(int $limit = 10000): int
    {
        $limit = max(1, min(50000, $limit));

        $orders = $this->orders->findSearchableOrders($limit);
        $this->manticore->resetIndex();

        if ($orders !== []) {
            $this->manticore->indexOrders($orders);
        }

        return count($orders);
    }
}
