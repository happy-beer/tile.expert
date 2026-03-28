<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\OrderStatsService;
use App\Tests\Double\FakeOrderRepository;
use PHPUnit\Framework\TestCase;

final class OrderStatsServiceTest extends TestCase
{
    public function testBuildsPaginatedStatsPayload(): void
    {
        $repo = new FakeOrderRepository();
        $repo->bucketCount = 5;
        $repo->aggregateRows = [
            ['bucket' => '2026-03', 'count' => 3],
            ['bucket' => '2026-02', 'count' => 2],
        ];

        $service = new OrderStatsService($repo);
        $payload = $service->getStats('month', 2, 2);

        self::assertSame(2, $payload['page']);
        self::assertSame(2, $payload['limit']);
        self::assertSame(5, $payload['total']);
        self::assertSame(3, $payload['pages']);
        self::assertSame('month', $payload['groupBy']);
        self::assertSame($repo->aggregateRows, $payload['data']);
    }

    public function testClampsInvalidPagingValues(): void
    {
        $repo = new FakeOrderRepository();
        $repo->bucketCount = 1;
        $repo->aggregateRows = [
            ['bucket' => '2026', 'count' => 1],
        ];

        $service = new OrderStatsService($repo);
        $payload = $service->getStats('year', 0, 9999);

        self::assertSame(1, $payload['page']);
        self::assertSame(500, $payload['limit']);
        self::assertSame(1, $payload['pages']);
    }
}
