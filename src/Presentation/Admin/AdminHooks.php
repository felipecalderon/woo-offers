<?php

declare(strict_types=1);

namespace WooOffers\Presentation\Admin;

use WC_Product;
use WooOffers\Application\Service\ForcedOfferEvaluator;
use WooOffers\Application\Service\OfferRuleService;

final class AdminHooks
{
    public function __construct(
        private OfferRuleService $ruleService,
        private ForcedOfferEvaluator $evaluator
    ) {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_post_woo_offers_save_rules', [$this, 'handleSave']);
        add_action('wp_ajax_woo_offers_products_context', [$this, 'handleProductsContext']);
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Woo Offers', 'woo-offers'),
            __('Woo Offers', 'woo-offers'),
            'manage_woocommerce',
            'woo-offers',
            [$this, 'renderSettingsPage']
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'woocommerce_page_woo-offers') {
            return;
        }

        wp_enqueue_style('woocommerce_admin_styles');
        wp_enqueue_script('wc-enhanced-select');

        wp_enqueue_style(
            'woo-offers-admin',
            WOO_OFFERS_URL . 'assets/css/admin.css',
            [],
            WOO_OFFERS_VERSION
        );

        wp_enqueue_script(
            'woo-offers-admin',
            WOO_OFFERS_URL . 'assets/js/admin.js',
            ['jquery', 'wc-enhanced-select'],
            WOO_OFFERS_VERSION,
            true
        );

        wp_localize_script('woo-offers-admin', 'WooOffersAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('woo_offers_admin_nonce'),
            'removeLabel' => __('Quitar', 'woo-offers'),
            'pendingLabel' => __('Pendiente de guardar', 'woo-offers'),
            'appliedLabel' => __('Aplicada', 'woo-offers'),
            'invalidLabel' => __('No aplicada (la oferta debe ser menor al precio normal)', 'woo-offers'),
            'unknownLabel' => __('No validable (producto sin precio normal)', 'woo-offers'),
            'invalidFormMessage' => __('Hay reglas invalidas: el precio oferta debe ser menor al precio normal.', 'woo-offers'),
            'naLabel' => ' - ',
        ]);
    }

    public function handleSave(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('No tienes permisos para esta accion.', 'woo-offers'));
        }

        check_admin_referer('woo_offers_save_rules');

        $productIds = isset($_POST['product_ids']) ? array_map('absint', (array) wp_unslash($_POST['product_ids'])) : [];
        $offerPrices = isset($_POST['offer_prices']) ? array_map('sanitize_text_field', (array) wp_unslash($_POST['offer_prices'])) : [];
        $regularPrices = isset($_POST['regular_prices']) ? array_map('sanitize_text_field', (array) wp_unslash($_POST['regular_prices'])) : [];

        $this->ruleService->saveFromRequest($productIds, $offerPrices, $regularPrices);

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'woo-offers',
                    'updated' => '1',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public function handleProductsContext(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('No autorizado.', 'woo-offers')], 403);
        }

        check_ajax_referer('woo_offers_admin_nonce', 'nonce');

        $ids = isset($_POST['ids']) ? array_map('absint', (array) wp_unslash($_POST['ids'])) : [];
        $ids = array_values(array_filter(array_unique($ids), static fn (int $id): bool => $id > 0));

        $items = [];

        foreach ($ids as $id) {
            $product = wc_get_product($id);
            if (!$product instanceof WC_Product) {
                continue;
            }

            $currentRaw = $this->getCurrentPriceRaw($product);
            $regularRaw = $this->getRegularPriceRaw($product);

            $items[] = [
                'product_id' => $product->get_id(),
                'name' => wp_strip_all_tags($product->get_formatted_name()),
                'sku' => (string) $product->get_sku(),
                'current_price' => $this->formatPricePlain($currentRaw),
                'current_price_raw' => $currentRaw,
                'regular_price_raw' => $regularRaw,
                'regular_price_input' => $this->formatPriceInput($regularRaw),
            ];
        }

        wp_send_json_success(['items' => $items]);
    }

    public function renderSettingsPage(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $rows = $this->buildRows();
        $updated = isset($_GET['updated']) && $_GET['updated'] === '1';

        include WOO_OFFERS_PATH . 'templates/admin-settings.php';
    }

    /**
     * @return array<int, array<string, string|int|float|null>>
     */
    private function buildRows(): array
    {
        $rows = [];

        foreach ($this->ruleService->all() as $rule) {
            $product = wc_get_product($rule->productId());
            if (!$product instanceof WC_Product) {
                continue;
            }

            $currentRaw = $this->getCurrentPriceRaw($product);
            $regularRaw = $this->getRegularPriceRaw($product);
            $forcedPrice = $this->evaluator->forcedPriceFor($product);

            $rows[] = [
                'product_id' => $product->get_id(),
                'name' => wp_strip_all_tags($product->get_formatted_name()),
                'sku' => (string) $product->get_sku(),
                'current_price' => $this->formatPricePlain($currentRaw),
                'current_price_raw' => $currentRaw,
                'offer_price' => $this->formatPriceInput($rule->offerPrice()),
                'regular_price' => $this->formatPriceInput($rule->regularPrice() ?? $regularRaw),
                'status' => $forcedPrice !== null
                    ? __('Aplicada', 'woo-offers')
                    : __('No aplicada (la oferta debe ser menor al precio normal)', 'woo-offers'),
                'status_type' => $forcedPrice !== null ? 'applied' : 'invalid',
            ];
        }

        return $rows;
    }

    private function getCurrentPriceRaw(WC_Product $product): ?float
    {
        $price = (string) $product->get_price('edit');
        if ($price === '') {
            $price = (string) $product->get_regular_price('edit');
        }

        if ($price === '') {
            return null;
        }

        $value = (float) wc_format_decimal($price, wc_get_price_decimals());

        return $value > 0.0 ? $value : null;
    }

    private function getRegularPriceRaw(WC_Product $product): ?float
    {
        $price = (string) $product->get_regular_price('edit');
        if ($price === '') {
            $price = (string) $product->get_price('edit');
        }

        if ($price === '') {
            return null;
        }

        $value = (float) wc_format_decimal($price, wc_get_price_decimals());

        return $value > 0.0 ? $value : null;
    }

    private function formatPricePlain(?float $price): string
    {
        if ($price === null) {
            return ' - ';
        }

        return html_entity_decode(
            wp_strip_all_tags(wc_price($price)),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    private function formatPriceInput(string|float|null $price): string
    {
        if ($price === null || $price === '') {
            return '';
        }

        return (string) wc_format_decimal((string) $price, wc_get_price_decimals());
    }
}
