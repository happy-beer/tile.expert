<?php

declare(strict_types=1);

namespace App\DTO;

final class OrderStatsItem
{
    public function __construct(
        public readonly string $bucket,
        public readonly int $count,
    ) {
    }
}
