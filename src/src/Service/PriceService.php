<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\PriceRequest;
use App\DTO\PriceResult;
use App\Integration\TileExpertClient;

final class PriceService
{
    public function __construct(private readonly TileExpertClient $tileExpertClient)
    {
    }

    public function getPrice(PriceRequest $request): PriceResult
    {
        $html = $this->tileExpertClient->fetchProductHtml(
            $request->factory,
            $request->collection,
            $request->article,
        );

        $price = $this->tileExpertClient->extractPriceEuro($html, $request->article);

        return new PriceResult(
            price: $price,
            factory: $request->factory,
            collection: $request->collection,
            article: $request->article,
        );
    }
}
