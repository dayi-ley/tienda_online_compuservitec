<?php
/**
 * MÓDULO: 70-checkout-solo-peru.php
 * User request explícito: "funcionara solo para peru, por defecto y bloqueado
 * para que no haya errores, solo debe estar Peru".
 *
 * TÉCNICA = FILTROS OFICIALES WOOCOMMERCE (PHP CORE)
 *   => NO depende de CSS, NO se puede burlar por DevTools/consola.
 *   => Woo NUNCA envía otros países al <select> = SOLO Perú (PE)
 * Hooks: plugins_loaded (después de que Woo cargue).
 *
 * NIVEL 1 SEGURIDAD (PHP):
 *   - woocommerce_allowed_countries = 'specific'
 *   - woocommerce_specific_allowed_countries = ['PE']
 *   - default_checkout_country = 'PE' (defecto al entrar al checkout)
 *   - woocommerce_shipping_countries = 'specific'
 *   - woocommerce_specific_shipping_countries = ['PE']
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) return;

    /* ========================================================
       1) PAÍS por DEFECTO al entrar al Checkout = PERÚ (PE)
       ======================================================== */
    add_filter('default_checkout_country', function ($pais_default) {
        return 'PE';
    }, 999999, 1);

    add_filter('default_checkout_shipping_country', function ($pais_envio_default) {
        return 'PE';
    }, 999999, 1);

    /* ========================================================
       2) PAISES PERMITIDOS en TODO el Sistema (Billing + Checkout Admin)
          = SOLO PERÚ. Otros 249 países NUNCA LLEGAN al HTML del combo.
       ======================================================== */
    add_filter('woocommerce_allowed_countries', function ($modo) {
        // 'specific' = usamos la lista whitelist de specific_allowed_countries
        return 'specific';
    }, 999999, 1);

    add_filter('woocommerce_specific_allowed_countries', function ($lista) {
        // ÚNICO país permitido = Perú
        return ['PE'];
    }, 999999, 1);

    /* ========================================================
       3) PAISES DE ENVÍO (Shipping) = SOLO PERÚ
          (Por si en el futuro hay cálculo de envío).
       ======================================================== */
    add_filter('woocommerce_shipping_countries', function ($modo_envio) {
        return 'specific';
    }, 999999, 1);

    add_filter('woocommerce_specific_shipping_countries', function ($lista_envio) {
        return ['PE'];
    }, 999999, 1);

    /* ========================================================
       4) Defecto Location customer (Woo → Settings → Default)
          = Perú. (afecta cálculo de impuestos, shipping preview).
       ======================================================== */
    add_filter('woocommerce_customer_default_location', function ($loc) {
        return 'PE';
    }, 999999, 1);

}, 5);
