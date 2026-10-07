<?php
/**
 * 99-woo-cache.php
 * ============================================================
 * OPTIMIZACIÓN VELOCIDAD CARRITO WOO BLOCKS (Backend / PHP / MySQL)
 * ============================================================
 * ⚠️ REGLAS DE ENCAPSULACIÓN (FIX 2026-10-06):
 * TODOS los filtros SÓLO se ejecutan cuando is_cart() === TRUE.
 * NUNCA afectan /shop/, /product/, /checkout/, páginas normales, etc.
 * (Anteriormente los filtros corrían en TODAS las páginas y mataban
 *  el loop principal de la Tienda /shop/ → <ul class="products"> VACÍO).
 *
 * Diagnóstico 100% confirmado por Network Doc Timing (carrito):
 *   - TTFB (Waiting for server response): 1.26 s  🔴 MUY ALTO
 *   - Content Download:                    0.92 s  🔴 ALTO
 *   - HTML principal /cart/:               2.19 s  🔴 TOTAL
 *   - 2do pedido duplicado /cart/ fetch:   2.69 s  🔴 (hidratación React)
 *   - Métrica interna del tema: 141 queries SQL por página.
 *
 * OBJETIVO DE ESTE MÓDULO:
 *   Reducir las 141 queries a ~60 queries (por caché) y el TTFB a < 600ms,
 *   SIN ROMPER NADA, SIN DESHABILITAR NINGUNA FUNCIONALIDAD.
 *
 * SEGURIDAD:
 *   - 0 inputs del usuario (no $_POST / $_GET / $_REQUEST).
 *   - 0 writes directos a SQL. Solo utiliza Transients API de WordPress.
 *   - No es accesible por URL (ABSPATH check).
 * ============================================================ */
if (!defined('ABSPATH')) exit;

/* ================================================================
   WRAPPER GLOBAL: Sólo inicializar en la página del CARRITO.
   (template_redirect hook: en este punto ya sabemos el query var)
   ================================================================ */
add_action('template_redirect', function () {

    // SI NO ES CARRITO: NO HACEMOS NADA. SALIR INMEDIATAMENTE.
    if (!function_exists('is_cart') || !is_cart()) return;

    /* =========================================================
       1. GROUP CACHE: Activar cache de grupo persistente para
            productos, términos y objetos Woo. Esto reduce
            get_post_meta() repetidas 20 veces por producto.
       ========================================================= */
    if (function_exists('wp_cache_add_global_groups')) {
        wp_cache_add_global_groups(array('products', 'product_meta', 'wc_product', 'woocommerce_cache', 'terms', 'term_meta'));
    }

    /* =========================================================
       2. WP_Query: Forzar cacheo de resultados + NO ejecutar
            COUNT(*) calculo total de posts (no_found_rows=true).
            Aplica a: New in store empty cart, product-new, shortcodes
            DENTRO del carrito.
       ========================================================= */
    add_filter('posts_pre_query', function ($posts, $query) {
        // Sólo en el carrito, sólo en queries WooCommerce, NO la main query global
        if (is_admin() || !is_object($query) || !empty($query->is_main_query) || !is_cart()) {
            return $posts;
        }
        if (
            is_array($query->query_vars) &&
            isset($query->query_vars['post_type']) &&
            in_array($query->query_vars['post_type'], array('product', 'product_variation'), true) &&
            (!isset($query->query_vars['no_found_rows']) || true !== $query->query_vars['no_found_rows'])
        ) {
            $query->query_vars['cache_results']          = true;
            $query->query_vars['update_post_meta_cache'] = true;
            $query->query_vars['update_post_term_cache'] = true;
            $query->query_vars['no_found_rows']          = true;
        }
        return $posts;
    }, 10, 2);

    /* =========================================================
       3. WP_Query para BLOQUE New in store del carrito vacío
            + Woo Blocks product-new → no_found_rows + cache
       ========================================================= */
    add_filter('woocommerce_shortcode_products_query', function ($query_args, $atts, $loop_name) {
        if (!is_array($query_args)) return $query_args;
        $query_args['cache_results']          = true;
        $query_args['update_post_meta_cache'] = true;
        $query_args['update_post_term_cache'] = true;
        $query_args['no_found_rows']          = true;
        return $query_args;
    }, 9999, 3);

    /* =========================================================
       4. get_terms() del menu header / buscador: cache persistente
            (suelen ser 20-30 queries repetidas por page load)
            APLICA SOLO EN CARRITO.
       ========================================================= */
    add_filter('get_terms_args', function ($args) {
        if (!is_admin() && is_array($args) && is_cart()) {
            $args['cache_results']          = true;
            $args['update_term_meta_cache'] = true;
        }
        return $args;
    }, 99999, 1);

    /* =========================================================
       5. Desactivar options table queries innecesarias en /cart/:
            - stats/comments/rss/per_page (no usados en carrito)
            - count comments/pending (no necesitamos)
       ========================================================= */
    add_filter('pre_option_default_comment_status', function () { return 'closed'; }, 99999);
    add_filter('pre_option_default_ping_status',    function () { return 'closed'; }, 99999);
    add_filter('pre_option_posts_per_rss',          function () { return 10;       }, 99999);
    add_filter('pre_option_posts_per_page',         function () { return (int)get_option('posts_per_page', 10); }, 99999);

    add_filter('wp_count_comments', function () {
        return (object) array(
            'approved'       => 0, 'moderated' => 0, 'spam' => 0,
            'trash'          => 0, 'post-trashed' => 0, 'total_comments' => 0,
        );
    }, 99999);

    /* =========================================================
       6. Cache de MENU HEADER: Priming cache de object_ids del menu
          (solo en el carrito, no rompe shop/home).
       ========================================================= */
    add_filter('wp_nav_menu_objects', function ($items) {
        if (!is_cart() || !is_array($items)) return $items;
        $ids = array();
        foreach ($items as $itm) {
            if (!empty($itm->object_id)) $ids[] = (int)$itm->object_id;
        }
        if (count($ids) > 0 && function_exists('_prime_post_caches')) {
            _prime_post_caches($ids, false, false);
        }
        return $items;
    }, 1, 1);

    // Flag global para hooks internos (no hace nada)
    $GLOBALS['scx_cache_woo_on'] = true;

}, 5); // prioridad 5 (antes de que Woo init principal del carrito)
