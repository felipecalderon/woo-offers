<?php

declare(strict_types=1);

namespace WooOffers\Tests\Integration;

use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;
use WooOffers\Application\Service\ForcedOfferEvaluator;
use WooOffers\Domain\Offer\OfferRule;
use WooOffers\Infrastructure\WooCommerce\ForcedOfferPriceApplier;
use WooOffers\Tests\Support\InMemoryOfferRuleRepository;

require_once dirname(__DIR__) . '/Support/InMemoryOfferRuleRepository.php';

final class ForcedOfferPriceApplierTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (
            !class_exists('WooCommerce')
            || !class_exists(WC_Product_Simple::class)
            || !class_exists(WC_Product_Variable::class)
            || !class_exists(WC_Product_Variation::class)
        ) {
            $this->markTestSkipped('WooCommerce no disponible en entorno de test.');
        }
    }

    public function test_filter_price_returns_forced_offer_price_when_rule_applies(): void
    {
        $product = $this->createSimpleProduct(100.0);
        $repo = new InMemoryOfferRuleRepository([
            new OfferRule($product->get_id(), '89.90', '120.00'),
        ]);

        $applier = new ForcedOfferPriceApplier(new ForcedOfferEvaluator($repo));

        self::assertSame('89.9', $applier->filterPrice('100', $product));
        self::assertSame('120', $applier->filterRegularPrice('100', $product));
        self::assertTrue($applier->filterIsOnSale(false, $product));
        self::assertContains($product->get_id(), $applier->filterOnSaleProductIds([]));
    }

    public function test_variable_parent_is_flagged_on_sale_when_variation_has_forced_offer(): void
    {
        [$parent, $variation] = $this->createVariableProductWithVariation(100.0);
        $repo = new InMemoryOfferRuleRepository([
            new OfferRule($variation->get_id(), '89.90', '120.00'),
        ]);

        $applier = new ForcedOfferPriceApplier(new ForcedOfferEvaluator($repo));

        self::assertTrue($applier->filterIsOnSale(false, $parent));
        self::assertContains($variation->get_id(), $applier->filterOnSaleProductIds([]));
        self::assertContains($parent->get_id(), $applier->filterOnSaleProductIds([]));
    }

    public function test_inject_forced_ids_into_product_queries_marked_as_onsale(): void
    {
        $product = $this->createSimpleProduct(100.0);
        $repo = new InMemoryOfferRuleRepository([
            new OfferRule($product->get_id(), '89.90', '120.00'),
        ]);

        $applier = new ForcedOfferPriceApplier(new ForcedOfferEvaluator($repo));

        $query = new \WP_Query();
        $query->set('post_type', 'product');
        $query->set('post__in', [0]);

        $applier->injectForcedOnSaleIdsInQueries($query);

        $postIn = $query->get('post__in');
        self::assertIsArray($postIn);
        self::assertContains($product->get_id(), $postIn);
    }

    private function createSimpleProduct(float $price): WC_Product
    {
        $product = new WC_Product_Simple();
        $product->set_name('Integration Product');
        $product->set_regular_price((string) $price);
        $product->set_price((string) $price);
        $product->save();

        return wc_get_product($product->get_id());
    }

    /**
     * @return array{0: WC_Product, 1: WC_Product}
     */
    private function createVariableProductWithVariation(float $price): array
    {
        $parent = new WC_Product_Variable();
        $parent->set_name('Parent Variable Product');
        $parent->save();

        $variation = new WC_Product_Variation();
        $variation->set_parent_id($parent->get_id());
        $variation->set_regular_price((string) $price);
        $variation->set_price((string) $price);
        $variation->save();

        return [
            wc_get_product($parent->get_id()),
            wc_get_product($variation->get_id()),
        ];
    }
}
