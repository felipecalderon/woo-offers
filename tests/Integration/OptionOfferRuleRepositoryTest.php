<?php

declare(strict_types=1);

namespace WooOffers\Tests\Integration;

use WooOffers\Domain\Offer\OfferRule;
use WooOffers\Infrastructure\Persistence\OptionOfferRuleRepository;

final class OptionOfferRuleRepositoryTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists('WooCommerce')) {
            $this->markTestSkipped('WooCommerce no disponible en entorno de test.');
        }
    }

    protected function tearDown(): void
    {
        delete_option(WOO_OFFERS_OPTION_RULES);
        parent::tearDown();
    }

    public function test_save_and_load_rules_roundtrip(): void
    {
        $repository = new OptionOfferRuleRepository();
        $repository->save([
            new OfferRule(15, '9.99', '15.00'),
            new OfferRule(20, '12.50', '20.00'),
        ]);

        $stored = get_option(WOO_OFFERS_OPTION_RULES, []);
        self::assertIsArray($stored);
        self::assertSame('9.99', $stored[15]['offer_price']);
        self::assertSame('15.00', $stored[15]['regular_price']);
        self::assertSame('12.50', $stored[20]['offer_price']);
        self::assertSame('20.00', $stored[20]['regular_price']);

        $loaded = $repository->all();

        self::assertCount(2, $loaded);
        self::assertSame(15, $loaded[0]->productId());
        self::assertSame('9.99', $loaded[0]->offerPrice());
        self::assertSame('15', $loaded[0]->regularPrice());
    }

    public function test_loads_legacy_scalar_format(): void
    {
        update_option(WOO_OFFERS_OPTION_RULES, [
            99 => '5.50',
        ], false);

        $repository = new OptionOfferRuleRepository();
        $loaded = $repository->all();

        self::assertCount(1, $loaded);
        self::assertSame(99, $loaded[0]->productId());
        self::assertSame('5.5', $loaded[0]->offerPrice());
        self::assertNull($loaded[0]->regularPrice());
    }
}
