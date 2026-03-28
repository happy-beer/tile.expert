<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class SearchRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Parameter "q" is required.')]
        #[Assert\Length(min: 2, minMessage: 'Parameter "q" must contain at least 2 characters.')]
        public readonly string $q,
        #[Assert\Positive(message: 'Parameter "page" must be greater than 0.')]
        public readonly int $page = 1,
        #[Assert\Positive(message: 'Parameter "limit" must be greater than 0.')]
        #[Assert\LessThanOrEqual(value: 100, message: 'Parameter "limit" must be less than or equal to 100.')]
        public readonly int $limit = 20,
    ) {
    }
}
