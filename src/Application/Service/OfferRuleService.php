<?php

declare(strict_types=1);

namespace WooOffers\Application\Service;

use WooOffers\Application\Contract\OfferRuleRepository;
use WooOffers\Domain\Offer\OfferRule;

final class OfferRuleService
{
    public function __construct(private OfferRuleRepository $repository)
    {
    }

    /**
     * @return OfferRule[]
     */
    public function all(): array
    {
        return $this->repository->all();
    }

    /**
     * @return array<int, string>
     */
    public function allAsMap(): array
    {
        $map = [];

        foreach ($this->repository->all() as $rule) {
            $map[$rule->productId()] = $rule->offerPrice();
        }

        return $map;
    }

    /**
     * @param int[] $productIds
     * @param string[] $offerPrices
     * @param string[] $regularPrices
     */
    public function saveFromRequest(array $productIds, array $offerPrices, array $regularPrices): void
    {
        $rulesByProduct = [];

        foreach ($productIds as $index => $productId) {
            $id = absint($productId);
            $rawOfferPrice = isset($offerPrices[$index]) ? (string) $offerPrices[$index] : '';
            $rawRegularPrice = isset($regularPrices[$index]) ? (string) $regularPrices[$index] : '';
            $normalizedOffer = self::normalizePriceInput($rawOfferPrice);
            $normalizedRegular = self::normalizePriceInput($rawRegularPrice);

            if (
                $id <= 0
                || $normalizedOffer === ''
                || (float) $normalizedOffer <= 0.0
                || $normalizedRegular === ''
                || (float) $normalizedRegular <= 0.0
                || (float) $normalizedOffer >= (float) $normalizedRegular
            ) {
                continue;
            }

            $rulesByProduct[$id] = new OfferRule($id, (string) $normalizedOffer, (string) $normalizedRegular);
        }

        $this->repository->save(array_values($rulesByProduct));
    }

    /**
     * Accepts values like "129.990", "129,990", "129990" and decimal formats.
     */
    private static function normalizePriceInput(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return '';
        }

        $raw = preg_replace('/[^\d.,-]/', '', $raw) ?? '';
        if ($raw === '') {
            return '';
        }

        $lastDot = strrpos($raw, '.');
        $lastComma = strrpos($raw, ',');

        if ($lastDot !== false && $lastComma !== false) {
            $decimalSeparator = $lastDot > $lastComma ? '.' : ',';
            $thousandSeparator = $decimalSeparator === '.' ? ',' : '.';
            $normalized = str_replace($thousandSeparator, '', $raw);
            $normalized = str_replace($decimalSeparator, '.', $normalized);

            return wc_format_decimal($normalized, wc_get_price_decimals());
        }

        if ($lastDot !== false) {
            $decimalsLength = strlen($raw) - $lastDot - 1;
            $normalized = $decimalsLength > 0 && $decimalsLength <= 2
                ? $raw
                : str_replace('.', '', $raw);

            return wc_format_decimal($normalized, wc_get_price_decimals());
        }

        if ($lastComma !== false) {
            $decimalsLength = strlen($raw) - $lastComma - 1;
            $normalized = $decimalsLength > 0 && $decimalsLength <= 2
                ? str_replace(',', '.', $raw)
                : str_replace(',', '', $raw);

            return wc_format_decimal($normalized, wc_get_price_decimals());
        }

        return wc_format_decimal($raw, wc_get_price_decimals());
    }
}
