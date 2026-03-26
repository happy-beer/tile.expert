<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private string $hash;

    #[ORM\Column(length: 64)]
    private string $token;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $status = 1;

    #[ORM\Column(name: 'vat_type', type: 'integer', options: ['default' => 0])]
    private int $vatType = 0;

    #[ORM\Column(name: 'pay_type', type: 'smallint')]
    private int $payType;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 5)]
    private string $locale = 'it';

    #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
    private string $currency = 'EUR';

    #[ORM\Column(length: 3, options: ['default' => 'm'])]
    private string $measure = 'm';

    #[ORM\Column(length: 200)]
    private string $name;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'create_date', type: 'datetime_immutable')]
    private \DateTimeImmutable $createDate;

    #[ORM\Column(name: 'update_date', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updateDate = null;

    #[ORM\Column(name: 'delivery_price_euro', type: 'float', nullable: true)]
    private ?float $deliveryPriceEuro = null;

    /**
     * @var Collection<int, OrderArticle>
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderArticle::class)]
    private Collection $articles;

    public function __construct(
        string $hash,
        string $token,
        int $payType,
        string $name,
        \DateTimeImmutable $createDate,
    ) {
        $this->hash = $hash;
        $this->token = $token;
        $this->payType = $payType;
        $this->name = $name;
        $this->createDate = $createDate;
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreateDate(): \DateTimeImmutable
    {
        return $this->createDate;
    }

    public function getUpdateDate(): ?\DateTimeImmutable
    {
        return $this->updateDate;
    }

    public function setUpdateDate(?\DateTimeImmutable $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getDeliveryPriceEuro(): ?float
    {
        return $this->deliveryPriceEuro;
    }

    public function setDeliveryPriceEuro(?float $deliveryPriceEuro): self
    {
        $this->deliveryPriceEuro = $deliveryPriceEuro;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->deliveryPriceEuro;
    }

    public function setTotal(?float $total): self
    {
        $this->deliveryPriceEuro = $total;

        return $this;
    }
}
