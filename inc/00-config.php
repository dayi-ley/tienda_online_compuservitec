<?php
/**
 * 00-config.php · Flags generales + Kill Switch admin
 * ----------------------------------------------------
 *  · RANGO del functions.php original: Líneas 1 al 69
 *  · Si quieres desactivar CUALQUIER COSA de este tema SOLO para pruebas:
 *    abre este archivo y cambia los defines (ON / OFF) — no toques nada más.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ==========================================================================
   🔴🔴🔴 KILL SWITCH TEMPORAL (BORRAR ESTE BLOQUE CUANDO EL ADMIN ABRA NORMAL) 🔴🔴🔴
   Desactiva COMPLETAMENTE los filtros de traducción SOLO EN EL PANEL ADMIN para
   evitar el error "initializeEditaror is not a function" (editor de plugins/Loco).
   Los textos carrito/checkout frontend siguen traducidos.
   PARA DESACTIVAR ESTE KILL SWITCH: borra las 3 líneas define de abajo.
   ========================================================================== */
if ( function_exists( 'is_admin' ) && is_admin() ) {
    @define( 'SCX_DESACTIVA_TRADUCCIONES', true );
    @define( 'SCX_DESACTIVA_OB_OUTPUT_BUFFER', true );
    // Desregla completamente gettext y ngettext solo en admin (si están cargados).
    // Las funciones storex_child_traducciones_... ya no se ejecutan.
    global $wp_filter;
    if ( is_array( $wp_filter ) ) {
        foreach ( array( 'gettext', 'ngettext' ) as $hk ) {
            if ( isset( $wp_filter[ $hk ] ) && is_object( $wp_filter[ $hk ] ) && is_callable( array( $wp_filter[ $hk ], 'remove_all_filters' ) ) ) {
                $wp_filter[ $hk ]->remove_all_filters( 999999 );
            }
        }
    }
}

/* ==========================================================================
   ============= MODO PRUEBA · FLAGS para optimizaciones reversibles =========
   Instrucciones:
     - Para ACTIVAR una prueba:    define( 'SCX_XXXX', true  );
     - Para DESACTIVAR (revertir): define( 'SCX_XXXX', false );
     - Des/comentar no es necesario: cambia true ↔ false, guarda, Ctrl+F5.
     - Luego mira los números de rendimiento que aparecen (solo para admins)
       al final del HTML (abajo del todo) o en F12 → Consola (console.log).
   ========================================================================== */

// PRUEBA 0: Mostrar diagnóstico mini al final del HTML (tiempo / memoria / queries)
// SÓLO para usuarios logueados que pueden gestionar opciones. No afecta visitantes.
define( 'SCX_MOSTRAR_METRICAS_RENDIMIENTO', true );

// PRUEBA 1 (más probable que aporte mejora):
//   SALTARSE el Output Buffering en páginas WooCommerce pesadas
//   (shop / categoría / producto / tag / carrito / checkout / mi cuenta)
//   En estas páginas NO inyectamos el footer custom vía OB (debería caer el
//   fallback de WP_Widget normal, o si no se ve nada lo restauramos).
//   Valor   true  = NO usar OB en WooCommerce (ahorra memoria y tiempo)
//   Valor   false = mantener OB en TODAS las páginas (comportamiento actual)
define( 'SCX_SALTAR_OB_EN_WOOCOMMERCE', true );

// PRUEBA 2 (más agresiva):
//   QUITAR el OB COMPLETAMENTE en TODO el sitio.
//   Solo activar para comprobar cuanto consume el OB en particular.
//   Si con esto la categoría sigue lenta → la culpa NO es nuestro functions.php.
//   Nota: desactiva PRUEBA 1 primero si usas esta (esta cubre más).
define( 'SCX_DESACTIVAR_OB_COMPLETAMENTE', false );

// PRUEBA 3 (sólo afecta a visitantes no-logueados, muy útil en producción):
//   Activar un caché de OB muy simple: si la página ya fue generada y es
//   exactamente la misma, se sirve del cache archivo en /wp-content/cache/scx-ob/.
//   En Local by Flywheel el filesystem es rápido → lo notarás poco, pero en
//   hosting con disco lento sí.
define( 'SCX_OB_CACHE_SIMPLE', false );

// PRUEBA 4 (extra):
//   Desactivar todos los filtros de lectura de theme_mods (slider/info etc),
//   para ver si la lentitud viene de muchos get_theme_mod(). Normalmente NO.
define( 'SCX_DESACTIVAR_FILTROS_THEME_MODS', false );

/* ============= FIN DE FLAGS DE PRUEBA (empieza el resto del tema) ============ */
