<?php

declare(strict_types=1);

namespace WooOffers\Tests\Integration;

use WC_Product;
use WC_Product_Simple;
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

        if (!class_exists('WooCommerce') || !class_exists(WC_Product_Simple::class)) {
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
}
