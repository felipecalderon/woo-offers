<?php

declare(strict_types=1);

namespace WooOffers\Domain\Offer;

final class OfferRule
{
    public function __construct(
        private int $productId,
        private string $offerPrice,
        private ?string $regularPrice = null
    ) {
    }

    public function productId(): int
    {
        return $this->productId;
    }

    public function offerPrice(): string
    {
        return $this->offerPrice;
    }

    public function regularPrice(): ?string
    {
        return $this->regularPrice;
    }
}
