<?php
// 30-woocommerce.php · breadcrumb + gateways pago + fixes myaccount (backup L767-L911)
if (!defined('ABSPATH')) exit;

/* ==========================================================================
   FIX: Breadcrumb WooCommerce: "Inicio > Tienda" en vez de "Inicio > Product"
   (el nombre "Tienda" viene de la página wc_get_page_id('shop'). Si no hay
   página Shop, se pone "Tienda" como fallback.)
   ========================================================================== */
if ( ! function_exists( 'storex_child_fix_breadcrumb_tienda_nombre' ) ) :
	function storex_child_fix_breadcrumb_tienda_nombre( $defaults ) {
		$shop_id = ( function_exists( 'wc_get_page_id' ) ) ? wc_get_page_id( 'shop' ) : -1;
		if ( $shop_id > 0 ) {
			$shop_title = get_the_title( $shop_id );
			if ( $shop_title ) {
				$defaults['shop'] = $shop_title;
			}
		}
		if ( empty( $defaults['shop'] ) || 'Product' === $defaults['shop'] ) {
			$defaults['shop'] = 'Tienda';
		}
		$defaults['home'] = 'Inicio';
		return $defaults;
	}
endif;
add_filter( 'woocommerce_breadcrumb_defaults', 'storex_child_fix_breadcrumb_tienda_nombre', 999999 );

/* ==========================================================================
   FIX CRÍTICO: Desactivar método COD (Cash On Delivery / Pago Contra Entrega)
   porque rompe el JS de Woo Blocks en Checkout y tira el error:
   "There was an error registering the payment method with id 'cod':
    TypeError: Cannot read properties of undefined (reading 'length')"
   El cliente usa SÓLO Transferencia Bancaria + Yape + Plin, así que COD
   no es necesario. Lo eliminamos del array $gateways para que NUNCA
   se registre ni se intente renderizar. Si alguna vez quiere recuperar
   este método de pago, solo hay que comentar el add_filter.
   ========================================================================== */
add_filter( 'woocommerce_payment_gateways', 'storex_child_desactivar_cod_gateway', 999999, 1 );
function storex_child_desactivar_cod_gateway( $gateways ) {
	// unset por ID del gateway. Si existen variantes, las borramos todas.
	if ( isset( $gateways['cod'] ) ) {
		unset( $gateways['cod'] );
	}
	// También chequeo por índice numérico por si acaso StoreX/Woo mezcla arrays.
	foreach ( $gateways as $idx => $gw ) {
		if ( is_string( $gw ) && 'cod' === $gw ) {
			unset( $gateways[ $idx ] );
		} elseif ( is_object( $gw ) && isset( $gw->id ) && 'cod' === $gw->id ) {
			unset( $gateways[ $idx ] );
		}
	}
	// Re-indexar para evitar huecos (aunque Woo no lo exige, limpieza).
	return is_array( $gateways ) ? array_values( $gateways ) : $gateways;
}
// OPCIONAL (por si acaso el hook anterior no lo atrapa): desactivar opciones de DB.
add_action( 'template_redirect', 'storex_child_fuerza_desactivar_cod_option', 1 );
function storex_child_fuerza_desactivar_cod_option() {
	if ( function_exists( 'wc' ) && ( is_checkout() || is_checkout_pay_page() || is_cart() ) ) {
		$opciones = get_option( 'woocommerce_cod_settings', null );
		if ( is_array( $opciones ) && ( ! isset( $opciones['enabled'] ) || 'yes' === $opciones['enabled'] ) ) {
			$opciones['enabled'] = 'no';
			update_option( 'woocommerce_cod_settings', $opciones, true );
		}
	}
}

/* ==========================================================================
   FIX: Página My Account (Mi Cuenta) — filtros dedicados.
   1) Menú lateral Woo: cambiar "Inquiries" (añadido por Burger/StoreX) por "Consultas".
   2) Forzar el TÍTULO DE LA PÁGINA a "Mi Cuenta" (hero banner breadcrumb title H1)
      por si el usuario dejó escrito "My account" manualmente en Páginas → Título.
   ========================================================================== */
if ( ! function_exists( 'storex_child_fix_myaccount_menu' ) ) :
	function storex_child_fix_myaccount_menu( $items ) {
		if ( isset( $items['inquiries'] ) ) {
			$items['inquiries'] = 'Consultas';
		} elseif ( is_array( $items ) ) {
			foreach ( $items as $k => $v ) {
				if ( is_string( $v ) && false !== stripos( $v, 'Inquiries' ) ) {
					$items[ $k ] = str_replace( array( 'Inquiries', 'inquiries' ), array( 'Consultas', 'consultas' ), $v );
				}
			}
		}
		return $items;
	}
endif;
add_filter( 'woocommerce_account_menu_items', 'storex_child_fix_myaccount_menu', 999999, 1 );
add_filter( 'woocommerce_get_account_menu_items', 'storex_child_fix_myaccount_menu', 999999, 1 );

if ( ! function_exists( 'storex_child_fix_myaccount_title' ) ) :
	function storex_child_fix_myaccount_title( $title, $post_id = 0 ) {
		if ( ! is_admin() && function_exists( 'is_account_page' ) && is_account_page() ) {
			$title_sin_tags = wp_strip_all_tags( $title );
			if ( false !== stripos( $title_sin_tags, 'My account' ) || false !== stripos( $title_sin_tags, 'My Account' ) ) {
				return 'Mi Cuenta';
			}
		}
		return $title;
	}
endif;
add_filter( 'the_title', 'storex_child_fix_myaccount_title', 999999, 2 );
add_filter( 'wp_title_parts', function( $parts ) {
	if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_admin() ) {
		foreach ( $parts as $i => $p ) {
			if ( false !== stripos( $p, 'My account' ) ) {
				$parts[ $i ] = 'Mi Cuenta';
			}
		}
	}
	return $parts;
}, 999999, 1 );

/* ==========================================================================
   FIX: Breadcrumb StoreX (si no usa WooCommerce breadcrumb, usa el propio).
   Reemplaza "Home" por "Inicio" y "Product" por "Tienda" en el HTML final.
   ========================================================================== */
if ( ! function_exists( 'storex_child_fix_breadcrumb_storex_html' ) ) :
	function storex_child_fix_breadcrumb_storex_html( $html ) {

		// Protección contra reemplazos agresivos: sólo aplicamos a nodos breadcrumb
		if ( false === strpos( $html, 'breadcrumb' ) && false === strpos( $html, 'breadcrumbs' ) && false === strpos( $html, 'woocommerce-breadcrumb' ) ) {
			return $html;
		}

		$busca = array(
			'>Home<',
			'&rsaquo; Home',
			'Home &rsaquo;',
			'>Product<',
			'&rsaquo; Product',
			'Product &rsaquo;',
		);
		$reemplaza = array(
			'>Inicio<',
			'&rsaquo; Inicio',
			'Inicio &rsaquo;',
			'>Tienda<',
			'&rsaquo; Tienda',
			'Tienda &rsaquo;',
		);

		return str_replace( $busca, $reemplaza, $html );
	}
endif;
// Se engancha al mismo hook OB de shutdown para aplicarlo antes de imprimir.
add_filter( 'storex_child_final_html_output', 'storex_child_fix_breadcrumb_storex_html', 20, 1 );
