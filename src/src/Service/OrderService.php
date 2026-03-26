<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepositoryInterface;

final class OrderService
{
    public function __construct(private readonly OrderRepositoryInterface $orders)
    {
    }

    public function getById(int $id): ?Order
    {
        return $this->orders->findById($id);
    }
}
