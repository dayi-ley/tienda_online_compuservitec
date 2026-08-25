<?php
// 60-metricas.php · Mostrar métricas rendimiento HTML/console (backup L2988-L3048)
if (!defined('ABSPATH')) exit;

/* ==========================================================================
   MÉTRICAS DE RENDIMIENTO · Mostradas a TODOS los visitantes si la constante
   SCX_MOSTRAR_METRICAS_RENDIMIENTO = true. Se pinta tanto en comentario HTML
   (Ctrl+U) como en console.log(F12→Console).
   ========================================================================== */
if ( defined( 'SCX_MOSTRAR_METRICAS_RENDIMIENTO' ) && SCX_MOSTRAR_METRICAS_RENDIMIENTO ) {

	if ( ! function_exists( 'storex_child_mostrar_metricas' ) ) {
		function storex_child_mostrar_metricas() {

			// Tiempo total desde que empezó PHP a cargar WP
			// (timestart se define en wp-settings.php como $timestart global)
			global $timestart;
			$t0 = ( is_float( $timestart ) ) ? $timestart : $_SERVER['REQUEST_TIME_FLOAT'];
			$segundos = ( microtime( true ) - (float)$t0 );

			$mem_peak = function_exists( 'memory_get_peak_usage' ) ? memory_get_peak_usage( true ) : memory_get_usage( true );
			$mem_peak_mb = round( $mem_peak / 1024 / 1024, 2 );

			global $wpdb;
			$num_queries = ( is_object( $wpdb ) && isset( $wpdb->num_queries ) ) ? (int) $wpdb->num_queries : 0;

			$woo_heavy = function_exists( 'storex_child_es_pagina_woo_pesada' ) && storex_child_es_pagina_woo_pesada();
			$es_home = is_front_page();

			$ob_abierto = (int) ob_get_level();
			$flags = array(
				'SALTAR_OB_WOO'       => ( defined( 'SCX_SALTAR_OB_EN_WOOCOMMERCE' ) && SCX_SALTAR_OB_EN_WOOCOMMERCE ? 'ON' : 'OFF' ),
				'DESACTIVAR_OB_FULL'  => ( defined( 'SCX_DESACTIVAR_OB_COMPLETAMENTE' ) && SCX_DESACTIVAR_OB_COMPLETAMENTE ? 'ON' : 'OFF' ),
				'OB_CACHE_SIMPLE'     => ( defined( 'SCX_OB_CACHE_SIMPLE' ) && SCX_OB_CACHE_SIMPLE ? 'ON' : 'OFF' ),
				'NO_THEME_MOD_FILTERS'=> ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ? 'ON' : 'OFF' ),
			);

			$flags_txt = '';
			foreach ( $flags as $k => $v ) {
				$flags_txt .= ( $flags_txt ? '  ' : '' ) . $k . '=' . $v;
			}

			// En HTML final (oculto visualmente pero visible en Ctrl+U / inspeccionar)
			echo "\n" . '<!-- SCX_METRICAS : '
				. 'TIME=' . sprintf( '%0.4fs', $segundos )
				. '  MEM_PEAK=' . $mem_peak_mb . 'MB'
				. '  QUERIES=' . $num_queries
				. '  OB_LEVEL=' . $ob_abierto
				. '  TIPO_PAGINA=' . ( $es_home ? 'HOME' : ( $woo_heavy ? 'WOO_PESADA' : 'NORMAL' ) )
				. '  ' . $flags_txt
				. ' -->' . "\n";

			// En consola navegador (F12 → Console)
			echo '<script>console.log("SCX_METRICAS: '
				. addslashes( sprintf( '%0.4fs | %s MB | %s queries | OB=%d | %s | %s',
					$segundos,
					$mem_peak_mb,
					$num_queries,
					$ob_abierto,
					( $es_home ? 'HOME' : ( $woo_heavy ? 'WOO_PESADA' : 'NORMAL' ) ),
					$flags_txt
				) )
				. '");</script>' . "\n";
		}
	}
	add_action( 'wp_footer', 'storex_child_mostrar_metricas', 999999 );
}
