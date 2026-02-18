<?php

declare(strict_types=1);

namespace WooOffers\Infrastructure\Persistence;

use WooOffers\Application\Contract\OfferRuleRepository;
use WooOffers\Domain\Offer\OfferRule;

final class OptionOfferRuleRepository implements OfferRuleRepository
{
    /**
     * @return OfferRule[]
     */
    public function all(): array
    {
        $stored = get_option(WOO_OFFERS_OPTION_RULES, []);
        if (!is_array($stored)) {
            return [];
        }

        $rules = [];

        foreach ($stored as $rawProductId => $rawRule) {
            $productId = absint($rawProductId);
            $rawOfferPrice = is_array($rawRule) ? ($rawRule['offer_price'] ?? '') : $rawRule;
            $rawRegularPrice = is_array($rawRule) ? ($rawRule['regular_price'] ?? '') : '';

            $offerPrice = wc_format_decimal((string) $rawOfferPrice, wc_get_price_decimals());
            $regularPrice = wc_format_decimal((string) $rawRegularPrice, wc_get_price_decimals());

            if ($productId <= 0 || $offerPrice === '' || (float) $offerPrice <= 0.0) {
                continue;
            }

            $rules[] = new OfferRule(
                $productId,
                (string) $offerPrice,
                $regularPrice !== '' && (float) $regularPrice > 0.0 ? (string) $regularPrice : null
            );
        }

        return $rules;
    }

    /**
     * @param OfferRule[] $rules
     */
    public function save(array $rules): void
    {
        $payload = [];

        foreach ($rules as $rule) {
            $payload[$rule->productId()] = [
                'offer_price' => $rule->offerPrice(),
                'regular_price' => $rule->regularPrice() ?? '',
            ];
        }

        update_option(WOO_OFFERS_OPTION_RULES, $payload, false);
    }
}
