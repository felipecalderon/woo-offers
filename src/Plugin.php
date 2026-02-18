<?php

declare(strict_types=1);

namespace WooOffers;

use WooOffers\Application\Service\ForcedOfferEvaluator;
use WooOffers\Application\Service\OfferRuleService;
use WooOffers\Infrastructure\Persistence\OptionOfferRuleRepository;
use WooOffers\Infrastructure\WooCommerce\ForcedOfferPriceApplier;
use WooOffers\Integrations\WooCommerceCompat;
use WooOffers\Presentation\Admin\AdminHooks;

final class Plugin
{
    private static bool $booted = false;
    private static bool $requirementsNoticeHooked = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        self::loadTextDomain();

        $issues = self::environmentIssues();

        if ($issues !== []) {
            self::logIssues('boot_environment_invalid', $issues);
            self::hookRequirementsNotice();
            return;
        }

        $repository = new OptionOfferRuleRepository();
        $ruleService = new OfferRuleService($repository);
        $evaluator = new ForcedOfferEvaluator($repository);

        (new WooCommerceCompat())->register();
        (new ForcedOfferPriceApplier($evaluator))->register();
        (new AdminHooks($ruleService, $evaluator))->register();
    }

    public static function activate(): void
    {
        $issues = self::environmentIssues();

        if ($issues !== []) {
            self::logIssues('activation_environment_invalid', $issues);

            // Keep plugin activation non-fatal and show contextual notice in admin.
            self::hookRequirementsNotice();
        }
    }

    public static function deactivate(): void
    {
        // Reservado para limpiar tareas programadas, transients, etc.
    }

    private static function loadTextDomain(): void
    {
        load_plugin_textdomain('woo-offers', false, dirname(plugin_basename(WOO_OFFERS_FILE)) . '/languages');
    }

    /**
     * @return string[]
     */
    private static function environmentIssues(): array
    {
        global $wp_version;

        $issues = [];
        $currentWpVersion = (string) ($wp_version ?? '');

        if (!version_compare((string) PHP_VERSION, WOO_OFFERS_MIN_PHP, '>=')) {
            $issues[] = sprintf('PHP actual %s, minimo requerido %s.', PHP_VERSION, WOO_OFFERS_MIN_PHP);
        }

        if ($currentWpVersion === '' || !version_compare($currentWpVersion, WOO_OFFERS_MIN_WP, '>=')) {
            $issues[] = sprintf('WordPress actual %s, minimo requerido %s.', $currentWpVersion !== '' ? $currentWpVersion : 'desconocido', WOO_OFFERS_MIN_WP);
        }

        if (!class_exists('WooCommerce')) {
            $issues[] = 'WooCommerce no esta cargado o no esta activo.';
        } elseif (!defined('WC_VERSION')) {
            $issues[] = 'No se detecto WC_VERSION.';
        } elseif (!version_compare((string) WC_VERSION, WOO_OFFERS_MIN_WC, '>=')) {
            $issues[] = sprintf('WooCommerce actual %s, minimo requerido %s.', WC_VERSION, WOO_OFFERS_MIN_WC);
        }

        return $issues;
    }

    private static function hookRequirementsNotice(): void
    {
        if (self::$requirementsNoticeHooked) {
            return;
        }

        self::$requirementsNoticeHooked = true;
        add_action('admin_notices', [self::class, 'renderRequirementsNotice']);
    }

    /**
     * @param string[] $issues
     */
    private static function logIssues(string $context, array $issues): void
    {
        $message = sprintf(
            '[Woo Offers] %s | %s',
            $context,
            implode(' | ', $issues)
        );

        error_log($message);
    }

    public static function renderRequirementsNotice(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        $issues = self::environmentIssues();

        echo '<div class="notice notice-error"><p>'
            . esc_html(
                sprintf(
                    __('Woo Offers requiere WordPress %s+, PHP %s+ y WooCommerce %s+.', 'woo-offers'),
                    WOO_OFFERS_MIN_WP,
                    WOO_OFFERS_MIN_PHP,
                    WOO_OFFERS_MIN_WC
                )
            )
            . '</p>';

        if ($issues !== []) {
            echo '<p><strong>' . esc_html__('Detalle detectado:', 'woo-offers') . '</strong></p><ul style="list-style:disc; margin-left:1.5em;">';

            foreach ($issues as $issue) {
                echo '<li>' . esc_html($issue) . '</li>';
            }

            echo '</ul>';
        }

        echo '</div>';
    }
}
