<?php

declare(strict_types=1);

namespace App\DTO;

final class PriceResult
{
    public function __construct(
        public readonly float $price,
        public readonly string $factory,
        public readonly string $collection,
        public readonly string $article,
    ) {
    }

    /**
     * @return array{price: float, factory: string, collection: string, article: string}
     */
    public function toArray(): array
    {
        return [
            'price' => $this->price,
            'factory' => $this->factory,
            'collection' => $this->collection,
            'article' => $this->article,
        ];
    }
}
