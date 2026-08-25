<?php
// 10-rendimiento.php · Optimizaciones rendimiento + textdomains (backup L72-L350)
if (!defined('ABSPATH')) exit;

/* ==========================================================================
   OPTIMIZACIÓN DE RENDIMIENTO · BLOQUE 1 · Caché transitorios WordPress Nativa
   (NO usa plugins, usa set_transient/get_transient API propia de WP).
   --------------------------------------------------------------------------
   Lighthouse te marcó:
     • "Document request latency: Est savings 5,100 ms"  ← EL CUELLO PRINCIPAL
     • "Minimize main-thread work: 5.7 s"                ← #2
     • "Speed Index 12.2 s"                               ← por queries MySQL.

   Solución: cachear TODAS las queries pesadas del home durante 12 minutos.
   Cache que se limpia SOLO si: caduca el tiempo (12min) o el usuario
   guarda cambios en el Customizer o actualiza productos WooCommerce.
   ========================================================================== */

if ( ! function_exists( 'storex_child_cache_version_key' ) ) :
	// Una clave común para invalidar TODOS los caches de golpe.
	function storex_child_cache_version_key() {
		$v = get_transient( 'scx_cache_version_v1' );
		if ( empty( $v ) ) {
			$v = (string) time();
			set_transient( 'scx_cache_version_v1', $v, 12 * HOUR_IN_SECONDS );
		}
		return 'scx_' . $v . '_';
	}
endif;

/**
 * Helper: devuelve $value SI existe en el transient,
 * si no existe, llama $fn() para calcularlo, guarda y lo devuelve.
 */
if ( ! function_exists( 'storex_child_with_cache' ) ) :
	function storex_child_with_cache( $cache_key_suffix, $fn, $ttl = 720 ) {
		$full_key = storex_child_cache_version_key() . $cache_key_suffix;
		$cached   = get_transient( $full_key );
		if ( false !== $cached ) {
			return $cached;
		}
		$value = $fn();
		set_transient( $full_key, $value, $ttl );
		return $value;
	}
endif;

/**
 * Invalida TODOS los caches (se llama cuando guardas customizer o editar WC).
 */
if ( ! function_exists( 'storex_child_bust_cache' ) ) :
	function storex_child_bust_cache() {
		delete_transient( 'scx_cache_version_v1' );
	}
endif;

// Invalidate cuando guardas customizer (cambias banners, secciones, etc):
add_action( 'customize_save_after', 'storex_child_bust_cache', 1 );
// Invalidate cuando se editan/crean productos, categorías producto o stock:
add_action( 'save_post_product',      'storex_child_bust_cache', 1 );
add_action( 'edited_product_cat',     'storex_child_bust_cache', 1 );
add_action( 'delete_product_cat',     'storex_child_bust_cache', 1 );
add_action( 'create_product_cat',     'storex_child_bust_cache', 1 );
add_action( 'woocommerce_product_set_stock_status', 'storex_child_bust_cache', 1 );
add_action( 'woocommerce_updated_product_stock',    'storex_child_bust_cache', 1 );

/* ==========================================================================
   OPTIMIZACIÓN RENDIMIENTO · BLOQUE 2 · Fetchpriority + Lazy loading imágenes
   - Banner hero = LCP → <img fetchpriority="high">  (Lighthouse LCP 9.1s)
   - Todas las demás imágenes <img> sin loading/lazy → loading="lazy" nativo.
   ========================================================================== */

if ( ! function_exists( 'storex_child_img_attrs_tune' ) ) :
	/**
	 * Busca <img> en todo el HTML final y ajusta:
	 *  - Si el src contiene banner-compuservitec-bg → fetchpriority="high" (banner LCP)
	 *  - Todos los demás <img> que NO tengan loading=... → loading="lazy"
	 */
	function storex_child_img_attrs_tune( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		// 1) Encontrar todas las etiquetas <img ...>
		if ( preg_match_all( '/<img\s+([^>]+?)\/?>/is', $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$full    = $m[0];
				$attrs   = $m[1];
				$changed = false;

				// Analizar src
				$src = '';
				if ( preg_match( '/src\s*=\s*["\']([^"\']+)["\']/i', $attrs, $sm ) ) {
					$src = $sm[1];
				}

				// Chequear si existe loading / fetchpriority / decoding / width / height
				$has_load   = (bool) preg_match( '/\sloading\s*=/i', $attrs );
				$has_fetch  = (bool) preg_match( '/\sfetchpriority\s*=/i', $attrs );
				$has_decode = (bool) preg_match( '/\sdecoding\s*=/i', $attrs );
				$has_w      = (bool) preg_match( '/\swidth\s*=/i', $attrs );
				$has_h      = (bool) preg_match( '/\sheight\s*=/i', $attrs );

				// Extra: banner hero (LCP) → SÍ fetchpriority high y QUITAR loading lazy
				$es_banner_lcp =
					( $src !== '' ) && (
						strpos( $src, 'banner-compuservitec-bg' ) !== false ||
						strpos( $src, 'banner-img-1' ) !== false ||
						strpos( $src, 'slider/banner' ) !== false
					);

				$new_attrs = $attrs;

				if ( $es_banner_lcp ) {
					// Banner LCP: alta prioridad y NO lazy.
					if ( $has_load ) {
						$new_attrs = preg_replace( '/\sloading\s*=\s*["\'][^"\']*["\']/i', '', $new_attrs );
						$has_load = false;
						$changed = true;
					}
					if ( ! $has_fetch ) {
						$new_attrs .= ' fetchpriority="high"';
						$has_fetch = true;
						$changed = true;
					}
					// El banner siempre decoding async es seguro
					if ( ! $has_decode ) {
						$new_attrs .= ' decoding="async"';
						$changed = true;
					}
				} else {
					// Todo el resto de imágenes: lazy loading nativo.
					if ( ! $has_load ) {
						$new_attrs .= ' loading="lazy"';
						$has_load = true;
						$changed = true;
					}
					if ( ! $has_decode ) {
						$new_attrs .= ' decoding="async"';
						$changed = true;
					}
					// Nunca fetchpriority en otras imágenes
				}

				// Evita warnings en lighthouse "img sin width/height explicitos"
				// Solo lo hacemos en imágenes que NO tengan las dos dimensiones, y
				// que sean de la librería multimedia (o assets nuestro child).
				if ( $changed && $src !== '' && ( ! $has_w || ! $has_h ) ) {
					// Intentar adivinar path local para getimagesize (solo si es local).
					$local = false;
					$home  = home_url( '/' );
					if ( strpos( $src, $home ) === 0 ) {
						$rel   = substr( $src, strlen( $home ) );
						$local = ABSPATH . ltrim( $rel, '/' );
					} elseif ( strpos( $src, content_url() ) === 0 ) {
						$rel   = substr( $src, strlen( content_url() ) );
						$local = WP_CONTENT_DIR . ltrim( $rel, '/' );
					} elseif ( strpos( $src, '/' ) === 0 ) {
						$local = ABSPATH . ltrim( $src, '/' );
					}
					if ( $local && @file_exists( $local ) ) {
						$size = @getimagesize( $local );
						if ( is_array( $size ) && ! empty( $size[0] ) && ! empty( $size[1] ) ) {
							if ( ! $has_w ) { $new_attrs .= ' width="' . (int) $size[0] . '"'; $changed = true; }
							if ( ! $has_h ) { $new_attrs .= ' height="' . (int) $size[1] . '"'; $changed = true; }
						}
					}
				}

				if ( $changed ) {
					// Reconstruir etiqueta img
					$new_full = rtrim( '<img ' . $new_attrs, ' /' );
					// Mantener self-closing si lo estaba
					if ( substr( rtrim( $full ), -2 ) === '/>' ) {
						$new_full .= ' />';
					} else {
						$new_full .= '>';
					}
					$html = str_replace( $full, $new_full, $html );
				}
			}
		}
		return $html;
	}
endif;

/* ==========================================================================
   OPTIMIZACIÓN RENDIMIENTO · BLOQUE 3 · Cachear HTML de secciones costosas
   (Categorías populares, Productos destacados, Info Section, Top Categories).
   Evitamos que WooCommerce haga 30 queries para pintar 8 productos cada F5.
   ========================================================================== */

if ( ! function_exists( 'storex_child_cache_section_output' ) ) :
	/**
	 * Dado un nombre de sección y un callable, cachea el HTML (output buffer).
	 */
	function storex_child_cache_section_output( $name, $fn, $ttl = 900 ) {
		// En el customizer preview o en el editor NO cacheamos
		if ( function_exists( 'storex_child_is_customizer_context' ) && storex_child_is_customizer_context() ) {
			call_user_func( $fn );
			return;
		}
		// Si el usuario actual puede editar temas → no cachear para ver cambios realtime
		if ( current_user_can( 'edit_theme_options' ) ) {
			call_user_func( $fn );
			return;
		}
		$key = 'section_' . sanitize_key( $name ) . '_'
			. md5( ( is_ssl() ? 'https' : 'http' ) . '_' . get_locale() );
		$cached = storex_child_with_cache( $key, function() use ( $fn ) {
			ob_start();
			call_user_func( $fn );
			return ob_get_clean();
		}, $ttl );
		echo $cached;
	}
endif;

/* ==========================================================================
   OPTIMIZACIÓN RENDIMIENTO · BLOQUE 4 · Aplicar snippets en el HTML final
   (se ejecuta dentro de storex_child_aplicar_reemplazos_globales, por lo que
   ya forma parte del OB y no requiere hook extra).
   ========================================================================== */

if ( ! function_exists( 'storex_child_aplicar_optimizaciones_rendimiento_html' ) ) :
	function storex_child_aplicar_optimizaciones_rendimiento_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		// A) Fetchpriority / lazy / decoding / width+height
		$html = storex_child_img_attrs_tune( $html );

		// B) Quitar el emoji WP que añade 4 scripts / styles innecesarios en front.
		//    (LightHouse lo marca como JS no usado).
		if ( function_exists( 'print_emoji_styles' ) ) {
			remove_action( 'wp_head', 'print_emoji_styles' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
		}
		if ( function_exists( 'print_emoji_detection_script' ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'wp_print_scripts', 'print_emoji_detection_script', 7 );
		}

		return $html;
	}
endif;

// Activar la desactivación de emojis de forma global (no solo HTML via OB).
if ( ! function_exists( 'storex_child_disable_wp_emojis' ) ) :
	function storex_child_disable_wp_emojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}
endif;
add_action( 'init', 'storex_child_disable_wp_emojis', 9999 );

/* ==========================================================================
   FORZAR CARGA DE TEXT-DOMAIN StoreX (padre) — para Loco Translate

   Burger Companion NO usa __() correctamente, así que quitamos su carga
   (no sirve de nada). Solo mantenemos StoreX que sí tiene 110 strings
   traducibles via Loco.
   ========================================================================== */
if ( ! function_exists( 'storex_child_forzar_carga_textdomains' ) ) :
	function storex_child_forzar_carga_textdomains() {

		// 1) TEMA PADRE StoreX (textdomain 'storex')
		//    - Ruta 1: /themes/storex/languages/ (si padre trae traducciones)
		//    - Ruta 2: /languages/themes/ + /languages/loco/themes/ (donde Loco guarda)
		load_theme_textdomain( 'storex', get_template_directory() . '/languages' );
		load_theme_textdomain( 'storex', WP_LANG_DIR . '/themes' );
		load_theme_textdomain( 'storex', WP_LANG_DIR . '/loco/themes' );

		// 2) TEMA HIJO StoreX Child (textdomain 'storex-child')
		//    Si después usas __() en el child, aquí se cargan sus traducciones.
		load_child_theme_textdomain( 'storex-child', get_stylesheet_directory() . '/languages' );
	}
endif;
add_action( 'after_setup_theme', 'storex_child_forzar_carga_textdomains', 5 );
