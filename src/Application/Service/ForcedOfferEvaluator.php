<?php

declare(strict_types=1);

namespace WooOffers\Application\Service;

use WC_Product;
use WooOffers\Application\Contract\OfferRuleRepository;

final class ForcedOfferEvaluator
{
    /**
     * @var array<int, array{offer:string,regular:?string}>|null
     */
    private ?array $ruleMap = null;

    public function __construct(private OfferRuleRepository $repository)
    {
    }

    public function forcedPriceFor(WC_Product $product): ?string
    {
        $productId = $product->get_id();
        $rule = $this->rules()[$productId] ?? null;

        if ($rule === null) {
            return null;
        }

        $offerPrice = (float) wc_format_decimal($rule['offer'], wc_get_price_decimals());
        $regularPrice = $this->normalizedRegularPrice($product, $rule['regular']);

        if ($regularPrice === null) {
            return null;
        }

        if ($offerPrice <= 0.0 || $offerPrice >= $regularPrice) {
            return null;
        }

        return (string) wc_format_decimal((string) $offerPrice, wc_get_price_decimals());
    }

    public function forcedRegularPriceFor(WC_Product $product): ?string
    {
        $productId = $product->get_id();
        $rule = $this->rules()[$productId] ?? null;

        if ($rule === null) {
            return null;
        }

        $offerPrice = (float) wc_format_decimal($rule['offer'], wc_get_price_decimals());
        $regularPrice = $this->normalizedRegularPrice($product, $rule['regular']);

        if ($regularPrice === null || $offerPrice <= 0.0 || $offerPrice >= $regularPrice) {
            return null;
        }

        return (string) wc_format_decimal((string) $regularPrice, wc_get_price_decimals());
    }

    /**
     * @return int[]
     */
    public function forcedOnSaleProductIds(): array
    {
        $ids = [];

        foreach (array_keys($this->rules()) as $productId) {
            $product = wc_get_product($productId);
            if (!$product instanceof WC_Product) {
                continue;
            }

            if ($this->forcedPriceFor($product) !== null) {
                $ids[] = $productId;
            }
        }

        return $ids;
    }

    private function currentBasePrice(WC_Product $product): ?float
    {
        $rawPrice = (string) $product->get_price('edit');
        if ($rawPrice === '') {
            $rawPrice = (string) $product->get_regular_price('edit');
        }

        if ($rawPrice === '') {
            return null;
        }

        $value = (float) wc_format_decimal($rawPrice, wc_get_price_decimals());

        return $value > 0.0 ? $value : null;
    }

    private function normalizedRegularPrice(WC_Product $product, ?string $configuredRegular): ?float
    {
        if ($configuredRegular !== null && $configuredRegular !== '') {
            $value = (float) wc_format_decimal($configuredRegular, wc_get_price_decimals());
            if ($value > 0.0) {
                return $value;
            }
        }

        return $this->currentBasePrice($product);
    }

    /**
     * @return array<int, array{offer:string,regular:?string}>
     */
    private function rules(): array
    {
        if ($this->ruleMap !== null) {
            return $this->ruleMap;
        }

        $this->ruleMap = [];

        foreach ($this->repository->all() as $rule) {
            $this->ruleMap[$rule->productId()] = [
                'offer' => $rule->offerPrice(),
                'regular' => $rule->regularPrice(),
            ];
        }

        return $this->ruleMap;
    }
}
