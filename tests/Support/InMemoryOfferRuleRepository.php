<?php

declare(strict_types=1);

namespace WooOffers\Tests\Support;

use WooOffers\Application\Contract\OfferRuleRepository;
use WooOffers\Domain\Offer\OfferRule;

final class InMemoryOfferRuleRepository implements OfferRuleRepository
{
    /**
     * @var OfferRule[]
     */
    private array $rules;

    /**
     * @param OfferRule[] $rules
     */
    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    /**
     * @return OfferRule[]
     */
    public function all(): array
    {
        return $this->rules;
    }

    /**
     * @param OfferRule[] $rules
     */
    public function save(array $rules): void
    {
        $this->rules = $rules;
    }
}
