<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class OrderStatsRequest
{
    public function __construct(
        #[Assert\Positive(message: 'Parameter "page" must be greater than 0.')]
        public readonly int $page = 1,
        #[Assert\Positive(message: 'Parameter "limit" must be greater than 0.')]
        #[Assert\LessThanOrEqual(value: 500, message: 'Parameter "limit" must be less than or equal to 500.')]
        public readonly int $limit = 20,
        #[Assert\NotBlank(message: 'Parameter "groupBy" is required.')]
        #[Assert\Choice(choices: ['day', 'month', 'year'], message: 'Parameter "groupBy" must be one of: day, month, year.')]
        public readonly string $groupBy = 'month',
    ) {
    }
}

