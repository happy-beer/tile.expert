<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class SearchReindexRequest
{
    public function __construct(
        #[Assert\Positive(message: 'Parameter "limit" must be greater than 0.')]
        #[Assert\LessThanOrEqual(value: 50000, message: 'Parameter "limit" must be less than or equal to 50000.')]
        public readonly int $limit = 10000,
    ) {
    }
}
