<?php

declare(strict_types=1);

namespace WooOffers\Infrastructure\WooCommerce;

use WC_Cart;
use WC_Product;
use WooOffers\Application\Service\ForcedOfferEvaluator;

final class ForcedOfferPriceApplier
{
    public function __construct(private ForcedOfferEvaluator $evaluator)
    {
    }

    public function register(): void
    {
        add_filter('woocommerce_product_get_price', [$this, 'filterPrice'], 9999, 2);
        add_filter('woocommerce_product_variation_get_price', [$this, 'filterPrice'], 9999, 2);
        add_filter('woocommerce_product_get_regular_price', [$this, 'filterRegularPrice'], 9999, 2);
        add_filter('woocommerce_product_variation_get_regular_price', [$this, 'filterRegularPrice'], 9999, 2);
        add_filter('woocommerce_product_get_sale_price', [$this, 'filterSalePrice'], 9999, 2);
        add_filter('woocommerce_product_variation_get_sale_price', [$this, 'filterSalePrice'], 9999, 2);
        add_filter('woocommerce_product_is_on_sale', [$this, 'filterIsOnSale'], 9999, 2);
        add_filter('woocommerce_product_variation_is_on_sale', [$this, 'filterIsOnSale'], 9999, 2);
        add_action('woocommerce_before_calculate_totals', [$this, 'applyCartPrice'], 9999);
    }

    /**
     * @param mixed $price
     * @return mixed
     */
    public function filterPrice($price, WC_Product $product)
    {
        $forced = $this->evaluator->forcedPriceFor($product);

        return $forced ?? $price;
    }

    /**
     * @param mixed $salePrice
     * @return mixed
     */
    public function filterSalePrice($salePrice, WC_Product $product)
    {
        $forced = $this->evaluator->forcedPriceFor($product);

        return $forced ?? $salePrice;
    }

    /**
     * @param mixed $regularPrice
     * @return mixed
     */
    public function filterRegularPrice($regularPrice, WC_Product $product)
    {
        $forced = $this->evaluator->forcedRegularPriceFor($product);

        return $forced ?? $regularPrice;
    }

    public function filterIsOnSale(bool $onSale, WC_Product $product): bool
    {
        return $this->evaluator->forcedPriceFor($product) !== null ? true : $onSale;
    }

    public function applyCartPrice(WC_Cart $cart): void
    {
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }

        foreach ($cart->get_cart() as $cartItem) {
            if (!isset($cartItem['data']) || !$cartItem['data'] instanceof WC_Product) {
                continue;
            }

            $product = $cartItem['data'];
            $forced = $this->evaluator->forcedPriceFor($product);
            $forcedRegular = $this->evaluator->forcedRegularPriceFor($product);

            if ($forced !== null && $forcedRegular !== null) {
                $product->set_price($forced);
                if (method_exists($product, 'set_regular_price')) {
                    $product->set_regular_price($forcedRegular);
                }
                if (method_exists($product, 'set_sale_price')) {
                    $product->set_sale_price($forced);
                }
            }
        }
    }
}
