<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Entity\Order;
use App\Service\OrderService;
use App\Tests\Double\FakeOrderRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrderGetEndpointTest extends WebTestCase
{
    public function testOrderGetReturnsSingleOrder(): void
    {
        $client = static::createClient();

        $order = new Order(
            hash: str_repeat('1', 32),
            token: str_repeat('2', 64),
            payType: 1,
            name: 'Feature Order',
            createDate: new \DateTimeImmutable('2026-03-29 12:00:00'),
        );
        $order->setEmail('feature@example.com');
        $order->setDescription('Feature description');
        $order->setStatus(2);
        $order->setTotal(123.45);

        $this->forceOrderId($order, 123);

        $repo = new FakeOrderRepository();
        $repo->ordersById[123] = $order;

        static::getContainer()->set(OrderService::class, new OrderService($repo));

        $client->request('GET', '/api/orders/123');

        self::assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(123, $data['id']);
        self::assertSame(str_repeat('1', 32), $data['hash']);
        self::assertSame('Feature Order', $data['name']);
        self::assertSame('feature@example.com', $data['email']);
    }

    public function testOrderGetReturns404WhenNotFound(): void
    {
        $client = static::createClient();

        $repo = new FakeOrderRepository();
        static::getContainer()->set(OrderService::class, new OrderService($repo));

        $client->request('GET', '/api/orders/999999');

        self::assertResponseStatusCodeSame(404);
    }

    private function forceOrderId(Order $order, int $id): void
    {
        $reflection = new \ReflectionObject($order);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($order, $id);
    }
}
