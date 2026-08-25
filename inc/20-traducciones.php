<?php
// 20-traducciones.php · gettext/ngettext + novedades (backup L351-L766)
if (!defined('ABSPATH')) exit;

/* ==========================================================================
   TRADUCCIONES PERSONALIZADAS WooCommerce / StoreX (sin plugins)
   Intercepta gettext / ngettext ANTES de imprimir frases en inglés y
   las reemplaza por español del Perú.

   ⚠️ MUY IMPORTANTE (para que el admin wp-admin no se ralentice):
   - El hook 'gettext' se ejecuta 20.000+ veces en wp-admin (Dashboard,
     Añadir Plugins, Editar Producto, etc.). Recorrer nuestra tabla $tabla
     20k veces × 50 frases = 1 millón de operaciones → admin MUY lento.
   - SOLUCIÓN: enganchamos los filtros SÓLO si NO estamos en /wp-admin/
     (is_admin() === false). Así:
       ✅ wp-admin: filtros NO cargan → admin rápido.
       ✅ Front-end (Home/Shop/Producto/Categoría): filtros SÍ cargan,
          incluso para el usuario ADMIN logueado (is_admin() solo mira
          la URL /wp-admin/, no el rol del usuario).
   ========================================================================== */
if ( ! is_admin() ) :

if ( ! function_exists( 'storex_child_traducciones_personalizadas' ) ) :
	function storex_child_traducciones_personalizadas( $translated, $text, $domain ) {

		// PROTECCIÓN CRÍTICA ANTI-CÓDIGO: NO traducir NOMBRES DE FUNCIONES JS, camelCase,
		// texto que parezca código inline (paréntesis, llaves, puntos, flechas), o textos
		// sin espacios (nunca son frases legibles). Causa bugs fatales como "initializeEditaror"
		// (R de más) cuando gettext intercepta scripts inline.
		static $re_no_traducir = null;
		if ( null === $re_no_traducir ) {
			$re_no_traducir = '~(
				^[a-zA-Z_][a-zA-Z0-9_]*$						# variable/función simple sin espacios
				|^[a-z]+(?:[A-Z][a-z]+)+[a-zA-Z0-9]*$			# camelCase (initializeEditor)
				|^(?:wp|wc|jQuery|document|window)\.				# wp.xxxx o wc.xxxx o jQuery.xxxx
				|[(){}\[\];=<>+\-*\/&|!:]						# símbolos de código
				|javascript:|function\s|return\s|new\s|const\s
				|initialize\w*Editor							# initializeEditor / initializeEditaror typo ya
				|editarar|editaror
			)~x';
		}
		// Textos muy cortos o que parezcan código → devolver ORIGINAL sin tocar.
		if ( is_string( $text ) ) {
			$sin_espacios = preg_replace( '~\s+~u', '', $text );
			if ( strlen( $text ) < 6 || $sin_espacios === $text || @preg_match( $re_no_traducir, (string)$text ) ) {
				return $text;
			}
			// Corregir typo residual (si ya llegó "initializeEditaror" con R de más)
			if ( stripos( $text, 'initializeEditaror' ) !== false || stripos( $text, 'editarar' ) !== false ) {
				$text = str_ireplace(
					array( 'initializeEditaror', 'initializeeditaror', 'Editarar', 'editarar', 'Editaror', 'editaror' ),
					array( 'initializeEditor',  'initializeEditor',  'Editar',   'editar',   'Editor',   'editor' ),
					$text
				);
				return $text;
			}
		} else {
			return $translated;
		}

		// Sólo aplicamos a textos de WooCommerce, el tema StoreX o textos default (WP Core)
		$dominios_permitidos = array( 'woocommerce', 'storex', 'default', 'burger-companion' );
		if ( ! in_array( $domain, $dominios_permitidos, true ) ) {
			return $translated;
		}

		// --- Tabla de reemplazos: ORIGINAL (inglés) => FINAL (español PE) ---
		// Solo frases que: a) Woo no traduce; b) Burger Companion (mal hecho); c) StoreX hardcodeado.
		// Frases Home, Cart, Search, 404, Footer... ya se traducen en Loco Translate (storex-es_PE.po).
		$tabla = array(

			// ====== Paginado resultados Woo ======
			'Showing %1$d&ndash;%2$d of %3$d results'    => 'Mostrando %1$d&ndash;%2$d de %3$d resultados',
			'Showing all results'                        => 'Mostrando todos los resultados',
			'Showing 1 result'                           => 'Mostrando 1 resultado',

			// ====== Select Ordenar por Woo ======
			'Default sorting'                            => 'Orden predeterminado',
			'Sort by popularity'                         => 'Ordenar por popularidad',
			'Sort by average rating'                     => 'Ordenar por valoración media',
			'Sort by latest'                             => 'Ordenar por más reciente',
			'Sort by price: low to high'                 => 'Ordenar por precio: menor a mayor',
			'Sort by price: high to low'                 => 'Ordenar por precio: mayor a menor',

			// ====== Producto individual ======
			'Awaiting product image'                     => 'Imagen del producto pendiente',
			'Product image'                              => 'Imagen del producto',
			'quantity'                                   => 'Cantidad',
			'Quantity'                                   => 'Cantidad',
			'SKU:'                                       => 'SKU:',
			'Category:'                                  => 'Categoría:',
			'Categories:'                                => 'Categorías:',
			'Tags:'                                      => 'Etiquetas:',
			'Brand:'                                     => 'Marca:',
			'Ask a Question'                             => 'Preguntar por este producto',

			// ====== Stock ======
			'%s in stock'                                => '%s en stock',
			'%1$s in stock (can be backordered)'         => '%1$s en stock (se puede pedir reserva)',
			'Out of stock'                               => 'Agotado',
			'Out of stock?'                              => '¿Agotado?',
			'In stock'                                   => 'En stock',
			'Only %s left in stock'                      => 'Solo quedan %s en stock',
			'Only %s left in stock (can be backordered)' => 'Solo quedan %s en stock (se puede pedir reserva)',

			// ====== Tabs / Fichas producto ======
			'Reviews'                                    => 'Reseñas',
			'Reviews (%d)'                               => 'Reseñas (%d)',
			'Store Policies'                             => 'Políticas de la tienda',
			'Enquiries'                                  => 'Consultas',
			'Description'                                => 'Descripción',
			'Additional information'                     => 'Información adicional',
			'Related products'                           => 'Productos relacionados',

			// ====== Bloque Reseñas ======
			'There are no reviews yet.'                  => 'Aún no hay reseñas.',
			'Be the first to review &ldquo;%s&rdquo;'    => 'Sé el primero en opinar sobre &ldquo;%s&rdquo;',
			'Your rating *'                              => 'Tu valoración *',
			'Your review *'                              => 'Tu reseña *',
			'Submit'                                     => 'Enviar reseña',
			'Review Awaiting Approval'                   => 'Reseña pendiente de aprobación',

			// ====== Add to cart / Cart ======
			'Add to cart'                                => 'Añadir al carrito',
			'View cart'                                  => 'Ver carrito',
			'Proceed to checkout'                        => 'Proceder a pagar',
			'Proceed to Checkout'                        => 'Proceder a pagar',
			'Search products&hellip;'                    => 'Buscar productos&hellip;',
			'Search for:'                                => 'Buscar:',
			'Cart'                                       => 'Carrito',
			'>Cart<'                                     => '>Carrito<',
			'Product'                                    => 'Producto',
			'>Product<'                                  => '>Producto<',
			'Image'                                      => 'Imagen',
			'>Image<'                                    => '>Imagen<',
			'Price'                                      => 'Precio',
			'>Price<'                                    => '>Precio<',
			'Quantity'                                   => 'Cantidad',
			'>Quantity<'                                 => '>Cantidad<',
			'Qty'                                        => 'Cant.',
			'>Qty<'                                      => '>Cant.<',
			'Total'                                      => 'Total',
			'>Total<'                                    => '>Total<',
			'Cart totals'                                => 'Totales del carrito',
			'Cart Totals'                                => 'Totales del carrito',
			'>Cart totals<'                              => '>Totales del carrito<',
			'>Cart Totals<'                              => '>Totales del carrito<',
			'Cart summary'                               => 'Resumen del carrito',
			'Your order'                                 => 'Tu pedido',
			'Subtotal'                                   => 'Subtotal',
			'>Subtotal<'                                 => '>Subtotal<',
			'Shipping'                                   => 'Envío',
			'>Shipping<'                                 => '>Envío<',
			'Shipping options'                           => 'Opciones de envío',
			'Discount'                                   => 'Descuento',
			'Coupon:'                                    => 'Cupón:',
			'Coupon code:'                               => 'Código de cupón:',
			'Add coupons'                                => 'Añadir cupones',
			'>Add coupons<'                              => '>Añadir cupones<',
			'Add coupon'                                 => 'Añadir cupón',
			'Apply coupon'                               => 'Aplicar cupón',
			'>Apply coupon<'                             => '>Aplicar cupón<',
			'Coupon code'                                => 'Código de cupón',
			'>Coupon code<'                              => '>Código de cupón<',
			'Enter your code'                            => 'Introduce tu código',
			'Have a coupon?'                             => '¿Tienes un cupón?',
			'>Have a coupon?'                            => '>¿Tienes un cupón?',
			'Click here to enter your code'              => 'Haz clic aquí para introducir tu código',
			'If you have a coupon code, please apply it below.' => 'Si tienes un código de cupón, aplícalo a continuación.',
			'Update cart'                                => 'Actualizar carrito',
			'>Update cart<'                              => '>Actualizar carrito<',
			'Update Cart'                                => 'Actualizar carrito',
			'>Update Cart<'                              => '>Actualizar carrito<',
			'Empty cart'                                 => 'Vaciar carrito',
			'>Empty cart<'                               => '>Vaciar carrito<',
			'Free'                                       => 'Gratis',
			'>Free<'                                     => '>Gratis<',
			'Estimated total'                            => 'Total estimado',
			'Estimated Total'                            => 'Total estimado',
			'>Estimated total<'                          => '>Total estimado<',
			'Estimated tax'                              => 'Impuesto estimado',
			'Taxes'                                      => 'Impuestos',
			'>Taxes<'                                    => '>Impuestos<',
			'Including %s in taxes'                      => 'Incluye %s en impuestos',
			'Including %1$s in tax and %2$s in %3$s'     => 'Incluye %1$s en impuestos y %2$s en %3$s',
			'Remove this item'                           => 'Eliminar este producto',
			'Remove item'                                => 'Eliminar',
			'You cannot add another &ldquo;%s&rdquo; to your cart.' => 'No puedes añadir otro &ldquo;%s&rdquo; a tu carrito.',
			'&ldquo;%s&rdquo; has been added to your cart.' => '&ldquo;%s&rdquo; se ha añadido a tu carrito.',
			'Your cart is currently empty.'              => 'Tu carrito está vacío.',
			'Your cart is currently empty!'              => '¡Tu carrito está vacío!',
			'No products in the cart.'                   => 'No hay productos en el carrito.',
			'Cart is empty'                              => 'Carrito vacío',
			'Cart Empty'                                 => 'Carrito vacío',
			'New in store'                               => 'Novedades en la tienda',
			'New in Store'                               => 'Novedades en la tienda',
			'New products'                               => 'Productos nuevos',
			'Shop now'                                   => 'Comprar ahora',
			'Browse store'                               => 'Ver tienda',
			'Return to shop'                             => 'Volver a la tienda',

			// ====== Página My Account (Mi Cuenta) ======
			'My Account'                                 => 'Mi Cuenta',
			'Dashboard'                                  => 'Panel',
			'Orders'                                     => 'Pedidos',
			'Downloads'                                  => 'Descargas',
			'Addresses'                                  => 'Direcciones',
			'Inquiries'                                  => 'Consultas',
			'Account details'                            => 'Detalles de la cuenta',
			'Log out'                                    => 'Cerrar sesión',
			'Logout'                                     => 'Cerrar sesión',
			'Login'                                      => 'Iniciar sesión',
			'Log in'                                     => 'Iniciar sesión',
			'Register'                                   => 'Registrarse',
			'Create an account'                            => 'Crear una cuenta',
			'Email address'                              => 'Correo electrónico',
			'Email address *'                            => 'Correo electrónico *',
			'Your email'                                 => 'Tu correo',
			'A link to set a new password will be sent to your email address.' => 'Te enviaremos un enlace a tu correo para que establezcas tu contraseña.',
			'Your account is using a temporary password. We emailed you a link to change your password.' => 'Tu cuenta está usando una contraseña temporal. Te enviamos un enlace a tu correo para que cambies tu contraseña.',
			'Resend'                                     => 'Reenviar',
			'Resend link'                                => 'Reenviar enlace',
			'Lost your password?'                        => '¿Olvidaste tu contraseña?',
			'Lost your password'                         => '¿Olvidaste tu contraseña?',
			'Remember me'                                => 'Recuérdame',
			'Username or email address *'                => 'Usuario o correo electrónico *',
			'Username or email address'                  => 'Usuario o correo electrónico',
			'Password *'                                 => 'Contraseña *',
			'Password'                                   => 'Contraseña',
			'Required'                                   => 'Obligatorio',
			'Hello %s'                                   => 'Hola %s',
			'Hello %1$s (not %1$s? %2$s)'                => 'Hola %1$s (¿no eres %1$s? %2$s)',
			'From your account dashboard you can view your recent orders, manage your shipping and billing addresses, and edit your password and account details.' => 'Desde el panel de tu cuenta puedes ver tus %1$spedidos recientes%2$s, gestionar tus %3$sdirecciones de envío y facturación%4$s, y %5$s editar tu contraseña y los detalles de tu cuenta%6$s.',
			'recent orders'                              => 'pedidos recientes',
			'shipping and billing addresses'             => 'direcciones de envío y facturación',
			'edit your password and account details.'    => 'edita tu contraseña y los detalles de tu cuenta.',
			'We have emailed you a new link to change your password.' => 'Te hemos enviado un nuevo enlace para cambiar tu contraseña.',

			// ====== Página Checkout / Pagar ======
			'Checkout'                                   => 'Pagar',
			'Contact information'                        => 'Información de contacto',
			'Contact info'                               => 'Datos de contacto',
			'Delivery'                                   => 'Entrega',
			'Ship'                                       => 'Envío a domicilio',
			'Shipping'                                   => 'Envío',
			'Shipping method'                            => 'Método de envío',
			'Pickup locations'                           => 'Puntos de recojo',
			'Pickup location'                            => 'Punto de recojo',
			'Pickup'                                     => 'Recojo en tienda',
			'Local pickup'                               => 'Recojo en tienda',
			'Billing address'                            => 'Dirección de facturación',
			'Billing details'                            => 'Datos de facturación',
			'Shipping address'                           => 'Dirección de envío',
			'Edit'                                       => 'Editar',
			'Change'                                     => 'Cambiar',
			'First name'                                 => 'Nombres',
			'First name *'                               => 'Nombres *',
			'Last name'                                  => 'Apellidos',
			'Last name *'                                => 'Apellidos *',
			'Company name'                               => 'Empresa (opcional)',
			'Company'                                    => 'Empresa',
			'Country / Region'                           => 'País / Región',
			'Country / Region *'                         => 'País / Región *',
			'Street address'                             => 'Dirección',
			'Street address *'                           => 'Dirección *',
			'Apartment, suite, unit, etc.'               => 'Depto., piso, interior, etc. (opcional)',
			'+ Add apartment, suite, unit, etc.'         => '+ Añadir depto., piso, interior, etc.',
			'Town / City'                                => 'Ciudad / Distrito',
			'Town / City *'                              => 'Ciudad / Distrito *',
			'State / County'                             => 'Departamento / Provincia',
			'State / County *'                           => 'Departamento / Provincia *',
			'Postcode / ZIP'                             => 'Código postal',
			'Postcode / ZIP *'                           => 'Código postal *',
			'Postcode'                                   => 'Código postal',
			'Phone'                                      => 'Teléfono',
			'Phone *'                                    => 'Teléfono *',
			'Phone (optional)'                           => 'Teléfono (opcional)',
			'Payment options'                            => 'Métodos de pago',
			'Payment method'                             => 'Método de pago',
			'Payment methods'                            => 'Métodos de pago',
			'There was an error registering the payment method with id' => 'Ocurrió un error al registrar el método de pago (id)',
			'Cannot read properties of undefined (reading \'length\')' => 'Por favor, selecciona un método de pago.',
			'Bank transfer / Yape / Plin'                => 'Transferencia bancaria / Yape / Plin',
			'Direct bank transfer'                       => 'Transferencia bancaria',
			'Cash on delivery'                           => 'Pago contra entrega',
			'Add a note to your order'                   => 'Añadir nota a tu pedido',
			'Order notes'                                => 'Notas del pedido',
			'Order notes (optional)'                     => 'Notas del pedido (opcional)',
			'Note'                                       => 'Nota',
			'Terms and Conditions'                       => 'Términos y Condiciones',
			'Terms & Conditions'                         => 'Términos y Condiciones',
			'Privacy Policy'                             => 'Política de Privacidad',
			'By proceeding with your purchase you agree to our Terms and Conditions and Privacy Policy' => 'Al continuar tu compra aceptas nuestros Términos y Condiciones y la Política de Privacidad.',
			'Place Order'                                => 'Finalizar compra',
			'Place order'                                => 'Finalizar compra',
			'Order summary'                              => 'Resumen del pedido',
			'Summary'                                    => 'Resumen',
			'item'                                       => 'producto',
			'items'                                      => 'productos',
			'Product'                                    => 'Producto',
			'Total price for'                            => 'Precio total por',
			'Quantity'                                   => 'Cantidad',
			'Grand total'                                => 'Total final',
			'Tax'                                        => 'Impuesto',
			'Remove coupon'                              => 'Quitar cupón',
			'Coupon:'                                    => 'Cupón:',
			'Code:'                                      => 'Código:',

			// ====== Checkout carrito vacío (mensaje de bloqueo) ======
			'Your cart is currently empty!'              => '¡Tu carrito está vacío!',
			'Your cart is currently empty.'              => 'Tu carrito está vacío.',
			'Checkout is not available whilst your cart is empty—please take a look through our store and come back when you\'re ready to place an order.' => 'No es posible pagar mientras tu carrito esté vacío — por favor, revisa la tienda y vuelve cuando estés listo para finalizar tu compra.',
			'Checkout is not available whilst your cart is empty.' => 'No es posible pagar mientras tu carrito esté vacío.',
		);

		// Búsqueda exacta (más rápida)
		if ( isset( $tabla[ $text ] ) ) {
			return $tabla[ $text ];
		}

		// Casos n of 5 stars (rating)
		if ( preg_match( '/^(\d+) of 5 stars$/', $text, $m ) ) {
			return $m[1] . ' de 5 estrellas';
		}
		if ( preg_match( '/^Rated (\d+) out of 5$/', $text, $m ) ) {
			return 'Valorado con ' . $m[1] . ' de 5';
		}

		return $translated;
	}
endif;
add_filter( 'gettext', 'storex_child_traducciones_personalizadas', 999999, 3 );

if ( ! function_exists( 'storex_child_traducciones_numericas_personalizadas' ) ) :
	function storex_child_traducciones_numericas_personalizadas( $translated, $single, $plural, $number, $domain ) {
		// PROTECCIÓN CRÍTICA ANTI-CÓDIGO (igual que gettext):
		static $re_no_traducir_ng = null;
		if ( null === $re_no_traducir_ng ) {
			$re_no_traducir_ng = '~(
				^[a-zA-Z_][a-zA-Z0-9_]*$
				|^[a-z]+(?:[A-Z][a-z]+)+[a-zA-Z0-9]*$
				|^(?:wp|wc|jQuery|document|window)\.
				|[(){}\[\];=<>+\-*\/&|!:]
				|javascript:|function\s|return\s|new\s|const\s
				|initialize\w*Editor|editarar|editaror
			)~x';
		}
		if ( is_string( $single ) ) {
			$sin_esp = preg_replace( '~\s+~u', '', $single );
			if ( strlen( $single ) < 6 || $sin_esp === $single || @preg_match( $re_no_traducir_ng, (string)$single ) ) {
				return $translated;
			}
			// Corrección typo residual
			if ( stripos( $single, 'initializeEditaror' ) !== false || stripos( $single, 'editarar' ) !== false ) {
				$saneado = str_ireplace(
					array( 'initializeEditaror', 'Editarar', 'editarar', 'Editaror', 'editaror' ),
					array( 'initializeEditor',  'Editar',   'editar',   'Editor',   'editor' ),
					$translated
				);
				return $saneado;
			}
		}

		$dominios_permitidos = array( 'woocommerce', 'storex', 'default', 'burger-companion' );
		if ( ! in_array( $domain, $dominios_permitidos, true ) ) {
			return $translated;
		}

		// Showing X-Y of Z results, singular/plural
		if ( $single === 'Showing 1 result' || $plural === 'Showing %1$d&ndash;%2$d of %3$d results' ) {
			if ( (int)$number <= 1 ) {
				return 'Mostrando 1 resultado';
			}
			return 'Mostrando %1$d&ndash;%2$d de %3$d resultados';
		}

		// %s in stock
		if ( $single === '%s in stock' || $plural === '%s in stock' ) {
			return _n( '%s en stock', '%s en stock', $number, 'storex-child' );
		}

		// Only %s left in stock
		if ( false !== strpos( $single, 'Only %s left in stock' ) ) {
			return _n( 'Solo queda %s en stock', 'Solo quedan %s en stock', $number, 'storex-child' );
		}

		return $translated;
	}
endif;
add_filter( 'ngettext', 'storex_child_traducciones_numericas_personalizadas', 999999, 5 );

endif; // ! is_admin() — fin de bloque traducciones gettext solo front-end.

/* =============================================================================
   7-bis) Aumentar productos Novedades en carrito vacío (Woo shortcode limit=4 → 12)
   ========================================================================== */
add_filter( 'shortcode_atts_products', 'storex_child_aumentar_productos_novedades', 999999, 4 );
add_filter( 'shortcode_atts_recent_products', 'storex_child_aumentar_productos_novedades', 999999, 4 );
add_filter( 'shortcode_atts_sale_products', 'storex_child_aumentar_productos_novedades', 999999, 4 );
add_filter( 'shortcode_atts_best_selling_products', 'storex_child_aumentar_productos_novedades', 999999, 4 );
add_filter( 'shortcode_atts_top_rated_products', 'storex_child_aumentar_productos_novedades', 999999, 4 );
add_filter( 'shortcode_atts_featured_products', 'storex_child_aumentar_productos_novedades', 999999, 4 );
function storex_child_aumentar_productos_novedades( $out, $pairs, $atts, $shortcode ) {
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_page( 'cart' ) || is_shop() ) ) {
		$out['limit']          = isset( $out['limit'] ) ? max( 12, absint( $out['limit'] ) ) : 12;
		$out['per_page']       = isset( $out['per_page'] ) ? max( 12, absint( $out['per_page'] ) ) : 12;
		$out['columns']        = isset( $out['columns'] ) ? max( 5, absint( $out['columns'] ) ) : 6;
		$out['orderby']        = 'date';
		$out['order']          = 'DESC';
	}
	return $out;
}
add_filter( 'woocommerce_shortcode_products_query', 'storex_child_forzar_12_productos_novedades', 999999, 3 );
function storex_child_forzar_12_productos_novedades( $query_args, $atts, $loop_name ) {
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_page( 'cart' ) ) ) {
		$query_args['posts_per_page'] = max( 12, absint( $query_args['posts_per_page'] ?? 12 ) );
		$query_args['nopaging']       = false;
	}
	return $query_args;
}
