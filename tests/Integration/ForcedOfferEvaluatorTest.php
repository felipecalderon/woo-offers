<?php

declare(strict_types=1);

namespace WooOffers\Tests\Integration;

use WC_Product;
use WC_Product_Simple;
use WooOffers\Application\Service\ForcedOfferEvaluator;
use WooOffers\Domain\Offer\OfferRule;
use WooOffers\Tests\Support\InMemoryOfferRuleRepository;

require_once dirname(__DIR__) . '/Support/InMemoryOfferRuleRepository.php';

final class ForcedOfferEvaluatorTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists('WooCommerce') || !class_exists(WC_Product_Simple::class)) {
            $this->markTestSkipped('WooCommerce no disponible en entorno de test.');
        }
    }

    public function test_forced_price_is_applied_when_offer_is_lower_than_current_price(): void
    {
        $product = $this->createSimpleProduct(100.0);
        $repo = new InMemoryOfferRuleRepository([
            new OfferRule($product->get_id(), '80.00'),
        ]);

        $evaluator = new ForcedOfferEvaluator($repo);

        self::assertSame('80', $evaluator->forcedPriceFor($product));
    }

    public function test_forced_price_is_not_applied_when_offer_is_greater_or_equal(): void
    {
        $product = $this->createSimpleProduct(100.0);

        $repoEqual = new InMemoryOfferRuleRepository([
            new OfferRule($product->get_id(), '100.00'),
        ]);
        $repoGreater = new InMemoryOfferRuleRepository([
            new OfferRule($product->get_id(), '120.00'),
        ]);

        $equalEvaluator = new ForcedOfferEvaluator($repoEqual);
        $greaterEvaluator = new ForcedOfferEvaluator($repoGreater);

        self::assertNull($equalEvaluator->forcedPriceFor($product));
        self::assertNull($greaterEvaluator->forcedPriceFor($product));
    }

    public function test_forced_price_uses_configured_regular_price_when_present(): void
    {
        $product = $this->createSimpleProduct(100.0);
        $repo = new InMemoryOfferRuleRepository([
            new OfferRule($product->get_id(), '110.00', '120.00'),
        ]);

        $evaluator = new ForcedOfferEvaluator($repo);

        self::assertSame('110', $evaluator->forcedPriceFor($product));
        self::assertSame('120', $evaluator->forcedRegularPriceFor($product));
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
