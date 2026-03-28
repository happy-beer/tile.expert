<?php

declare(strict_types=1);

namespace App\Mapper;

use App\Entity\Order;
use App\Entity\OrderArticle;

final class OrderMapper
{
    /**
     * @return array{
     *   id: int|null,
     *   hash: string,
     *   status: int,
     *   email: string|null,
     *   name: string,
     *   description: string|null,
     *   locale: string,
     *   currency: string,
     *   measure: string,
     *   total: float|null,
     *   createdAt: string,
     *   updatedAt: string|null,
     *   articles: array<int, array<string, mixed>>
     * }
     */
    public function toApi(Order $order): array
    {
        return [
            'id' => $order->getId(),
            'hash' => $order->getHash(),
            'status' => $order->getStatus(),
            'email' => $order->getEmail(),
            'name' => $order->getName(),
            'description' => $order->getDescription(),
            'locale' => $order->getLocale(),
            'currency' => $order->getCurrency(),
            'measure' => $order->getMeasure(),
            'total' => $order->getTotal(),
            'createdAt' => $order->getCreateDate()->format(DATE_ATOM),
            'updatedAt' => $order->getUpdateDate()?->format(DATE_ATOM),
            'articles' => array_map(
                fn (OrderArticle $article): array => $this->mapArticle($article),
                $order->getArticles()->toArray(),
            ),
        ];
    }

    /**
     * @return array{
     *   id: int|null,
     *   articleId: int|null,
     *   amount: float,
     *   price: float,
     *   priceEur: float|null,
     *   currency: string|null,
     *   measure: string|null,
     *   weight: float,
     *   packagingCount: float,
     *   pallet: float,
     *   packaging: float,
     *   swimmingPool: bool
     * }
     */
    private function mapArticle(OrderArticle $article): array
    {
        return [
            'id' => $article->getId(),
            'articleId' => $article->getArticleId(),
            'amount' => $article->getAmount(),
            'price' => $article->getPrice(),
            'priceEur' => $article->getPriceEur(),
            'currency' => $article->getCurrency(),
            'measure' => $article->getMeasure(),
            'weight' => $article->getWeight(),
            'packagingCount' => $article->getPackagingCount(),
            'pallet' => $article->getPallet(),
            'packaging' => $article->getPackaging(),
            'swimmingPool' => $article->isSwimmingPool(),
        ];
    }
}
