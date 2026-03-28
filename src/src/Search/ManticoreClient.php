<?php

declare(strict_types=1);

namespace App\Search;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ManticoreClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $manticoreUrl = 'http://manticore:9308',
    ) {
    }

    public function resetIndex(): void
    {
        $this->cli('CREATE TABLE IF NOT EXISTS orders_rt(id bigint, name text, email text, description text, locale string, status uint, created_at timestamp)');
        $this->cli('TRUNCATE RTINDEX orders_rt');
    }

    /**
     * @param array<int, array{id:int,name:string,email:?string,description:?string,locale:string,status:int,createdAt:string}> $orders
     */
    public function indexOrders(array $orders): void
    {
        foreach ($orders as $order) {
            $timestamp = strtotime((string) $order['createdAt']);
            if ($timestamp === false) {
                $timestamp = time();
            }

            $this->post('/replace', [
                'table' => 'orders_rt',
                'id' => (int) $order['id'],
                'doc' => [
                    'name' => (string) $order['name'],
                    'email' => (string) ($order['email'] ?? ''),
                    'description' => (string) ($order['description'] ?? ''),
                    'locale' => (string) $order['locale'],
                    'status' => (int) $order['status'],
                    'created_at' => $timestamp,
                ],
            ]);
        }
    }

    /**
     * @return array{total:int,data:array<int, array{id:int,name:string,email:?string,description:?string,locale:string,status:int,createdAt:string,score:int}>}
     */
    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $payload = [
            'table' => 'orders_rt',
            'query' => [
                'match' => [
                    '*' => trim($query),
                ],
            ],
            'limit' => $limit,
            'offset' => $offset,
        ];

        $response = $this->post('/search', $payload);
        $hits = $response['hits']['hits'] ?? [];
        $totalRaw = $response['hits']['total'] ?? 0;
        $total = is_array($totalRaw) ? (int) ($totalRaw['value'] ?? 0) : (int) $totalRaw;

        $rows = [];
        foreach ($hits as $hit) {
            if (!is_array($hit)) {
                continue;
            }

            $source = is_array($hit['_source'] ?? null) ? $hit['_source'] : [];
            $createdAt = isset($source['created_at']) ? (int) $source['created_at'] : 0;

            $rows[] = [
                'id' => (int) ($hit['_id'] ?? 0),
                'name' => (string) ($source['name'] ?? ''),
                'email' => ($source['email'] ?? '') !== '' ? (string) $source['email'] : null,
                'description' => ($source['description'] ?? '') !== '' ? (string) $source['description'] : null,
                'locale' => (string) ($source['locale'] ?? ''),
                'status' => (int) ($source['status'] ?? 0),
                'createdAt' => $createdAt > 0 ? gmdate('Y-m-d H:i:s', $createdAt) : '',
                'score' => (int) ($hit['_score'] ?? 0),
            ];
        }

        return [
            'total' => $total,
            'data' => $rows,
        ];
    }

    private function cli(string $query): void
    {
        $response = $this->httpClient->request('POST', rtrim($this->manticoreUrl, '/') . '/cli', [
            'json' => ['query' => $query],
            'timeout' => 10,
        ]);

        $status = $response->getStatusCode();
        if ($status >= 400) {
            throw new \RuntimeException(sprintf('Manticore CLI HTTP %d on query: %s', $status, $query));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        $response = $this->httpClient->request('POST', rtrim($this->manticoreUrl, '/') . $path, [
            'json' => $payload,
            'timeout' => 10,
        ]);

        $status = $response->getStatusCode();
        if ($status >= 400) {
            throw new \RuntimeException(sprintf('Manticore HTTP %d on %s', $status, $path));
        }

        $data = $response->toArray(false);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid Manticore response format.');
        }

        return $data;
    }
}
