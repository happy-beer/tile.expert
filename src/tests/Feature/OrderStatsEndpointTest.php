<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Service\OrderStatsService;
use App\Tests\Double\FakeOrderRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrderStatsEndpointTest extends WebTestCase
{
    public function testOrderStatsEndpointReturnsAggregations(): void
    {
        $client = static::createClient();

        $repo = new FakeOrderRepository();
        $repo->bucketCount = 2;
        $repo->aggregateRows = [
            ['bucket' => '2099-02', 'count' => 7],
            ['bucket' => '2099-01', 'count' => 3],
        ];

        static::getContainer()->set(OrderStatsService::class, new OrderStatsService($repo));

        $client->request('GET', '/api/orders/stats?groupBy=month&page=1&limit=20');

        self::assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('month', $data['groupBy']);
        self::assertSame(2, $data['total']);
        self::assertSame(1, $data['page']);
        self::assertSame(20, $data['limit']);
        self::assertSame($repo->aggregateRows, $data['data']);
    }
}
