<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Service\OrderService;
use App\Tests\Double\FakeOrderRepository;
use PHPUnit\Framework\TestCase;

final class OrderServiceTest extends TestCase
{
    public function testReturnsOrderById(): void
    {
        $order = new Order(
            hash: str_repeat('a', 32),
            token: str_repeat('b', 64),
            payType: 1,
            name: 'Unit Test Order',
            createDate: new \DateTimeImmutable('2026-03-29 00:00:00'),
        );

        $this->forceOrderId($order, 77);

        $repo = new FakeOrderRepository();
        $repo->ordersById[77] = $order;

        $service = new OrderService($repo);

        self::assertSame($order, $service->getById(77));
        self::assertNull($service->getById(999));
    }

    private function forceOrderId(Order $order, int $id): void
    {
        $reflection = new \ReflectionObject($order);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($order, $id);
    }
}
