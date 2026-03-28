<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\OrderRepositoryInterface;
use App\Search\ManticoreClient;

final class SearchService
{
    public function __construct(
        private readonly ManticoreClient $manticore,
        private readonly OrderRepositoryInterface $orders,
    ) {
    }

    /**
     * @return array{
     *   page:int,
     *   limit:int,
     *   total:int,
     *   pages:int,
     *   source:string,
     *   data:array<int, array<string, mixed>>
     * }
     */
    public function search(string $query, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $offset = ($page - 1) * $limit;

        try {
            $result = $this->manticore->search($query, $limit, $offset);
            $rows = $result['data'];
            $total = $result['total'];

            return [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => max(1, (int) ceil($total / $limit)),
                'source' => 'manticore',
                'data' => $rows,
            ];
        } catch (\Throwable) {
            $rows = $this->orders->searchByText($query, $page, $limit);
            $total = $this->orders->countSearchByText($query);

            return [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => max(1, (int) ceil($total / $limit)),
                'source' => 'fallback_like',
                'data' => $rows,
            ];
        }
    }
}
