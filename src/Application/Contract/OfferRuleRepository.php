<?php

declare(strict_types=1);

namespace WooOffers\Application\Contract;

use WooOffers\Domain\Offer\OfferRule;

interface OfferRuleRepository
{
    /**
     * @return OfferRule[]
     */
    public function all(): array;

    /**
     * @param OfferRule[] $rules
     */
    public function save(array $rules): void;
}
