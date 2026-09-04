<?php
/**
 * Theme functions.php — StoreX Child (GRUPO COMPUSERVITEC)
 * ==========================================================
 *  · 11 de agosto de 2026  →  Organizado por módulos (antes 3048 líneas monolíticas).
 *
 *  ¿DÓNDE ESTÁ EL CÓDIGO AHORA?
 *  ============================
 *  inc/
 *  ├── 00-config.php          Flags (SCX_*) + Kill Switch admin Temporal
 *  ├── 10-rendimiento.php     Optimizaciones: caché transitorios, lazy img, desactivar emojis, textdomains
 *  ├── 20-traducciones.php    gettext + ngettext (Login/Carrito/Checkout) + Aumentar productos novedades (12)
 *  ├── 30-woocommerce.php     WooCommerce: breadcrumb, COD gateway, fixes My Account
 *  ├── 40-html-final.php      Output Buffer shutdown → 95 str_replace + REGEX traducciones HTML final
 *  ├── 50-tema.php            StoreX child: Burger integration, theme_mods slider/footer/header, enqueue,
 *  │                           footer cols, shortcodes, reemplazos globales OB early, shutdown_final
 *  └── 60-metricas.php        SCX_MOSTRAR_METRICAS_RENDIMIENTO → comentario HTML + console.log
 *
 *  CÓMO VOLVER AL ESTADO ANTERIOR (100% reversible en 2 pasos):
 *  ============================================================
 *  1. Borra este archivo functions.php.
 *  2. Renombra functions-BACKUP-2026-08-11.php a functions.php.
 *  Listo, vuelve a ser el archivo monolítico de 3048 líneas sin tocar.
 *
 *  CÓMO DESACTIVAR UN MÓDULO PARA PROBAR (sin editarlo):
 *  ====================================================
 *  Renombra el archivo agregando .OFF al final.
 *  Ej:  20-traducciones.php  →  20-traducciones.php.OFF   (deja de cargarse)
 *  Para volver a activarlo: borra .OFF.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Directorio base del child theme (evitamos repetir __DIR__ en cada include)
$scx_child_dir = rtrim( dirname( __FILE__ ), '/\\' ) . DIRECTORY_SEPARATOR;

// ======================== CARGA DE MÓDULOS (orden 00 → 60) ========================
$scx_modulos = array(
	'inc' . DIRECTORY_SEPARATOR . '00-config.php',
	'inc' . DIRECTORY_SEPARATOR . '10-rendimiento.php',
	'inc' . DIRECTORY_SEPARATOR . '20-traducciones.php',
	'inc' . DIRECTORY_SEPARATOR . '30-woocommerce.php',
	'inc' . DIRECTORY_SEPARATOR . '40-html-final.php',
	'inc' . DIRECTORY_SEPARATOR . '50-tema.php',
	'inc' . DIRECTORY_SEPARATOR . '56-carrito-titulo.php',
	'inc' . DIRECTORY_SEPARATOR . '60-metricas.php',
);
foreach ( $scx_modulos as $scx_mod ) {
	$_scx_ruta = $scx_child_dir . ltrim( str_replace( '/', DIRECTORY_SEPARATOR, $scx_mod ), DIRECTORY_SEPARATOR );
	if ( file_exists( $_scx_ruta ) ) {
		require_once $_scx_ruta;
	}
}
unset( $scx_child_dir, $scx_modulos, $_scx_ruta, $scx_mod );

/* =============== FIN =============== */
