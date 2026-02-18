<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Woo Offers', 'woo-offers'); ?></h1>
    <p><?php esc_html_e('Selecciona productos por nombre o SKU y define un precio normal y un precio oferta forzados.', 'woo-offers'); ?></p>
    <p><?php esc_html_e('La regla solo se aplica cuando el precio oferta es menor al precio normal.', 'woo-offers'); ?></p>

    <?php if (!empty($updated)) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Reglas guardadas.', 'woo-offers'); ?></p>
        </div>
    <?php endif; ?>

    <div id="woo-offers-validation-message" class="notice notice-error" style="display:none;">
        <p></p>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="woo-offers-admin-form" id="woo-offers-admin-form">
        <?php wp_nonce_field('woo_offers_save_rules'); ?>
        <input type="hidden" name="action" value="woo_offers_save_rules" />

        <div class="woo-offers-toolbar">
            <label for="woo-offers-product-search" class="screen-reader-text">
                <?php esc_html_e('Buscar productos', 'woo-offers'); ?>
            </label>
            <select
                id="woo-offers-product-search"
                class="wc-product-search"
                multiple="multiple"
                style="width: 480px;"
                data-placeholder="<?php esc_attr_e('Buscar por nombre o SKU', 'woo-offers'); ?>"
                data-action="woocommerce_json_search_products_and_variations"
            ></select>
            <button type="button" class="button button-secondary" id="woo-offers-add-products">
                <?php esc_html_e('Agregar a la lista', 'woo-offers'); ?>
            </button>
        </div>

        <div class="woo-offers-table-wrap">
            <table class="widefat striped woo-offers-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Producto', 'woo-offers'); ?></th>
                        <th><?php esc_html_e('SKU', 'woo-offers'); ?></th>
                        <th><?php esc_html_e('Precio actual', 'woo-offers'); ?></th>
                        <th><?php esc_html_e('Precio normal', 'woo-offers'); ?></th>
                        <th><?php esc_html_e('Precio oferta', 'woo-offers'); ?></th>
                        <th><?php esc_html_e('Estado', 'woo-offers'); ?></th>
                        <th><?php esc_html_e('Accion', 'woo-offers'); ?></th>
                    </tr>
                </thead>
                <tbody id="woo-offers-rules-body">
                    <?php foreach ($rows as $row) : ?>
                        <tr
                            data-product-id="<?php echo esc_attr((string) $row['product_id']); ?>"
                            data-current-price="<?php echo esc_attr($row['current_price_raw'] !== null ? (string) $row['current_price_raw'] : ''); ?>"
                        >
                            <td class="woo-col-product">
                                <?php echo esc_html((string) $row['name']); ?>
                                <input type="hidden" name="product_ids[]" value="<?php echo esc_attr((string) $row['product_id']); ?>" />
                            </td>
                            <td class="woo-col-sku"><?php echo esc_html((string) $row['sku']); ?></td>
                            <td class="woo-col-current-price"><?php echo esc_html((string) $row['current_price']); ?></td>
                            <td>
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    class="small-text woo-offers-price-input"
                                    name="regular_prices[]"
                                    value="<?php echo esc_attr((string) $row['regular_price']); ?>"
                                    required
                                />
                            </td>
                            <td>
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    class="small-text woo-offers-price-input"
                                    name="offer_prices[]"
                                    value="<?php echo esc_attr((string) $row['offer_price']); ?>"
                                    required
                                />
                            </td>
                            <td class="woo-offers-status woo-offers-status--<?php echo esc_attr((string) $row['status_type']); ?>"><?php echo esc_html((string) $row['status']); ?></td>
                            <td>
                                <button type="button" class="button-link-delete woo-offers-remove-row">
                                    <?php esc_html_e('Quitar', 'woo-offers'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php submit_button(__('Guardar reglas', 'woo-offers')); ?>
    </form>
</div>
