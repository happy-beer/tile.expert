<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class PriceRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Parameter "factory" is required.')]
        #[Assert\Length(max: 100, maxMessage: 'Parameter "factory" is too long.')]
        #[Assert\Regex(
            pattern: '/^[a-z0-9-]+$/',
            message: 'Parameter "factory" must contain only lowercase letters, digits and hyphen.'
        )]
        public readonly string $factory,
        #[Assert\NotBlank(message: 'Parameter "collection" is required.')]
        #[Assert\Length(max: 100, maxMessage: 'Parameter "collection" is too long.')]
        #[Assert\Regex(
            pattern: '/^[a-z0-9-]+$/',
            message: 'Parameter "collection" must contain only lowercase letters, digits and hyphen.'
        )]
        public readonly string $collection,
        #[Assert\NotBlank(message: 'Parameter "article" is required.')]
        #[Assert\Length(max: 200, maxMessage: 'Parameter "article" is too long.')]
        #[Assert\Regex(
            pattern: '/^[a-z0-9-]+$/',
            message: 'Parameter "article" must contain only lowercase letters, digits and hyphen.'
        )]
        public readonly string $article,
    ) {
    }
}
