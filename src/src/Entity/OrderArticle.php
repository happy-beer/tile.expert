<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders_article')]
class OrderArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'orders_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\Column(name: 'article_id', type: 'integer', nullable: true)]
    private ?int $articleId = null;

    #[ORM\Column(type: 'float')]
    private float $amount;

    #[ORM\Column(type: 'float')]
    private float $price;

    #[ORM\Column(name: 'price_eur', type: 'float', nullable: true)]
    private ?float $priceEur = null;

    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(type: 'string', length: 2, nullable: true)]
    private ?string $measure = null;

    #[ORM\Column(type: 'float')]
    private float $weight = 0.0;

    #[ORM\Column(name: 'packaging_count', type: 'float')]
    private float $packagingCount = 1.0;

    #[ORM\Column(type: 'float')]
    private float $pallet = 1.0;

    #[ORM\Column(type: 'float')]
    private float $packaging = 1.0;

    #[ORM\Column(name: 'swimming_pool', type: 'boolean', options: ['default' => false])]
    private bool $swimmingPool = false;

    public function __construct(float $amount, float $price)
    {
        $this->amount = $amount;
        $this->price = $price;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
