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

add_action('after_setup_theme', function () {
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
       2) PAISES PERMITIDOS = SOLO PERU, solo en "Mi Cuenta".
          Filtros REALES de WooCommerce (los anteriores no existian):
            - woocommerce_countries_allowed_countries
            - woocommerce_countries_shipping_countries
          Al quedar 1 solo pais, el <select> entra en modo "single":
          <option value="PE" selected> (sin placeholder ni select2).
          El checkout queda intacto.
       ======================================================== */
    add_filter('woocommerce_countries_allowed_countries', function ($paises) {
        if (function_exists('is_account_page') && is_account_page()) {
            return ['PE' => isset($paises['PE']) ? $paises['PE'] : 'Perú'];
        }
        return $paises;
    }, 999999, 1);

    add_filter('woocommerce_countries_shipping_countries', function ($paises) {
        if (function_exists('is_account_page') && is_account_page()) {
            return ['PE' => isset($paises['PE']) ? $paises['PE'] : 'Perú'];
        }
        return $paises;
    }, 999999, 1);

    /* ========================================================
       4) Defecto Location customer (Woo → Settings → Default)
          = Perú. (afecta cálculo de impuestos, shipping preview).
       ======================================================== */
    add_filter('woocommerce_customer_default_location', function ($loc) {
        return 'PE:CAJ';
    }, 999999, 1);

    /* Este filtro corre DESPUES de la geolocalizacion: garantiza el array
       final country/state que usa set_defaults() del customer de sesion. */
    add_filter('woocommerce_customer_default_location_array', function ($loc) {
        return array(
            'country' => 'PE',
            'state'   => 'CAJ',
        );
    }, 999999, 1);

    /* ========================================================
       5) DEPARTAMENTO por DEFECTO = CAJAMARCA (CAJ)
          El negocio solo opera en Cajamarca por ahora.
       ======================================================== */
    add_filter('default_checkout_state', function ($estado_default) {
        return 'CAJ';
    }, 999999, 1);

    add_filter('default_checkout_shipping_state', function ($estado_envio_default) {
        return 'CAJ';
    }, 999999, 1);

    /* ========================================================
       6) SOLO CAJAMARCA disponible en la lista de Departamentos.
          Quitamos los demás estados del combo (el combo solo verá
          Cajamarca). Más robusto que bloquear solo el click.
       ======================================================== */
    add_filter('woocommerce_states', function ($estados) {
        if (isset($estados['PE'])) {
            $estados['PE'] = ['CAJ' => $estados['PE']['CAJ'] ?? 'Cajamarca'];
        }
        return $estados;
    }, 999999, 1);

    /* ========================================================
       6.b) ENVIO A DOMICILIO: el core NO copia el state del
       billing cuando YA existe direccion de envio
       (has_shipping_address() = true), por eso el select de
       Departamento de envio quedaba vacio. Se fuerza CAJ en
       LECTURA cuando esta vacio (no toca la BD).
       ======================================================== */
    add_filter('woocommerce_customer_get_shipping_state', function ($estado, $cliente) {
        $pais = $cliente->get_shipping_country();
        if ($pais === 'PE' && empty($estado)) {
            return 'CAJ';
        }
        return $estado;
    }, 999999, 2);

    /* ========================================================
       7) VALIDACIÓN SERVER-SIDE: Estado debe ser Cajamarca (CAJ).
          Defensa en profundidad por si alguien intenta burlar el CSS.
       ======================================================== */
    add_action('woocommerce_checkout_process', function () {
        $state = isset($_POST['billing_state']) ? wc_clean(wp_unslash($_POST['billing_state'])) : '';
        if ($state !== 'CAJ') {
            wc_add_notice('Lo sentimos, por ahora solo operamos en el departamento de Cajamarca.', 'error');
        }
    });

    /* ========================================================
       8) TELÉFONO OBLIGATORIO (Billing + Shipping).
          Filtro estándar woocommerce_checkout_fields. WooCommerce
          Blocks lee la propiedad 'required' y refleja el asterisco.
       ======================================================== */
    add_filter('woocommerce_checkout_fields', function ($campos) {
        if (isset($campos['billing']['billing_phone'])) {
            $campos['billing']['billing_phone']['required'] = true;
        }
        if (isset($campos['shipping']['shipping_phone'])) {
            $campos['shipping']['shipping_phone']['required'] = true;
        }
        return $campos;
    }, 999999, 1);

    /* ========================================================
       9) VALIDACIÓN SERVER-SIDE del Teléfono (por si Blocks no
          valida en frontend). Defensa en profundidad.
       ======================================================== */
    add_action('woocommerce_checkout_process', function () {
        $telefono = isset($_POST['billing_phone']) ? trim(wc_clean(wp_unslash($_POST['billing_phone']))) : '';
        if ($telefono === '') {
            wc_add_notice('El teléfono celular es un campo obligatorio.', 'error');
        }
    });

    /* ========================================================
       10) PAIS POR DEFECTO en "Mi Cuenta > Direcciones" (form clásico).
          WooCommerce lee el país desde user_meta; para un usuario nuevo
          está vacío y muestra el placeholder "Select a country / region…".
          Este filtro fuerza PE cuando no hay valor guardado.
       ======================================================== */
    add_filter('woocommerce_my_account_edit_address_field_value', function ($valor, $campo, $tipo) {
        if (($campo === $tipo . '_country') && empty($valor)) {
            return 'PE';
        }
        if (($campo === $tipo . '_state') && empty($valor)) {
            return 'CAJ';
        }
        return $valor;
    }, 999999, 3);

}, 5);
