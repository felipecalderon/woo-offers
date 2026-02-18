<?php

declare(strict_types=1);

namespace WooOffers\Integrations;

final class WooCommerceCompat
{
    public function register(): void
    {
        add_action('before_woocommerce_init', [$this, 'declareHposCompatibility']);
    }

    public function declareHposCompatibility(): void
    {
        if (!class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            return;
        }

        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', WOO_OFFERS_FILE, true);
    }
}
