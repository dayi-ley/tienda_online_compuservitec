<?php
// 50-tema.php · Tema storex-child: theme_mods, slider, footer, OB early (backup L1378-L2987)
if (!defined('ABSPATH')) exit;

/* ==========================================================================
   FIX: Burger Companion no carga las secciones para temas hijo de StoreX
   El plugin requiere $theme->name === 'StoreX' literal para cargar
   inc/storex/storex.php. Con tema hijo (StoreX - Grupo Compuservitec)
   no entra en la condición, así que lo cargamos manualmente.

   Importante: PRIORIDAD 11, después del init del plugin (priority 10).
   El plugin carga inc/custom-controls/customizer-repeater/functions.php
   (que define Burger_Companion_Repeater) dentro de su hook init. Si
   cargamos storex.php antes, el Customizer lanza Fatal error.
   ========================================================================== */
if ( ! function_exists( 'storex_child_load_burger_storex_integration' ) ) :
	function storex_child_load_burger_storex_integration() {
		if ( defined( 'BURGER_COMPANION_PLUGIN_DIR' ) ) {
			$storex_inc = BURGER_COMPANION_PLUGIN_DIR . 'inc/storex/storex.php';
			if ( file_exists( $storex_inc ) && ! class_exists( 'Burger_Companion_Repeater', false ) ) {
				require_once BURGER_COMPANION_PLUGIN_DIR . 'inc/custom-controls/customizer-repeater/functions.php';
				require_once BURGER_COMPANION_PLUGIN_DIR . 'inc/custom-controls/range-validator/range-control.php';
				if ( class_exists( 'WP_Customize_Control' ) ) {
					require_once BURGER_COMPANION_PLUGIN_DIR . 'inc/custom-controls/product-cat/product-cat-control.php';
				}
			}
			if ( file_exists( $storex_inc ) ) {
				require_once $storex_inc;
			}
		}
	}
endif;
add_action( 'init', 'storex_child_load_burger_storex_integration', 11 );

if ( ! function_exists( 'storex_child_debug_sections' ) ) :
	function storex_child_debug_sections() {
		if ( ! isset( $_GET['debug_sec'] ) || $_GET['debug_sec'] !== '1' ) {
			return;
		}
		global $wp_filter;
		echo '<pre style="background:#fff;color:#222;padding:20px;font-size:14px;text-align:left;border:2px solid red;z-index:99999;position:relative;">';
		echo "<b>=== DEBUG storex_sections ===</b>\n";
		echo 'is_front_page: ' . ( is_front_page() ? 'YES' : 'NO' ) . "\n";
		echo 'is_page_template(frontpage): ' . ( is_page_template( 'templates/template-frontpage.php' ) ? 'YES' : 'NO' ) . "\n";
		echo 'queried_id: ' . get_queried_object_id() . "\n";
		echo 'template_slug: ' . ( get_page_template_slug( get_queried_object_id() ) ?: '(none)' ) . "\n";
		echo "\n<b>HOOKS attached to storex_sections:</b>\n";
		if ( isset( $wp_filter['storex_sections'] ) ) {
			foreach ( $wp_filter['storex_sections'] as $priority => $callbacks ) {
				echo "\nPriority: $priority\n";
				foreach ( $callbacks as $idx => $cb ) {
					if ( is_array( $cb['function'] ) ) {
						$name = ( is_object( $cb['function'][0] ) ? get_class( $cb['function'][0] ) : $cb['function'][0] ) . '::' . $cb['function'][1];
					} elseif ( is_string( $cb['function'] ) ) {
						$name = $cb['function'];
					} else {
						$name = '(closure)';
					}
					echo "  - $name\n";
				}
			}
		} else {
			echo "NO HOOKS attached\n";
		}
		echo "\n<b>theme_mods</b>\n";
		echo 'hs_top_categories: [' . get_theme_mod( 'hs_top_categories', '(DEFAULT)' ) . "]\n";
		echo 'top_categories_id: ' . var_export( get_theme_mod( 'top_categories_id' ), true ) . "\n";
		echo 'hs_popular_categories: [' . get_theme_mod( 'hs_popular_categories', '(DEFAULT)' ) . "]\n";
		echo 'product_cat02_id: ' . var_export( get_theme_mod( 'product_cat02_id' ), true ) . "\n";
		echo 'hs_product_section: [' . get_theme_mod( 'hs_product_section', '(DEFAULT)' ) . "]\n";
		echo 'product_cat_id: ' . var_export( get_theme_mod( 'product_cat_id' ), true ) . "\n";
		echo 'hs_info_section: [' . get_theme_mod( 'hs_info_section', '(DEFAULT)' ) . "]\n";
		$slider = get_theme_mod( 'slider' );
		echo 'slider: ' . ( is_string( $slider ) ? substr( $slider, 0, 250 ) : var_export( $slider, true ) ) . "\n";
		echo '</pre>';
	}
endif;
add_action( 'wp_footer', 'storex_child_debug_sections', 1 );

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

if ( !function_exists( 'storex_child_enqueue_styles' ) ):
    function storex_child_enqueue_styles() {

        $parent_deps = array(
            'bootstrap-min',
            'owl-carousel-min',
            'font-awesome',
            'animate',
            'storex-elpath',
            'storex-main',
            'nice-select',
            'storex-media-query'
        );

        wp_enqueue_style(
            'storex-parent-style',
            trailingslashit( get_template_directory_uri() ) . 'style.css',
            $parent_deps,
            wp_get_theme( get_template() )->get( 'Version' )
        );

        global $wp_styles;
        if ( isset( $wp_styles->registered['storex-style'] ) ) {
            $wp_styles->registered['storex-style']->deps = array_unique(
                array_merge(
                    $wp_styles->registered['storex-style']->deps,
                    array( 'storex-parent-style' )
                )
            );
        }
    }
endif;
add_action( 'wp_enqueue_scripts', 'storex_child_enqueue_styles', 20 );

/* ==========================================================================
   FIXES: Burger Companion — Frontpage sections para tema hijo StoreX
   ========================================================================== */

/* ==========================================================================
   LIMPIEZA DE 1 SOLA VEZ + FILTRO DE LECTURA DE THEME_MODS:
   Interceptamos la opción BD `theme_mods_storex-child` al leerla y, si
   detectamos que slider/info_sec son los demo antiguos (5+ slides o
   contienen "Luxury Fashion", "Men Regular Fit"), los borramos del array.
   Así get_theme_mod('slider') devuelve '' → usa el default del plugin
   (6 slides Luxury Fashion) → nuestro filtro theme_mod_slider detecta
   Luxury Fashion y reemplaza por nuestro 1 slide en español.
   El usuario SIEMPRE puede guardar sus propios valores desde el Customizer.
   ========================================================================== */
if ( ! function_exists( 'storex_child_one_time_cleanup' ) ) :
	function storex_child_one_time_cleanup() {
		$force_reset = isset( $_GET['reset_storex_child'] ) && $_GET['reset_storex_child'] === '1';
		if ( ! $force_reset && get_transient( 'storex_child_cleanup_done' ) ) {
			return;
		}
		remove_theme_mod( 'slider' );
		remove_theme_mod( 'info_sec' );
		delete_transient( 'storex_child_cleanup_done' );
		delete_transient( 'storex_child_user_saved_personalization' );
		set_transient( 'storex_child_cleanup_done', '1', 10 * YEAR_IN_SECONDS );
	}
endif;
add_action( 'init', 'storex_child_one_time_cleanup', 12 );

if ( ! function_exists( 'storex_child_filter_theme_mods_on_read' ) ) :
	function storex_child_fingerprints_demo_slider( $raw_string ) {
		if ( ! is_string( $raw_string ) || '' === $raw_string ) {
			return false;
		}
		return (
			strpos( $raw_string, 'Luxury Fashion' ) !== false ||
			strpos( $raw_string, 'Classic Styles' ) !== false ||
			strpos( $raw_string, 'Smart Style Collection' ) !== false ||
			strpos( $raw_string, 'Starting From' ) !== false ||
			strpos( $raw_string, 'New Release' ) !== false
		);
	}
	function storex_child_fingerprints_demo_info( $raw_string ) {
		if ( ! is_string( $raw_string ) || '' === $raw_string ) {
			return false;
		}
		return (
			strpos( $raw_string, 'Men Regular Fit' ) !== false ||
			strpos( $raw_string, 'Premium Stylish Shoes' ) !== false ||
			strpos( $raw_string, 'Advanced Health Tracking' ) !== false ||
			strpos( $raw_string, 'Best Seller' ) !== false ||
			strpos( $raw_string, 'Flash Sale' ) !== false ||
			strpos( $raw_string, 'Limited Offer' ) !== false
		);
	}
	function storex_child_filter_theme_mods_on_read( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( isset( $value['slider'] ) ) {
			$raw_slider = is_string( $value['slider'] ) ? $value['slider'] : '';
			$count = 0;
			if ( is_string( $raw_slider ) && '' !== $raw_slider ) {
				$dec = json_decode( $raw_slider, true );
				if ( is_array( $dec ) ) {
					$count = count( $dec );
				}
			}
			if ( storex_child_fingerprints_demo_slider( $raw_slider ) || $count >= 4 ) {
				unset( $value['slider'] );
			}
		}
		if ( isset( $value['info_sec'] ) ) {
			$raw_info = is_string( $value['info_sec'] ) ? $value['info_sec'] : '';
			if ( storex_child_fingerprints_demo_info( $raw_info ) ) {
				unset( $value['info_sec'] );
			}
		}
		return $value;
}
endif;

/* Flag PRUEBA 4: si está activado, NUNCA metemos los filtros de theme_mods
   (para medir cuánto consumen). Todo se lee directamente de DB sin override. */
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :

	add_filter( 'option_theme_mods_storex-child', 'storex_child_filter_theme_mods_on_read', 999 );
	add_filter( 'theme_mods_storex-child', 'storex_child_filter_theme_mods_on_read', 999 );

/* ==========================================================================
   Filtro FINAL al slider/info_sec en FRONTEND NO-CUSTOMIZER:
   Si hay >= 3 slides y no estamos guardando ajustes ni dentro del panel
   del Customizer, directamente usamos nuestro default. Esto evita que
   la caché de WordPress (Object Cache) se interponga y muestre slides
   repetidos de la demo aunque ya hayamos limpiado la BD.
   Si el usuario entra al Customizer, pulsa Guardar con slides NUEVOS,
   el count será < 3 o los contendrán títulos únicos, y este check salta.
   ========================================================================== */
if ( ! function_exists( 'storex_child_force_frontend_defaults' ) ) :
	function storex_child_is_customizer_context() {
		// NO usar is_admin() a secas: retorna true para cualquier usuario autenticado
		// con toolbar visible, incluso en el HOME del frontend. Bloquearía el forzamiento.
		if ( isset( $_REQUEST['customize_messenger_channel'] ) || isset( $_REQUEST['customize_changeset_uuid'] ) || isset( $_REQUEST['wp_customize'] ) ) {
			return true;
		}
		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return true;
		}
		// Aceptar is_admin() SÓLO si es el panel /wp-admin/ real, no el frontend logueado.
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
			if ( strpos( $uri, '/wp-admin/' ) !== false ) {
				return true;
			}
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		if ( strpos( $uri, '/customize.php' ) !== false || strpos( $uri, 'customize_save' ) !== false ) {
			return true;
		}
		return false;
	}
	function storex_child_force_frontend_defaults() {
		if ( storex_child_is_customizer_context() ) {
			return;
		}
		$slider = get_theme_mod( 'slider' );
		$need_override = false;
		if ( is_string( $slider ) && '' !== $slider ) {
			$dec = json_decode( $slider, true );
			if ( is_array( $dec ) ) {
				$count = count( $dec );
				if ( $count >= 3 ) {
					$need_override = true;
				}
				if ( ! $need_override && $count >= 2 ) {
					$first_hash = '';
					$all_same = true;
					for ( $i = 0; $i < $count; $i++ ) {
						$t = isset( $dec[ $i ]['title'] ) ? (string) $dec[ $i ]['title'] : '';
						$s = isset( $dec[ $i ]['subtitle'] ) ? (string) $dec[ $i ]['subtitle'] : '';
						$h = md5( trim( wp_strip_all_tags( $t . '|' . $s ) ) );
						if ( '' === $first_hash ) {
							$first_hash = $h;
						} elseif ( $h !== $first_hash ) {
							$all_same = false;
							break;
						}
					}
					if ( $all_same ) {
						$need_override = true;
					}
				}
			}
		}
		if ( $need_override ) {
		global $storex_child_forced_slider;
		$storex_child_forced_slider = storex_child_slider_json_default();
		// PRUEBA 4 OFF = si el flag está desactivado (el predeterminado), aplicamos el filtro.
		if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
			add_filter( 'theme_mod_slider', function( $ignored ) {
				global $storex_child_forced_slider;
				return $storex_child_forced_slider;
			}, 999999 );
		endif;
	}

		$info = get_theme_mod( 'info_sec' );
		$info_override = false;
		if ( is_string( $info ) && '' !== $info ) {
			$dec = json_decode( $info, true );
			if ( is_array( $dec ) && count( $dec ) === 3 ) {
				$s = isset( $dec[0]['subtitle'] ) ? (string) $dec[0]['subtitle'] : '';
				if (
					strpos( $s, 'Men Regular Fit' ) !== false ||
					strpos( $s, 'Casual Shirt' ) !== false ||
					strpos( $s, 'Stylish Shoes' ) !== false ||
					strpos( $s, 'Tracking Watch' ) !== false
				) {
					$info_override = true;
				}
			}
		}
		if ( $info_override ) {
		global $storex_child_forced_info;
		$storex_child_forced_info = storex_child_info_json_default();
		// PRUEBA 4 OFF = aplicar el filtro.
		if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
			add_filter( 'theme_mod_info_sec', function( $ignored ) {
				global $storex_child_forced_info;
				return $storex_child_forced_info;
			}, 999999 );
		endif;
	}
}
endif;
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
	add_action( 'wp', 'storex_child_force_frontend_defaults', 1 );
endif;

/* ==========================================================================
   MARCADOR: Cuando el usuario guarde cambios REALES en el Customizer,
   ponemos un transient 10 años para no volver a forzar el slider/info.
   Se detecta: se guarda desde customize_save_after y el slider NO es demo
   (no contiene Luxury Fashion y el count es < 4, o las 3 slides info tienen
   títulos que no son nuestra demo antigua).
   ========================================================================== */
if ( ! function_exists( 'storex_child_detect_user_personalization' ) ) :
	function storex_child_detect_user_personalization() {
		$saved_slider = get_theme_mod( 'slider' );
		$user_ok = false;
		if ( is_string( $saved_slider ) && '' !== $saved_slider ) {
			$dec = json_decode( $saved_slider, true );
			if ( is_array( $dec ) ) {
				$cnt = count( $dec );
				$raw_clean = preg_replace( '/\s+/', '', $saved_slider );
				if (
					( $cnt < 4 ) &&
					( strpos( $saved_slider, 'Luxury Fashion' ) === false ) &&
					( strpos( $saved_slider, 'New Release' ) === false ) &&
					( strpos( $saved_slider, 'Classic Styles' ) === false ) &&
					( strpos( $saved_slider, 'Smart Style Collection' ) === false )
				) {
					$user_ok = true;
				}
			}
		}
		$saved_info = get_theme_mod( 'info_sec' );
		$info_ok = false;
		if ( is_string( $saved_info ) && '' !== $saved_info ) {
			$dec2 = json_decode( $saved_info, true );
			if ( is_array( $dec2 ) ) {
				$cnt2 = count( $dec2 );
				if (
					( $cnt2 === 3 ) &&
					( strpos( $saved_info, 'Men Regular Fit' ) === false ) &&
					( strpos( $saved_info, 'Premium Stylish Shoes' ) === false ) &&
					( strpos( $saved_info, 'Advanced Health Tracking' ) === false ) &&
					( strpos( $saved_info, 'Best Seller' ) === false )
				) {
					$info_ok = true;
				}
			}
		}
		if ( $user_ok || $info_ok ) {
			set_transient( 'storex_child_user_saved_personalization', '1', 10 * YEAR_IN_SECONDS );
		}
	}
endif;
add_action( 'customize_save_after', 'storex_child_detect_user_personalization', 20 );

/* ==========================================================================
   FORZAMIENTO INFALIBLE: En FRONTEND (fuera de Customizer y sin
   transient de user-personalizado) directamente devolvemos nuestros
   defaults de 1 slider + 3 info cards. Esto no puede fallar por
   Object Cache, filtros intermedios ni nada.
   ========================================================================== */
if ( ! function_exists( 'storex_child_infailable_force_default_slider' ) ) :
	function storex_child_infailable_force_default_slider() {
		return storex_child_slider_json_default();
	}
endif;
if ( ! function_exists( 'storex_child_infailable_force_default_info' ) ) :
	function storex_child_infailable_force_default_info() {
		return storex_child_info_json_default();
	}
endif;
add_action( 'template_redirect', function() {
	if ( storex_child_is_customizer_context() ) {
		return;
	}
	if ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) {
		return; // PRUEBA 4 activada → nos saltamos TODO forzamiento de theme_mods
	}
	// Capa 1: si NO hay transient de personalización, forzamos nuestros defaults directamente.
	if ( ! get_transient( 'storex_child_user_saved_personalization' ) ) {
		add_filter( 'theme_mod_slider', 'storex_child_infailable_force_default_slider', 99999999 );
		add_filter( 'theme_mod_info_sec', 'storex_child_infailable_force_default_info', 99999999 );
	}

	// Capa 2 (INFALIBLE): incluso si el transient existe, nos aseguramos de que:
	// - Slider tenga SOLO 1 slide (nunca 5 repetidos).
	// - Info section tenga SOLO 3 items.
	add_filter( 'theme_mod_slider', function( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return storex_child_slider_json_default();
		}
		$decoded = json_decode( $value, true );
		if ( ! is_array( $decoded ) || empty( $decoded ) ) {
			return storex_child_slider_json_default();
		}
		// Más de 1 slide? Devolvemos solo el PRIMERO.
		if ( count( $decoded ) > 1 ) {
			$only_first = array( $decoded[0] );
			return wp_json_encode( $only_first );
		}
		return $value;
	}, PHP_INT_MAX );

	add_filter( 'theme_mod_info_sec', function( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return storex_child_info_json_default();
		}
		$decoded = json_decode( $value, true );
		if ( ! is_array( $decoded ) || count( $decoded ) !== 3 ) {
			return storex_child_info_json_default();
		}
		return $value;
	}, PHP_INT_MAX );
}, 1 );

if ( ! function_exists( 'storex_child_existing_product_cat_slugs' ) ) :
	function storex_child_existing_product_cat_slugs() {
		static $slugs = null;
		if ( null !== $slugs ) {
			return $slugs;
		}
		$slugs = array();
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return $slugs;
		}
		$terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'slugs',
			'number'     => 6,
		) );
		if ( is_array( $terms ) && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$slugs = array_values( $terms );
		}
		if ( empty( $slugs ) ) {
			$slugs = array( 'tecnologia', 'perifericos' );
		}
		return $slugs;
	}
endif;

if ( ! function_exists( 'storex_child_slider_json_default' ) ) :
	function storex_child_slider_json_default() {
		$plugin_url = defined( 'BURGER_COMPANION_PLUGIN_URL' )
			? BURGER_COMPANION_PLUGIN_URL
			: content_url( '/plugins/burger-companion/' );
		$image = trailingslashit( $plugin_url ) . 'inc/storex/images/slider/banner-img-1.png';
		$slides = array(
			array(
				'image_url' => esc_url( $image ),
				'title'     => 'ÚLTIMOS INGRESOS',
				'subtitle'  => wp_kses_post( 'Ofertas en Tecnología<br><span>y Útiles de Oficina</span>' ),
				'text'      => wp_kses_post( 'Desde <span>S/ 87.67</span>' ),
				'text2'     => 'Comprar ahora',
				'link'      => esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ?: home_url( '/' ) ),
				'id'        => 'customizer_repeater_slider_001',
			),
		);
		return wp_json_encode( $slides );
	}
endif;

/* ==========================================================================
   BANNER INTELIGENTE — Textos DINÁMICOS desde WooCommerce (Enfoque 1)
   --------------------------------------------------------------------------
   Lee el catálogo real de productos y construye los textos del banner
   SIN QUE TENGAS QUE EDITAR NADA MANUALMENTE. Reglas:

   • Título (upper-text):  prioridad 🔥 ofertas → ⭐ destacados → ✨ nuevos
   • Subtítulo (h2 grande): tagline genérico con cuenta de categorías
   • Texto secundario (h3): "Desde S/ X.XX" (precio mínimo REAL publicado)
   • Botón (text2):         "Ver catálogo"  →  /shop
   • Imagen de fondo:       banner-compuservitec-bg.webp (subida por ti)
   ========================================================================== */
if ( ! function_exists( 'storex_child_wc_dynamic_banner_data' ) ) :
	function storex_child_wc_dynamic_banner_data() {

		// 1) URL FIJA de la imagen de fondo (tu WebP en /assets/images/)
		$banner_bg = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/images/banner-compuservitec-bg.webp';

		// 2) Link y texto del botón
		$shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
		$cta_link = ( $shop_id && $shop_id > 0 ) ? get_permalink( $shop_id ) : home_url( '/' );
		$cta_text = 'Ver catálogo';

		// 3) Valores por defecto seguros (por si WooCommerce está desactivado)
		$upper_text   = 'GRUPO COMPUSERVITEC';
		$subtitle     = 'Tu aliado en informática<br><span>papelería y útiles</span>';
		$text_tercio  = wp_kses_post( 'Envíos a Cajamarca' );
		$categorias   = 0;
		$productos    = 0;

		// ------------------------------------------------------------------
		// 4) WooCommerce ACTIVO? → Extraemos datos reales (¡CACHEADOS 15min via transient!)
		//    Sin cache: wc_get_products limit=-1 → 1700 IDs = 30+ queries.
		// ------------------------------------------------------------------
		if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_get_products' ) ) {

			// Intentamos leer todo el bloque de stats del transient.
			$cached_stats = storex_child_with_cache(
				'wc_dynamic_banner_stats_v2',
				function() {

					$stats = array(
						'categorias'      => 0,
						'productos'       => 0,
						'hay_ofertas'     => false,
						'hay_destacados'  => false,
						'hay_nuevos'      => false,
						'min_price'       => null,
					);

					// 4.1 Cuenta de categorías con al menos 1 producto publicado
					$cats = get_terms( array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => true,
						'fields'     => 'ids',
					) );
					if ( ! is_wp_error( $cats ) ) {
						$stats['categorias'] = count( $cats );
					}

					// 4.2 Cuenta total de productos publicados
					//    YA NO usamos wc_get_products(-1) que es super lento.
					//    Usamos wp_count_posts que cuenta 1 sola fila MySQL.
					$counts_obj = wp_count_posts( 'product' );
					if ( is_object( $counts_obj ) && isset( $counts_obj->publish ) ) {
						$stats['productos'] = (int) $counts_obj->publish;
					}

					// 4.3 ¿Hay productos EN OFERTA? (tienen _sale_price)
					$sale_products = wc_get_products( array(
						'status'   => 'publish',
						'limit'    => 1, // Basta 1 para saber si hay o no
						'return'   => 'ids',
						'paginate' => false,
						'meta_query' => array(
							array(
								'key'     => '_sale_price',
								'value'   => '',
								'compare' => '!=',
							),
						),
					) );
					$stats['hay_ofertas'] = ( is_countable( $sale_products ) && count( $sale_products ) > 0 );

					// 4.4 ¿Hay productos DESTACADOS (starred)?
					$featured_products = wc_get_products( array(
						'status'   => 'publish',
						'limit'    => 1, // Basta 1
						'return'   => 'ids',
						'paginate' => false,
						'featured' => true,
					) );
					$stats['hay_destacados'] = ( is_countable( $featured_products ) && count( $featured_products ) > 0 );

					// 4.5 ¿Hay productos NUEVOS? (publicados en los últimos 30 días)
					$nuevos_products = wc_get_products( array(
						'status'   => 'publish',
						'limit'    => 1, // Basta 1
						'return'   => 'ids',
						'paginate' => false,
						'date_created' => '>' . ( time() - ( 30 * DAY_IN_SECONDS ) ),
					) );
					$stats['hay_nuevos'] = ( is_countable( $nuevos_products ) && count( $nuevos_products ) > 0 );

					// 4.6 PRECIO MÍNIMO REAL → lo extraemos con 1 SQL directo,
					//     ya que iterar 1700 productos PHP es muy lento.
					global $wpdb;
					$min_sql = "SELECT MIN(CAST(pm.meta_value AS DECIMAL(10,2))) "
						. "FROM {$wpdb->posts} p "
						. "INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID "
						. "WHERE p.post_type = 'product' "
						. "AND p.post_status = 'publish' "
						. "AND pm.meta_key IN ('_price','_regular_price','_sale_price') "
						. "AND pm.meta_value != '' "
						. "AND pm.meta_value IS NOT NULL";
					$min = $wpdb->get_var( $min_sql );
					if ( null !== $min && is_numeric( $min ) ) {
						$stats['min_price'] = floatval( $min );
					}

					return $stats;

				},
				15 * MINUTE_IN_SECONDS
			);

			$categorias     = (int)    ( $cached_stats['categorias']     ?? 0 );
			$productos      = (int)    ( $cached_stats['productos']      ?? 0 );
			$hay_ofertas    = (bool)   ( $cached_stats['hay_ofertas']    ?? false );
			$hay_destacados = (bool)   ( $cached_stats['hay_destacados'] ?? false );
			$hay_nuevos     = (bool)   ( $cached_stats['hay_nuevos']     ?? false );
			$min_price      = $cached_stats['min_price'] ?? null;

			// --- Armamos el UPPER-TEXT (prioridad: ofertas > destacados > nuevos) ---
			if ( $hay_ofertas ) {
				$upper_text = '🔥 OFERTAS SEMANALES';
			} elseif ( $hay_destacados ) {
				$upper_text = '⭐ PRODUCTOS DESTACADOS';
			} elseif ( $hay_nuevos ) {
				$upper_text = '✨ RECIÉN LLEGADOS';
			} else {
				$upper_text = '🎯 TODO EN TECNOLOGÍA Y PAPELERÍA';
			}

			// --- Armamos el SUBTÍTULO (tagline + métricas) ---
			$subtitle  = 'Informática<br>';
			$subtitle .= '<span>· Papelería · Útiles de Oficina</span>';

			// --- Armamos el TEXTO TERCIO (precio mínimo o métricas) ---
			if ( $min_price !== null ) {
				$text_tercio = wp_kses_post( 'Desde <span>S/ ' . number_format( $min_price, 2, '.', ',' ) . '</span>' );
			} elseif ( $productos > 0 ) {
				$text_tercio = wp_kses_post( sprintf(
					_n( '<span>%d</span> artículo disponible',
						'<span>%d</span> artículos en <span>%s categorías</span>',
						$productos,
						'storex-child'
					),
					$productos,
					$categorias > 0 ? $categorias : 'varias'
				) );
			} else {
				$text_tercio = wp_kses_post( 'Envíos a San Ignacio y Cajamarca' );
			}
		}

		return array(
			'image_url'  => esc_url( $banner_bg ),
			'title'      => $upper_text,
			'subtitle'   => wp_kses_post( $subtitle ),
			'text'       => $text_tercio,
			'text2'      => $cta_text,
			'link'       => esc_url( $cta_link ),
			'id'         => 'customizer_repeater_slider_001',
		);
	}
endif;

/* ==========================================================================
   Sobrescribe LA FUNCIÓN DE SLIDER POR DEFECTO para usar la versión
   DINÁMICA (arriba) en vez del JSON fijo en español viejo.
   Tiene prioridad PHP_INT_MAX para que nadie lo pise.
   ========================================================================== */
if ( ! function_exists( 'storex_child_slider_json_dynamic' ) ) :
	function storex_child_slider_json_dynamic() {
		$data = storex_child_wc_dynamic_banner_data();
		return wp_json_encode( array( $data ) );
	}
endif;
/* ----- tema: filtrar slider dinámico solo si SCX_DESACTIVAR_FILTROS_THEME_MODS es false ---- */
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
	add_filter( 'theme_mod_slider', 'storex_child_slider_json_dynamic', PHP_INT_MAX );
endif;

if ( ! function_exists( 'storex_child_info_json_default' ) ) :
	function storex_child_info_json_default() {

		$base_img = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/images/info/';
		$shop     = ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 )
			? get_permalink( wc_get_page_id( 'shop' ) )
			: home_url( '/' );

		// ------------------------------------------------------------------
		// Lógica DINÁMICA: resolvemos la categoría asociada a cada tarjeta,
		// su precio mínimo y su enlace (si WooCommerce está activo).
		// ------------------------------------------------------------------
		$cards = array(
			array(
				'slug_fallback' => 'tecnologia-y-accesorios',
				'name_fallback' => 'Tecnología y Accesorios',
				'label'         => 'Categoría Destacada',
				'subtitle_fix'  => 'Tecnología y Accesorios',
				'icon'          => 'fa-laptop',
				'img'           => $base_img . 'info-01-tecnologia.webp',
				'id'            => 'customizer_repeater_info_001',
			),
			array(
				'slug_fallback' => 'papel-y-cartulina',
				'name_fallback' => 'Papelería y Útiles',
				'label'         => 'Útiles Escolares',
				'subtitle_fix'  => 'Papelería y Útiles',
				'icon'          => 'fa-book',
				'img'           => $base_img . 'info-02-papeleria-utiles.webp',
				'id'            => 'customizer_repeater_info_002',
			),
			array(
				'slug_fallback' => 'organizadores-y-archivo',
				'name_fallback' => 'Accesorios de Oficina',
				'label'         => 'Organización',
				'subtitle_fix'  => 'Accesorios de Oficina',
				'icon'          => 'fa-folder-open',
				'img'           => $base_img . 'info-03-accesorios-oficina.webp',
				'id'            => 'customizer_repeater_info_003',
			),
		);

		$items = array();

		foreach ( $cards as $c ) {

			$cat_id   = null;
			$cat_name = $c['subtitle_fix'];
			$cat_link = $shop;
			$count    = 0;
			$min_price = null;

			if ( class_exists( 'WooCommerce' ) && taxonomy_exists( 'product_cat' ) ) {

				// Búsqueda 1: por slug exacto
				$term = get_term_by( 'slug', $c['slug_fallback'], 'product_cat' );

				// Búsqueda 2: por nombre aproximado (si slug no coincide)
				if ( ! $term || is_wp_error( $term ) ) {
					$needle = mb_strtolower( $c['subtitle_fix'] );
					$_all   = get_terms( array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => false,
						'number'     => 200,
					) );
					if ( is_array( $_all ) && ! empty( $_all ) ) {
						foreach ( $_all as $_t ) {
							$_n = mb_strtolower( trim( $_t->name ) );
							if ( $_n === $needle || mb_strpos( $_n, $needle ) !== false ) {
								$term = $_t;
								break;
							}
						}
					}
				}

				if ( $term && ! is_wp_error( $term ) ) {
					$cat_id   = (int) $term->term_id;
					$cat_name = $term->name;
					$cat_link = get_term_link( $term, 'product_cat' );
					if ( is_wp_error( $cat_link ) ) { $cat_link = $shop; }
					$count    = (int) $term->count;

					// ---- Precio MÍNIMO dentro de ESTA categoría ----
					$prods = wc_get_products( array(
						'status'   => 'publish',
						'limit'    => -1,
						'return'   => 'objects',
						'paginate' => false,
						'category' => array( $term->slug ),
					) );
					if ( is_array( $prods ) && ! empty( $prods ) ) {
						foreach ( $prods as $p ) {
							if ( ! is_a( $p, 'WC_Product' ) ) { continue; }
							$pr = $p->get_price();
							if ( $pr === '' || $pr === null ) { continue; }
							$pr_f = floatval( $pr );
							if ( $min_price === null || $pr_f < $min_price ) {
								$min_price = $pr_f;
							}
						}
					}
				}
			}

			// --- Título superior (label) y subtítulo ---
			$title    = $c['label'];
			$subtitle = esc_html( $cat_name );

			// --- Texto terciario: precio mínimo o conteo ---
			if ( $min_price !== null ) {
				$text = wp_kses_post(
					sprintf(
						'<span>Desde</span> S/ %s',
						number_format( $min_price, 2, '.', ',' )
					)
				);
			} elseif ( $count > 0 ) {
				$text = wp_kses_post(
					sprintf(
						_n( '<span>%d</span> artículo',
							'<span>%d</span> artículos',
							$count,
							'storex-child'
						),
						$count
					)
				);
			} else {
				$text = wp_kses_post( sprintf( '<span>%s</span>', esc_html( $cat_name ) ) );
			}

			$items[] = array(
				'image_url' => esc_url( $c['img'] ),
				'title'     => $title,
				'subtitle'  => $subtitle,
				'text'      => $text,
				'text2'     => 'Ver categoría',
				'link'      => esc_url( $cat_link ),
				'icon'      => $c['icon'],
				'id'        => $c['id'],
			);
		}

		return wp_json_encode( $items );
	}
endif;

if ( ! function_exists( 'storex_child_force_theme_mod_1' ) ) :
	function storex_child_force_theme_mod_1( $value ) {
		return '1';
	}
endif;
/* ---------- flags SCX_DESACTIVAR_FILTROS_THEME_MODS: si está ON se salta TODO esto ----------- */
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
	add_filter( 'theme_mod_hs_top_categories',        'storex_child_force_theme_mod_1', 999 );
	add_filter( 'theme_mod_hs_popular_categories',    'storex_child_force_theme_mod_1', 999 );
	add_filter( 'theme_mod_hs_product_section',       'storex_child_force_theme_mod_1', 999 );
	add_filter( 'theme_mod_hs_info_section',          'storex_child_force_theme_mod_1', 999 );
	add_filter( 'theme_mod_hide_show_offer',          'storex_child_force_theme_mod_1', 999 );
	add_filter( 'theme_mod_hide_show_footer_card',    'storex_child_force_theme_mod_1', 999 );
	add_filter( 'theme_mod_hs_above_header_top',      'storex_child_force_theme_mod_1', 999 );
	add_filter( 'theme_mod_hs_above_support_info',    'storex_child_force_theme_mod_1', 999 );
	add_filter( 'theme_mod_hs_hdr_social_icon',       'storex_child_force_theme_mod_1', 999 );
endif;

/* ==========================================================================
   FOOTER — Copyright personalizado y ocultamos las 6 footer cards demo
   --------------------------------------------------------------------------
   1) Copyright SIN "Burger Software"
   2) hide_show_footer_card = 0 (oculta la vieja tira de tarjetas demo)
   ========================================================================== */
if ( ! function_exists( 'storex_child_footer_copyright_texto' ) ) :
	function storex_child_footer_copyright_texto( $ignorado ) {
		return sprintf(
			'© %s <strong>GRUPO COMPUSERVITEC</strong> · San Ignacio, Cajamarca · Venta de equipos informáticos, papelería y útiles de oficina · Todos los derechos reservados.',
			esc_html( date_i18n( 'Y' ) )
		);
	}
endif;
/* --- SCX_DESACTIVAR_FILTROS_THEME_MODS: si está ON, no aplicamos copyright ni footer_cards --- */
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
	add_filter( 'theme_mod_storex_footer_copyright', 'storex_child_footer_copyright_texto', 999 );

	if ( ! function_exists( 'storex_child_footer_cards_off' ) ) :
		function storex_child_footer_cards_off() { return '0'; }
	endif;
	add_filter( 'theme_mod_hide_show_footer_card', 'storex_child_footer_cards_off', 999999 );
	remove_all_filters( 'storex_footer_card', 999999 );
	add_filter( 'storex_footer_card', '__return_empty_string', 999999 );
endif;

/* ==========================================================================
   FOOTER — Quitar widgets demo burger-companion (Londres/About/Categs blog)
   y REEMPLAZARLOS por nuestras 4 columnas personalizadas (Contacto,
   Enlaces Rápidos, Atención al Cliente, Newsletter + pagos).
   --------------------------------------------------------------------------
   No modificamos options/sidebars_widgets de la BD: interceptamos directamente
   el render del sidebar "storex-footer-widget-area" vía el filtro
   "dynamic_sidebar_params" y si después de dynamic_sidebar() no se pintó nada
   forzamos nuestro HTML con "wp_footer" con reemplazo al <div class="widget-section">.
   ========================================================================== */

/* --- 1) Bandera global que indica si dynamic_sidebar del footer pintó algo --- */
function storex_child_flag_sidebar_has_widgets( $sidebars_widgets ) {
	if ( is_admin() ) { return $sidebars_widgets; }
	$GLOBALS['storex_child_footer_widgets_exist'] = ! empty( $sidebars_widgets['storex-footer-widget-area'] ) && is_array( $sidebars_widgets['storex-footer-widget-area'] ) && count( $sidebars_widgets['storex-footer-widget-area'] ) > 0;
	return $sidebars_widgets;
}
add_filter( 'sidebars_widgets', 'storex_child_flag_sidebar_has_widgets', 9999998 );

/* --- 2) Helper: pinta nuestro HTML de 4 columnas (llamado desde múltiples puntos) --- */
if ( ! function_exists( 'storex_child_render_footer_cols_html' ) ) :
	function storex_child_render_footer_cols_html() {
		$fb  = 'https://www.facebook.com/compuservitec.eirl';
		$wa  = 'https://wa.me/51971387882';
		$tel = 'tel:+51971387882';
		$em  = 'mailto:compuservitec.ge@gmail.com';
		$home = home_url( '/' );
		$shop = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : $home;
		$logo = ( function_exists( 'get_custom_logo' ) && get_custom_logo() ) ? get_custom_logo() : '';
		$mailto_dest = 'compuservitec.ge@gmail.com';
		$mailto_subject = rawurlencode( 'Suscripción ofertas Compuservitec' );
		?>
		<div class="scx-foo-col scx-foo-contacto">
			<div class="scx-foo-logo-marca">
				<div class="scx-foo-marca-grupo">GRUPO</div>
				<div class="scx-foo-marca-nombre">COMPUSERVITEC</div>
			</div>
			<ul class="scx-foo-lista">
				<li><i class="fa fa-map-marker"></i> Av. San Ignacio de Loyola Nro. 506<br>&nbsp;&nbsp;San Ignacio, Cajamarca</li>
				<li><i class="fa fa-phone"></i> <a href="<?php echo esc_url( $tel ); ?>">971 387 882</a></li>
				<li><i class="fa fa-whatsapp"></i> <a href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">WhatsApp</a></li>
				<li><i class="fa fa-envelope"></i> <a href="<?php echo esc_url( $em ); ?>">compuservitec.ge@gmail.com</a></li>
				<li><i class="fa fa-clock-o"></i> Lun - Sab: 8:00 – 20:00<br>&nbsp;&nbsp;Dom:8:00 -13:00</li>
				<li class="scx-foo-redes">
					<a href="<?php echo esc_url( $fb ); ?>" target="_blank" rel="noopener" aria-label="Facebook Compuservitec"><i class="fa fa-facebook"></i></a>
				</li>
			</ul>
		</div>

		<div class="scx-foo-col scx-foo-enlaces">
			<h4 class="scx-foo-titulo">Enlaces Rápidos</h4>
			<ul>
				<li><a href="<?php echo esc_url( $home ); ?>"><i class="fa fa-angle-right"></i> Inicio</a></li>
				<li><a href="<?php echo esc_url( $shop ); ?>"><i class="fa fa-angle-right"></i> Catálogo de productos</a></li>
				<li><a href="<?php echo esc_url( $home . '#categorias-populares' ); ?>"><i class="fa fa-angle-right"></i> Categorías Populares</a></li>
				<li><a href="<?php echo esc_url( $home . '#nosotros' ); ?>"><i class="fa fa-angle-right"></i> ¿Quiénes somos?</a></li>
				<li><a href="<?php echo esc_url( $shop . '?orderby=onsale' ); ?>"><i class="fa fa-angle-right"></i> Ofertas</a></li>
			</ul>
		</div>

		<div class="scx-foo-col scx-foo-ayuda">
			<h4 class="scx-foo-titulo">Ayuda y Atención</h4>
			<ul>
				<li><a href="#ayuda-envios"><i class="fa fa-angle-right"></i> Política de envíos</a></li>
				<li><a href="#ayuda-cambios"><i class="fa fa-angle-right"></i> Cambios y devoluciones</a></li>
				<li><a href="#ayuda-pagos"><i class="fa fa-angle-right"></i> Métodos de pago</a></li>
				<li><a href="#ayuda-privacidad"><i class="fa fa-angle-right"></i> Política de privacidad</a></li>
				<li><a href="#ayuda-terminos"><i class="fa fa-angle-right"></i> Términos y condiciones</a></li>
				<li><a href="#ayuda-horarios"><i class="fa fa-angle-right"></i> Horarios de atención</a></li>
			</ul>
		</div>

		<div class="scx-foo-col scx-foo-nl">
			<h4 class="scx-foo-titulo">Recibe nuestras ofertas</h4>
			<p class="scx-foo-nl-desc">Deja tu correo y entérate de promociones:</p>
			<form id="scx-nl-form" class="scx-nl-form"
				  action="#"
				  method="get" target="_self">
				<input type="email" id="scx-nl-email"
					   placeholder="Tu correo electrónico"
					   aria-label="Tu correo electrónico" required>
				<button type="submit" class="scx-nl-boton">
					<span>Recibir ofertas</span> <i class="fa fa-paper-plane"></i>
				</button>
			</form>

			<h4 class="scx-foo-titulo scx-foo-pagos-tit">Métodos de pago aceptados</h4>
			<div class="scx-foo-pagos scx-pagos-3col" aria-label="Métodos de pago aceptados">
				<?php
				$uri_child = get_stylesheet_directory_uri();
				$path_child = get_stylesheet_directory();

				$pagos = [
					[ 'slug' => 'visa', 'name' => 'Visa' ],
					[ 'slug' => 'yape', 'name' => 'Yape' ],
					[ 'slug' => 'plin', 'name' => 'Plin' ],
				];

				foreach ( $pagos as $pago ) :
					// Busca en 2 carpetas (por si te equivocaste de carpeta):
					//   1) assets/pagos/       (la que creaste tú)
					//   2) assets/images/pagos/ (la que sugerí yo)
					$rutas_webp = [
						$path_child . '/assets/pagos/' . $pago['slug'] . '.webp',
						$path_child . '/assets/images/pagos/' . $pago['slug'] . '.webp',
					];
					$rutas_png = [
						$path_child . '/assets/pagos/' . $pago['slug'] . '.png',
						$path_child . '/assets/images/pagos/' . $pago['slug'] . '.png',
					];
					$rutas_jpg = [
						$path_child . '/assets/pagos/' . $pago['slug'] . '.jpg',
						$path_child . '/assets/images/pagos/' . $pago['slug'] . '.jpg',
					];
					$src  = '';

					foreach ( $rutas_webp as $r ) { if ( ! $src && file_exists( $r ) ) {
						$folder = ( strpos( $r, '/assets/pagos/' ) !== false ) ? 'pagos' : 'images/pagos';
						$src = $uri_child . '/assets/' . $folder . '/' . $pago['slug'] . '.webp';
					}}
					if ( ! $src ) foreach ( $rutas_png as $r ) { if ( file_exists( $r ) ) {
						$folder = ( strpos( $r, '/assets/pagos/' ) !== false ) ? 'pagos' : 'images/pagos';
						$src = $uri_child . '/assets/' . $folder . '/' . $pago['slug'] . '.png';
						break;
					}}
					if ( ! $src ) foreach ( $rutas_jpg as $r ) { if ( file_exists( $r ) ) {
						$folder = ( strpos( $r, '/assets/pagos/' ) !== false ) ? 'pagos' : 'images/pagos';
						$src = $uri_child . '/assets/' . $folder . '/' . $pago['slug'] . '.jpg';
						break;
					}}
					?>
					<figure class="scx-pago scx-pago-<?php echo esc_attr( $pago['slug'] ); ?>" title="<?php echo esc_attr( $pago['name'] ); ?>">
						<?php if ( $src !== '' ) : ?>
							<img src="<?php echo esc_url( $src ); ?>"
								 alt="<?php echo esc_attr( $pago['name'] ); ?>"
								 loading="lazy"
								 decoding="async">
						<?php else : ?>
							<svg class="scx-pago-placeholder" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="<?php echo esc_attr( $pago['name'] ); ?>">
								<rect x="0" y="0" width="60" height="40" rx="4" fill="#ffffff"/>
								<text x="30" y="27" text-anchor="middle"
									  font-family="Arial, sans-serif"
									  font-weight="800" font-size="16" fill="#1c6eab">
									<?php echo esc_html( mb_strtoupper( $pago['name'] ) ); ?>
								</text>
							</svg>
						<?php endif; ?>
					</figure>
				<?php endforeach; ?>
			</div>
		</div>
		<script type="application/javascript">
		(function(){
			var f = document.getElementById('scx-nl-form');
			var i = document.getElementById('scx-nl-email');
			if (!f || !i) return;
			f.addEventListener('submit', function(ev){
				ev.preventDefault();
				var correo = (i.value || '').trim();
				if (correo === '') { i.focus(); return false; }
				var cuerpo = encodeURIComponent('Hola, quiero suscribirme a las ofertas. Mi correo: ' + correo);
				var mailto_url = 'mailto:<?php echo esc_js( $mailto_dest ); ?>?subject=<?php echo $mailto_subject; ?>&body=' + cuerpo;
				window.location.href = mailto_url;
				return false;
			});
		})();
		</script>
		<?php
	}
endif;

/* --- 3) Interceptamos dynamic_sidebar_params para contar cuántos widgets reales se pintan --- */
$GLOBALS['storex_child_footer_rendered_widget_count'] = 0;
add_filter( 'dynamic_sidebar_params', function( $params ) {
	if ( is_admin() || empty( $params[0]['id'] ) ) { return $params; }
	if ( $params[0]['id'] === 'storex-footer-widget-area' ) {
		$GLOBALS['storex_child_footer_rendered_widget_count']++;
	}
	return $params;
}, 999999 );
if ( ! function_exists( 'storex_child_theme_mod_slider' ) ) :
	function storex_child_theme_mod_slider( $value ) {
		if ( empty( $value ) ) {
			return storex_child_slider_json_default();
		}
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				$count  = count( $decoded );
				$first  = isset( $decoded[0] ) ? $decoded[0] : array();
				$title  = isset( $first['title'] ) ? (string) $first['title'] : '';
				$sub    = isset( $first['subtitle'] ) ? (string) $first['subtitle'] : '';

				$texts_all_default = true;
				if ( $count >= 2 ) {
					$first_hash = md5( trim( wp_strip_all_tags( $title ) . '|' . wp_strip_all_tags( $sub ) ) );
					for ( $i = 1; $i < $count; $i++ ) {
						$t = isset( $decoded[ $i ]['title'] ) ? (string) $decoded[ $i ]['title'] : '';
						$s = isset( $decoded[ $i ]['subtitle'] ) ? (string) $decoded[ $i ]['subtitle'] : '';
						$hash = md5( trim( wp_strip_all_tags( $t ) . '|' . wp_strip_all_tags( $s ) ) );
						if ( $hash !== $first_hash ) {
							$texts_all_default = false;
							break;
						}
					}
				} else {
					$texts_all_default = false;
				}

				$is_default = (
					$count >= 4 ||
					$texts_all_default ||
					strpos( $title, 'Luxury Fashion' ) !== false ||
					strpos( $title, 'New Release' ) !== false ||
					strpos( $title, 'LATEST ARRIVAL' ) !== false ||
					strpos( $title, 'ÚLTIMOS INGRESOS' ) !== false ||
					strpos( $sub, 'Smart Style Collection' ) !== false ||
					strpos( $sub, 'Classic Styles' ) !== false ||
					strpos( $sub, 'Fashion Display' ) !== false ||
					strpos( $sub, 'Ofertas en Tecnología' ) !== false ||
					strpos( $value, 'Smart Style Collection' ) !== false ||
					strpos( $value, 'Luxury Fashion' ) !== false
				);
				if ( $is_default ) {
					return storex_child_slider_json_default();
				}
			}
		}
		return $value;
	}
endif;
/* --- SCX_DESACTIVAR_FILTROS_THEME_MODS ON? → saltarse todos los add_filter de theme_mods --- */
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
	add_filter( 'theme_mod_slider', 'storex_child_theme_mod_slider', 999 );
endif;

if ( ! function_exists( 'storex_child_theme_mod_info_sec' ) ) :
	function storex_child_theme_mod_info_sec( $value ) {
		if ( empty( $value ) ) {
			return storex_child_info_json_default();
		}
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) && ! empty( $decoded ) ) {
				$first    = $decoded[0];
				$subtitle = isset( $first['subtitle'] ) ? (string) $first['subtitle'] : '';
				$title    = isset( $first['title'] ) ? (string) $first['title'] : '';
				if (
					count( $decoded ) === 3 &&
					(
						strpos( $subtitle, 'Men Regular Fit' ) !== false ||
						strpos( $subtitle, 'Casual Shirt' ) !== false ||
						strpos( $subtitle, 'Stylish Shoes' ) !== false ||
						strpos( $subtitle, 'Tracking Watch' ) !== false ||
						strpos( $title, 'Best Seller' ) !== false ||
						strpos( $value, 'Men Regular Fit' ) !== false
					)
				) {
					return storex_child_info_json_default();
				}
			}
		}
		return $value;
	}
endif;
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
	add_filter( 'theme_mod_info_sec', 'storex_child_theme_mod_info_sec', 999 );
endif;

if ( ! function_exists( 'storex_child_theme_mod_top_categories_id' ) ) :
	function storex_child_theme_mod_top_categories_id( $value ) {
		if ( empty( $value ) || ! is_array( $value ) ) {
			return storex_child_existing_product_cat_slugs();
		}
		return $value;
	}
endif;
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
	add_filter( 'theme_mod_top_categories_id', 'storex_child_theme_mod_top_categories_id', 999 );
endif;

if ( ! function_exists( 'storex_child_theme_mod_product_cat02_id' ) ) :
	function storex_child_theme_mod_product_cat02_id( $value ) {
		if ( empty( $value ) || ! is_array( $value ) ) {
			return storex_child_existing_product_cat_slugs();
		}
		return $value;
	}
endif;
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
	add_filter( 'theme_mod_product_cat02_id', 'storex_child_theme_mod_product_cat02_id', 999 );
endif;

if ( ! function_exists( 'storex_child_theme_mod_product_cat_id' ) ) :
	function storex_child_theme_mod_product_cat_id( $value ) {
		if ( empty( $value ) || ! is_array( $value ) ) {
			return storex_child_existing_product_cat_slugs();
		}
		return $value;
	}
endif;
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
	add_filter( 'theme_mod_product_cat_id', 'storex_child_theme_mod_product_cat_id', 999 );
endif;

if ( ! function_exists( 'storex_child_theme_mod_product_display_num' ) ) :
	function storex_child_theme_mod_product_display_num( $value ) {
		if ( empty( $value ) ) {
			return '8';
		}
		return $value;
	}
endif;
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :
	add_filter( 'theme_mod_product_display_num', 'storex_child_theme_mod_product_display_num', 999 );
endif;

/* ==========================================================================
   TRADUCCIONES: Títulos de secciones y labels (theme_mod)
   ==========================================================

   FORZAMOS los textos en español en el frontend, aunque haya un valor antiguo
   guardado en la base de datos (porque el plugin guardó "Popular Categories"
   en inglés cuando activamos el tema hijo por primera vez).

   Regla:
   - Si en el Customizer tu editas el valor y guardas, el valor NUEVO se
     detecta porque ya no coincide con el string inglés por defecto → usa el TUYO.
   - Si el valor en BD es el original inglés ("Popular Categories", etc.) →
     lo ignoramos y devolvemos el texto español.
   - Si quieres FORZAR SIEMPRE el texto español (incluso si editas en el
     Customizer), cambia `=== $en_default` por un `true` en cada filtro.
   ========================================================================== */

if ( ! function_exists( 'storex_child_theme_mod_title_es' ) ) :
	function storex_child_theme_mod_title_es( $value, $en_default, $es_default ) {
		if ( $value === $en_default ) {
			return $es_default;
		}
		if ( empty( $value ) ) {
			return $es_default;
		}
		return $value;
	}
endif;

$shop_url = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' );

if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :

	add_filter( 'theme_mod_popular_categories_title', function( $v ) use ( $shop_url ) {
		return storex_child_theme_mod_title_es( $v, 'Popular Categories', 'Categorías Populares' );
	}, 999 );

	add_filter( 'theme_mod_view_all_category_label', function( $v ) use ( $shop_url ) {
		return storex_child_theme_mod_title_es( $v, 'View All Category', 'Ver todas las categorías' );
	}, 999 );

	add_filter( 'theme_mod_view_all_category_url', function( $v ) use ( $shop_url ) {
		return storex_child_theme_mod_title_es( $v, '', $shop_url );
	}, 999 );

	add_filter( 'theme_mod_product_title', function( $v ) {
		return storex_child_theme_mod_title_es( $v, 'Trending Product', 'Productos Destacados' );
	}, 999 );

endif; // <-- fin SCX_DESACTIVAR_FILTROS_THEME_MODS (bloque de títulos de sección)

/* ==========================================================================
   TRADUCCIONES: Datos demo del header (Above Header + support info + top bar)
   Los originales tienen:
   - Dirección de Nueva York / Londres, emails wixipi6142@hidevak.com,
     teléfono indio "91 8578 790", oferta "Save Up to 60% Today".
   Los reemplazamos por placeholders en español. Tú luego los ajustas desde
   el Customizer (Frontpage Sections → Above Header / Header → editar).
   ========================================================================== */
if ( ! function_exists( 'storex_child_header_contact_default' ) ) :
	function storex_child_header_contact_default() {
		$items = array(
			array(
				'icon_value' => 'fa-map-marker',
				'title'      => 'San Ignacio, Cajamarca, Perú',
				'link'       => '#',
				'id'         => 'customizer_repeater_header_contact_001',
			),
			array(
				'icon_value' => 'fa-envelope-o',
				'title'      => 'contacto@compuservitec.pe',
				'link'       => 'mailto:contacto@compuservitec.pe',
				'id'         => 'customizer_repeater_header_contact_002',
			),
		);
		return wp_json_encode( $items );
	}
endif;

/* ----------- Bloque traducciones Header (dentro del flag para SCX_DESACTIVAR_FILTROS_THEME_MODS) ---------- */
if ( ! ( defined( 'SCX_DESACTIVAR_FILTROS_THEME_MODS' ) && SCX_DESACTIVAR_FILTROS_THEME_MODS ) ) :

	add_filter( 'theme_mod_above_header_top_content', function( $v ) {
		return storex_child_theme_mod_title_es( $v, '', storex_child_header_contact_default() );
	}, 999 );

	add_filter( 'theme_mod_abv_hdr_support_info_ttl', function( $v ) {
		return storex_child_theme_mod_title_es( $v, 'Call out Hotline 24/7', 'Atención al cliente' );
	}, 999 );
	add_filter( 'theme_mod_abv_hdr_support_info_subttl', function( $v ) {
		return storex_child_theme_mod_title_es( $v, '91 8578 790', '987 654 321' );
	}, 999 );

	add_filter( 'theme_mod_top_bar_offer_title', function( $v ) {
		$es_default = '🎉 Bienvenido a Compuservitec — Envíos a todo Cajamarca';
		$en_default_contains = 'Save Up to 60%';
		if ( is_string( $v ) && strpos( $v, $en_default_contains ) !== false ) {
			return $es_default;
		}
		return storex_child_theme_mod_title_es( $v, '', $es_default );
	}, 999 );

endif; // fin SCX_DESACTIVAR_FILTROS_THEME_MODS

endif; // Fin SCX_DESACTIVAR_FILTROS_THEME_MODS: todos los add_filter theme_mod_* quedan dentro del IF

/* ==========================================================================
   TRADUCCIONES: Strings del tema padre y plugin burger-companion
   (los que están como __('','storex') o esc_html_e('', 'storex') y no pasan
   por get_theme_mod). Última capa de fallback para cualquier inglés residual
   que aún aparezca en frontend.
   ========================================================================== */
if ( ! function_exists( 'storex_child_gettext_es' ) ) :
	function storex_child_gettext_es( $translation, $text, $domain ) {

		$storex_strings = array(
			/* ---------- Header / buscador ---------- */
			'Select Category'       => 'Seleccionar categoría',
			'Find Your products...' => 'Buscar productos...',
			'Search Products'       => 'Buscar productos',
			'All Categories'        => 'Todas las categorías',
			'Browse Categories'     => 'Explorar categorías',
			'Search'                => 'Buscar',
			/* ---------- Botones CTA ---------- */
			'Shop Now'              => 'Comprar ahora',
			'Shop now'              => 'Comprar ahora',
			'Add to cart'           => 'Añadir al carrito',
			'View All Category'     => 'Ver todas las categorías',
			'View All'              => 'Ver todo',
			/* ---------- Títulos de secciones ---------- */
			'Popular Categories'    => 'Categorías Populares',
			'Top Categories'        => 'Categorías Principales',
			'Top Categories Section' => 'Sección Categorías Principales',
			'Popular Categories Section' => 'Sección Categorías Populares',
			'Product Section'       => 'Sección de Productos',
			'Trending Product'      => 'Productos Destacados',
			'Info Section'          => 'Sección Informativa',
			'Footer Card'           => 'Tarjeta del pie',
			/* ---------- Etiquetas promo de sliders ---------- */
			'NEW'                   => 'NUEVO',
			'OFFERS'                => 'OFERTAS',
			'LATEST'                => 'LO MÁS NUEVO',
			'LATEST ARRIVAL'        => 'ÚLTIMOS INGRESOS',
			'Best Seller'           => 'Más vendidos',
			'Flash Sale'            => 'Oferta Flash',
			'Limited Offer'         => 'Oferta Limitada',
			'New Release'           => 'Nuevo Lanzamiento',
			/* ---------- Customizer (texto visible al editar) ---------- */
			'Header'                => 'Cabecera',
			'Above Header'          => 'Barra Superior',
			'Footer'                => 'Pie de página',
			'Settings'              => 'Ajustes',
			'Hide/Show'             => 'Ocultar/Mostrar',
			'Logo Width'            => 'Ancho del logo',
			'Support Info'          => 'Info de contacto',
			'Icon'                  => 'Ícono',
			'Title'                 => 'Título',
			'Sub Title'             => 'Subtítulo',
			'Information'           => 'Información',
			'Add New Information'   => 'Añadir nueva información',
			'Select category'       => 'Seleccionar categoría',
			'No of Product Display' => 'Número de productos a mostrar',
			/* ---------- Typography ---------- */
			'Typography'            => 'Tipografía',
			'Body Typography'       => 'Tipografía del cuerpo',
			'Headings'              => 'Encabezados',
			'Size'                  => 'Tamaño',
			'Line Height'           => 'Interlineado',
			'Font Style'            => 'Estilo de fuente',
			'Inherit'               => 'Heredar',
			'Normal'                => 'Normal',
			'Italic'                => 'Cursiva',
			'oblique'               => 'oblicua',
			'Default'               => 'Por defecto',
			'Font Size'             => 'Tamaño de fuente',
			'Uppercase'             => 'MAYÚSCULAS',
			'Lowercase'             => 'minúsculas',
			'Capitalize'            => 'Capitalizadas',
			'Text Transform'        => 'Transformación',
			'Transform'             => 'Transformación',
			/* ---------- Footer ---------- */
			'About Company'         => 'Sobre la empresa',
			'Categories'            => 'Categorías',
			'Archives'              => 'Archivos',
			'Pages'                 => 'Páginas',
			'Search'                => 'Buscar',
			'FooterCard'            => 'TarjetaPie',
			'Add New FooterCard'    => 'Añadir nueva TarjetaPie',
			/* ---------- Botones/texto WooCommerce (por si no traduce el paquete es_PE) ---------- */
			'Add to cart'           => 'Añadir al carrito',
			'Sale'                  => 'Oferta',
			'Original price was:'  => 'Precio original:',
			'Current price is:'   => 'Precio actual:',
		);

		$dominios_permitidos = array( 'storex', 'storecart', 'woocommerce', 'default', 'wc-frontend-manager', 'wc-multivendor-marketplace', 'wcfm' );

		$matched_domain = in_array( $domain, $dominios_permitidos, true );

		if ( $matched_domain && isset( $storex_strings[ $text ] ) ) {
			return $storex_strings[ $text ];
		}

		return $translation;
	}
endif;
add_filter( 'gettext', 'storex_child_gettext_es', 999, 3 );

/* El mismo diccionario para strings traducidos CON contexto (por si acaso). */
if ( ! function_exists( 'storex_child_gettext_with_context_es' ) ) :
	function storex_child_gettext_with_context_es( $translation, $text, $context, $domain ) {
		return storex_child_gettext_es( $translation, $text, $domain );
	}
endif;
add_filter( 'gettext_with_context', 'storex_child_gettext_with_context_es', 999, 4 );

/* ==========================================================================
   ÚLTIMA CAPA: Output Buffering + HOOK SHUTDOWN (prioridad 0)
   + FLAGS para activar/desactivar optimizaciones y retroceder fácilmente.
   ========================================================================== */

/* --- helpers pequeños para decidir si saltarse el OB en ciertas páginas --- */
if ( ! function_exists( 'storex_child_es_pagina_woo_pesada' ) ) {
	function storex_child_es_pagina_woo_pesada() {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return false;
		}
		// Lista concreta: shop / taxonomías producto / producto individual /
		// cart / checkout / my-account / endpoint woocommerce
		if ( is_shop()
			|| is_product_category()
			|| is_product_tag()
			|| is_product_taxonomy()
			|| is_product()
			|| is_cart()
			|| is_checkout()
			|| is_checkout_pay_page()
			|| is_wc_endpoint_url()
			|| ( function_exists( 'is_account_page' ) && is_account_page() ) ) {
			return true;
		}
		return false;
	}
}

/* --- A) Abrir buffer MUY temprano en el render del front --- */
if ( ! function_exists( 'storex_child_ob_open_early' ) ) {
	function storex_child_ob_open_early() {
		// Flag PRUEBA 2: desactivar OB completamente (medir impacto)
		if ( defined( 'SCX_DESACTIVAR_OB_COMPLETAMENTE' ) && SCX_DESACTIVAR_OB_COMPLETAMENTE ) {
			return;
		}
		// Flag PRUEBA 1: saltarse OB en Woo páginas pesadas
		if ( defined( 'SCX_SALTAR_OB_EN_WOOCOMMERCE' ) && SCX_SALTAR_OB_EN_WOOCOMMERCE ) {
			if ( storex_child_es_pagina_woo_pesada() ) {
				echo '<!-- SCX_OB_SALTADO_POR_WOOCOMMERCE -->';
				return;
			}
		}
		if ( function_exists( 'storex_child_is_customizer_context' ) && storex_child_is_customizer_context() ) {
			return;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		if (
			strpos( $uri, '/wp-admin/' ) !== false ||
			strpos( $uri, 'admin-ajax' ) !== false ||
			strpos( $uri, 'wp-json' ) !== false ||
			strpos( $uri, '/wp-login' ) !== false
		) {
			return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { return; }
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) { return; }
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) { return; }
		if ( PHP_SAPI === 'cli' ) { return; }

		// Flag PRUEBA 3: OB cache simple (archivo) — solo visitantes sin login.
		// No se usa si estamos en el admin.
		if (
			defined( 'SCX_OB_CACHE_SIMPLE' )
			&& SCX_OB_CACHE_SIMPLE
			&& ! is_user_logged_in()
		) {
			$scx_key = md5( ( is_ssl() ? 'https-' : 'http-' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			$scx_dir = WP_CONTENT_DIR . '/cache/scx-ob';
			if ( ! is_dir( $scx_dir ) ) {
				@mkdir( $scx_dir, 0755, true );
			}
			$scx_file = $scx_dir . '/' . $scx_key . '.html';
			// Si el cache existe y tiene menos de 5 minutos, lo servimos sin más.
			if ( file_exists( $scx_file ) && ( time() - filemtime( $scx_file ) ) < 300 ) {
				readfile( $scx_file );
				echo "\n<!-- SCX_OB_CACHE_SIMPLE_HIT -->";
				exit;
			}
		}

		ob_start();
		echo '<!-- SCX_OB_OPEN_EARLY_OK -->';
	}
}
add_action( 'template_redirect', 'storex_child_ob_open_early', -9999999 );

/* --- B) Reemplazo general sobre el HTML completo --- */
if ( ! function_exists( 'storex_child_aplicar_reemplazos_globales' ) ) {
	function storex_child_aplicar_reemplazos_globales( $html ) {
		$html = str_replace( 'aria-label="Search"', 'aria-label="Buscar"', $html );
		$html = str_replace( "aria-label='Search'", "aria-label='Buscar'", $html );
		$html = str_replace( 'placeholder="Search …"', 'placeholder="Buscar …"', $html );
		$html = str_replace( "placeholder='Search …'", "placeholder='Buscar …'", $html );
		$html = str_replace( '"Search for:"', '"Buscar:"', $html );
		$html = str_replace( "'Search for:'", "'Buscar:'", $html );
		$html = preg_replace( '/(<h\d[^>]*>)\s*About Company\s*(<\/h\d>)/i', '$1Sobre la empresa$2', $html );
		$html = preg_replace( '/(<h\d[^>]*>)\s*Categories\s*(<\/h\d>)/i', '$1Categorías$2', $html );
		$html = preg_replace( '/(<h\d[^>]*>)\s*Archives\s*(<\/h\d>)/i', '$1Archivos$2', $html );
		$html = preg_replace( '/(<h\d[^>]*>)\s*Pages\s*(<\/h\d>)/i', '$1Páginas$2', $html );
		$html = preg_replace( '/(<h\d[^>]*>)\s*Search\s*(<\/h\d>)/i', '$1Buscar$2', $html );
		$html = str_replace( 'Hello world!', '¡Hola mundo!', $html );
		$html = str_replace( 'info@yourstore.com', 'contacto@compuservitec.pe', $html );

		// --- Flag: si no hay que saltarse el footer, reemplazar.
		// En páginas Woo pesadas NO inyectamos las 4 cols vía OB:
		// el fallback WP_Widget debería pintarlo (si no cae el widget vacío).
		$saltar_footer_inject = false;
		if ( defined( 'SCX_DESACTIVAR_OB_COMPLETAMENTE' ) && SCX_DESACTIVAR_OB_COMPLETAMENTE ) {
			$saltar_footer_inject = true;
		}
		if ( ! $saltar_footer_inject
			 && defined( 'SCX_SALTAR_OB_EN_WOOCOMMERCE' ) && SCX_SALTAR_OB_EN_WOOCOMMERCE ) {
			if ( storex_child_es_pagina_woo_pesada() ) {
				$saltar_footer_inject = true;
			}
		}

		if ( ! $saltar_footer_inject ) {
			$_scx_apertura_ws = '<div class="widget-section">';
			$_scx_apertura_fb = '<div class="footer-bottom">';
			$_scx_p1 = stripos( $html, $_scx_apertura_ws );
			$_scx_p2 = false;
			if ( $_scx_p1 !== false ) {
				$_scx_p2 = stripos( $html, $_scx_apertura_fb, $_scx_p1 );
			}

			if ( $_scx_p1 !== false && $_scx_p2 !== false ) {
				$_scx_chunk = substr( $html, $_scx_p1, $_scx_p2 - $_scx_p1 );
				$_scx_pos_lastclose = strripos( $_scx_chunk, '</div>' );
				if ( $_scx_pos_lastclose !== false ) {
					ob_start();
					storex_child_render_footer_cols_html();
					$_scx_cols = ob_get_clean();
					$_scx_inicio = $_scx_p1 + strlen( $_scx_apertura_ws );
					$_scx_fin    = $_scx_p1 + $_scx_pos_lastclose;
					$_scx_reemplazo_interior = "\n"
						. '<div class="scx-foo-grid scx-foo-inject" style="width:100%">'
						. $_scx_cols
						. '</div>'
						. '<!-- SCX_WIDGET_REPLACED_OK -->'
						. "\n            ";
					$html = substr_replace( $html, $_scx_reemplazo_interior, $_scx_inicio, $_scx_fin - $_scx_inicio );
				}
			}
		} else {
			$html = str_replace(
				'</body>',
				'<!-- SCX_SALTO_FOOTER_INJECT_ESTA_PAGINA -->' . "\n</body>",
				$html
			);
		}

		return $html;
	}
}

/* --- C) SHUTDOWN priority 0: capturar buffer, aplicar reemplazos, imprimir. --- */
if ( ! function_exists( 'storex_child_shutdown_final' ) ) {
	function storex_child_shutdown_final() {
		$_scx_nivel = ob_get_level();
		if ( $_scx_nivel <= 0 ) {
			echo '<!-- SCX_SHUTDOWN_RAN_BUT_NO_OPEN_BUFFERS_LEVEL_0 -->';
			return;
		}
		$_scx_html = '';
		while ( ob_get_level() > 0 ) {
			$_scx_bloque = ob_get_clean();
			if ( $_scx_bloque !== false && strlen( $_scx_bloque ) > 0 ) {
				$_scx_html = $_scx_bloque . $_scx_html;
			}
		}
		if ( strlen( trim( $_scx_html ) ) > 0 ) {
			$_scx_html = storex_child_aplicar_reemplazos_globales( $_scx_html );
			// Aplicar las optimizaciones de rendimiento de imágenes y lazy, fetchpriority, minify lazy, cache etc.
			if ( function_exists( 'storex_child_aplicar_optimizaciones_rendimiento_html' ) ) {
				$_scx_html = storex_child_aplicar_optimizaciones_rendimiento_html( $_scx_html );
			}

			// --- HOOK FINAL: pasar todo el HTML por un apply_filters para que
			// otros bloques (traducciones, breadcrumbs, etc.) lo intercepten.
			// (Prioridades recomendadas en add_filter):
			//   10-19: Reemplazos visuales / breadcrumbs / traducciones.
			//   20-29: Cache HTML / minify / post-procesamiento.
			$_scx_html = apply_filters( 'storex_child_final_html_output', $_scx_html );

			$_scx_html = str_replace(
				'</body>',
				'<!-- SCX_SHUTDOWN_OK -->' . "\n</body>",
				$_scx_html
			);

			// Flag PRUEBA 3: guardar cache simple si está activo y no hubo login.
			if (
				defined( 'SCX_OB_CACHE_SIMPLE' )
				&& SCX_OB_CACHE_SIMPLE
				&& ! is_user_logged_in()
			) {
				$scx_key = md5( ( is_ssl() ? 'https-' : 'http-' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
				$scx_dir = WP_CONTENT_DIR . '/cache/scx-ob';
				if ( ! is_dir( $scx_dir ) ) {
					@mkdir( $scx_dir, 0755, true );
				}
				@file_put_contents( $scx_dir . '/' . $scx_key . '.html', $_scx_html );
			}

			echo $_scx_html;
		} else {
			echo '<!-- SCX_SHUTDOWN_RAN_BUT_HTML_EMPTY -->';
		}
	}
}
add_action( 'shutdown', 'storex_child_shutdown_final', 0 );

/* ==========================================================================
   FIX NUCLEAR BANNER FRAJA CELESTE / IMAGEN CORTADA
   (AUTO-INYECTA <img object-fit: cover>)
   --------------------------------------------------------------------------
   El bug que NUNCA se podía matar con CSS:
   Burger Companion en section-slider.php L30-L37 solo mete <figure class="image-layer">
   (la capa <img> que usa object-fit:cover y NUNCA deja franja) SI EL TEMA SE LLAMA
   EXACTAMENTE "StoreX" (==). Como el nuestro es "StoreX - Grupo Compuservitec" (!==),
   cae en el ELSE, solo carga pattern-layer (background: inline) y NUNCA <img>.
   SOLUCIÓN: cuando veamos "<div class="pattern-layer" style="background-image:url(XXX)">"
   en el HTML final (cualquier página), inyectamos DESPUÉS la <figure> + <img>
   con object-fit: cover INLINE (nada puede sobreescribirlo).
   ========================================================================== */
if ( ! function_exists( 'storex_child_inject_banner_image_layer_fix' ) ) :
	function storex_child_inject_banner_image_layer_fix( $html ) {

		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		// Solo inyectar si existe al menos 1 pattern-layer para no gastar CPU en páginas sin banner
		if ( stripos( $html, 'pattern-layer' ) === false ) {
			return $html;
		}

		/* ========================================================================
		   PASO 1 (REGEX MUY LAZA): Captura TODO <div ... class=" ... pattern-layer ... " ... >  ... </div>
		   - Origen class/style NO importa, lo cogemos tal cual (class=...  luego style=... o al revés).
		   - s = DOTALL (. incluye \n), i = case insensitivo, U = UNGREEDY (para en el PRIMER </div>).
		   ======================================================================== */
		$regex_div = '~<div\b[^>]*\bclass\s*=\s*["\'][^"\']*pattern-layer[^"\']*["\'][^>]*>.*?</div>~isU';

		$result = @preg_replace_callback(
			$regex_div,
			function ( $matches_div ) {

				$original_div = (string) $matches_div[0];

				/* ====================================================================
				   PASO 2 (EXTRAER URL MUY LAZA): Dentro del <div ...pattern-layer...>,
				   buscar PRIMERA ocurrencia de: background-image : url(  'URL'  )
				   No importa espacios / comillas simples/dobles / sin comillas → LAZA.
				   ==================================================================== */
				$url  = '';
				$ok   = @preg_match(
					'~background(?:-image)?\s*:\s*[^;]*?url\s*\(\s*[\'"]?([^\'")\s;]+)[\'"]?\s*\)~i',
					$original_div,
					$url_matches
				);
				if ( 1 === $ok && ! empty( $url_matches[1] ) ) {
					$url = trim( (string) $url_matches[1] );
				}

				// CASO SEGURO: no se pudo extraer URL → devolver el DIV ORIGINAL SIN MODIFICAR.
				if ( '' === $url ) {
					return $original_div;
				}

				// Escape seguro: URL limpia sin XSS.
				$url_clean = esc_url( $url );
				if ( '' === $url_clean ) {
					$url_clean = $url;     // fallback: si esc_url lo vacía (por ejemplo URL relativa rara), usamos la original.
				}
				$url_attr = htmlspecialchars( $url_clean, ENT_QUOTES, 'UTF-8' );

				// 🔴 NUCLEAR INLINE FIX (nada lo puede sobreescribir):
				// CONCATENACIÓN . y .= → 0 sprintf → 0 conflictos "100% !important" → 0 ValueError Unknown format specifier.
				$img_fix  = '<figure class="image-layer scx-banner-fix" aria-hidden="true" style="all:initial !important;display:block !important;width:100% !important;height:100% !important;max-width:100% !important;max-height:100% !important;position:absolute !important;top:0 !important;left:0 !important;right:0 !important;bottom:0 !important;margin:0 !important;padding:0 !important;border:0 !important;outline:0 !important;overflow:hidden !important;z-index:1 !important;pointer-events:none !important;background:transparent !important;">';
				$img_fix .=  '<img src="' . $url_attr . '" alt="Banner" style="display:block !important;width:100% !important;height:100% !important;max-width:100% !important;max-height:100% !important;margin:0 !important;padding:0 !important;border:0 !important;outline:0 !important;-o-object-fit:cover !important;object-fit:cover !important;-o-object-position:center center !important;object-position:center center !important;pointer-events:none !important;-ms-interpolation-mode:bicubic !important;" />';
				$img_fix .=  '</figure>';

				// ✅ NO MODIFICAMOS el DIV original (pattern-layer fallback sigue ahí, CSS lo muestra z=0).
				//    AÑADIMOS JUSTO DESPUÉS la capa nuclear <img> cover z=1.
				return $original_div . $img_fix;
			},
			$html
		);

		// Si falló preg_replace → devolver HTML ORIGINAL sin tocar (romper 0).
		if ( null === $result || '' === $result ) {
			return $html;
		}
		return $result;
	}
endif;
// Priority 12 → después de la traducción BANNER_STRUCTURE (9) y las traducciones generales (10)
// ⚠️ PHP FIX (regex inyectar img) DESACTIVADO TEMPORALMENTE: no estaba cazando la URL / no entraba
//    al filter o el inline del pattern-layer tenía orden atributos distinto. Usamos solución
//    100% CSS (background-size: 100% 100%) en pattern-layer que SIEMPRE llena TODO sin franja.
//    Si mañana quieres reactivarlo, solo descomenta la línea add_filter de abajo:
// add_filter( 'storex_child_final_html_output', 'storex_child_inject_banner_image_layer_fix', 12 );

// ⚠️ [ANTERIOR FIX OVERLAY PHP ELIMINADO]
// La función storex_child_inject_overlay_izq_div metía un <div> real por regex, pero el inyector
// a veces colocaba el div FUERA del contenedor position:relative .slide-item.p_relative → el
// overlay (position:absolute) se posicionaba relativo al body ENTERO → solapaba TODO el sitio
// con color #12375c (bug grave reportado 16/08). Solución final: CSS PURO pseudo-elemento sobre
// el padre garantizado rel → section.banner-section div.banner-carousel div.slide-item.p_relative::after
// (nunca abandona el banner, imposible solapar footer/header).
