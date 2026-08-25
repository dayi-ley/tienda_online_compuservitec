<?php
// 40-html-final.php · Traducciones HTML final OB shutdown (backup L912-L1377)
if (!defined('ABSPATH')) exit;

/* ==========================================================================
   FIX: Traducciones HTML final (solo frases hardcodeadas StoreX/Burger).

   Se ejecuta en el hook OB shutdown sobre el HTML final completo.
   Aquí SOLO van frases que: a) NO pasaron por gettext; b) NO en Loco.
   El resto (Home, Cart, Search...) ya se resuelve en Loco Translate.
   ========================================================================== */
if ( ! function_exists( 'storex_child_traducciones_html_final' ) ) :
	function storex_child_traducciones_html_final( $html ) {
		// ==========================================================
		// KILL SWITCH ADMIN 100%: NUNCA tocar HTML del panel WP-ADMIN.
		// El typo "initializeEditaror" se produce SOLO en admin
		// por traducciones malas de Loco guardadas en DB / .po files.
		// NOSOTROS NO METEMOS NADA EN EL HTML ADMIN. PUNTO.
		// ==========================================================
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return (string) $html;
		}
		// Kill switch temporal global (borrar esta línea para re-activar todo)
		if ( defined( 'SCX_DESACTIVA_TRADUCCIONES' ) && SCX_DESACTIVA_TRADUCCIONES ) {
			return (string) $html;
		}
		// Seguridad anti-null / no-string: el filtro puede recibir null cuando
		// algún plugin cortocircuita el buffer o corre en admin sin HTML.
		if ( ! is_string( $html ) || strlen( trim( $html ) ) === 0 ) {
			return is_string( $html ) ? $html : '';
		}
		$html = (string) $html;

		// === CORRECCIÓN DIRECTA DE TYPOS (ANTE TODO LO DEMÁS) ===
		// Si la DB metió el typo "initializeEditaror" con R de más en un script,
		// lo re-escribimos aquí al valor correcto para que no rompa el JS admin.
		// Esto corrige inmediatamente el TypeError del editor de plugins/Loco.
		$html = str_ireplace(
			array(
				'initializeEditaror',
				'initializeeditaror',
				'InitializeEditaror',
				'.editarar',
				' editarar ',
				'>editarar<',
				'Editarar',
			),
			array(
				'initializeEditor',
				'initializeEditor',
				'initializeEditor',
				'.editar',
				' editar ',
				'>editar<',
				'Editar',
			),
			(string) $html
		);

		$reemplazos = array(
			// ====== Burger Companion hardcodeado (tabs producto / etiquetas) ======
			'Reviews (0)'                                => 'Reseñas (0)',
			'Enquiries'                                  => 'Consultas',
			'Store Policies'                             => 'Políticas de la tienda',
			'There are no reviews yet.'                  => 'Aún no hay reseñas.',
			'Your rating *'                              => 'Tu valoración *',
			'Your review *'                              => 'Tu reseña *',
			'Related products'                           => 'Productos relacionados',
			'Ask a Question'                             => 'Preguntar por este producto',
			'Brand:'                                     => 'Marca:',
			'Category:'                                  => 'Categoría:',
			'Tags:'                                      => 'Etiquetas:',
			'Awaiting product image'                     => 'Imagen del producto pendiente',

			// ====== Stars rating (hardcodeado sin gettext) ======
			'1 of 5 stars'                               => '1 de 5 estrellas',
			'2 of 5 stars'                               => '2 de 5 estrellas',
			'3 of 5 stars'                               => '3 de 5 estrellas',
			'4 of 5 stars'                               => '4 de 5 estrellas',
			'5 of 5 stars'                               => '5 de 5 estrellas',

			// ====== Ordenar por select (a veces HTML directo) ======
			'>Default sorting<'                          => '>Orden predeterminado<',
			'>Sort by popularity<'                       => '>Ordenar por popularidad<',
			'>Sort by average rating<'                   => '>Ordenar por valoración media<',
			'>Sort by latest<'                           => '>Ordenar por más reciente<',
			'>Sort by price: low to high<'               => '>Ordenar por precio: menor a mayor<',
			'>Sort by price: high to low<'               => '>Ordenar por precio: mayor a menor<',

			// ====== Página Carrito (Cart) hardcodeada ======
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
			'Cart totals'                                => 'Totales del carrito',
			'Cart Totals'                                => 'Totales del carrito',
			'>Cart totals<'                              => '>Totales del carrito<',
			'>Cart Totals<'                              => '>Totales del carrito<',
			'Cart summary'                               => 'Resumen del carrito',
			'>Cart summary<'                             => '>Resumen del carrito<',
			'Your order'                                 => 'Tu pedido',
			'>Your order<'                               => '>Tu pedido<',
			'Subtotal'                                   => 'Subtotal',
			'>Subtotal<'                                 => '>Subtotal<',
			'Shipping'                                   => 'Envío',
			'>Shipping<'                                 => '>Envío<',
			'Shipping options'                           => 'Opciones de envío',
			'>Shipping options<'                         => '>Opciones de envío<',
			'Discount'                                   => 'Descuento',
			'>Discount<'                                 => '>Descuento<',
			'Add coupons'                                => 'Añadir cupones',
			'>Add coupons<'                              => '>Añadir cupones<',
			'Add coupon'                                 => 'Añadir cupón',
			'>Add coupon<'                               => '>Añadir cupón<',
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
			'Including S/'                               => 'Incluye S/',
			' in taxes'                                  => ' en impuestos',
			'Remove this item'                           => 'Eliminar este producto',
			'Remove item'                                => 'Eliminar',
			'Your cart is currently empty.'              => 'Tu carrito está vacío.',
			'Your cart is currently empty!'              => '¡Tu carrito está vacío!',
			'No products in the cart.'                   => 'No hay productos en el carrito.',
			'Cart is empty'                              => 'Carrito vacío',
			'Cart Empty'                                 => 'Carrito vacío',
			'New in store'                               => 'Novedades en la tienda',
			'>New in store<'                             => '>Novedades en la tienda<',
			'New in Store'                               => 'Novedades en la tienda',
			'New products'                               => 'Productos nuevos',
			'Shop now'                                   => 'Comprar ahora',
			'>Shop now<'                                 => '>Comprar ahora<',
			'Browse store'                               => 'Ver tienda',
			'>Browse store<'                             => '>Ver tienda<',
			'Your cart is currently empty!'              => '¡Tu carrito está vacío!',
			'>Your cart is currently empty!<'            => '>¡Tu carrito está vacío!<',
			'Checkout is not available whilst your cart is empty—please take a look through our store and come back when you\'re ready to place an order.' => 'No es posible pagar mientras tu carrito esté vacío — por favor, revisa la tienda y vuelve cuando estés listo para finalizar tu compra.',
			'>Checkout is not available whilst your cart is empty<' => '>No es posible pagar mientras tu carrito esté vacío<',
			'Checkout is not available whilst your cart is empty.' => 'No es posible pagar mientras tu carrito esté vacío.',
			'Return to shop'                             => 'Volver a la tienda',
			'>Return to shop<'                           => '>Volver a la tienda<',
			'Proceed to Checkout'                        => 'Proceder a pagar',
			'>Proceed to Checkout<'                      => '>Proceder a pagar<',

			// ====== Checkout / Pagar (strings exactos HTML) ======
			'Checkout'                                   => 'Pagar',
			'>Checkout<'                                 => '>Pagar<',
			'Contact information'                        => 'Información de contacto',
			'>Contact information<'                      => '>Información de contacto<',
			'Delivery'                                   => 'Entrega',
			'>Delivery<'                                 => '>Entrega<',
			'Ship'                                       => 'Envío a domicilio',
			'>Ship<'                                     => '>Envío a domicilio<',
			'Shipping'                                   => 'Envío',
			'>Shipping<'                                 => '>Envío<',
			'Pickup locations'                           => 'Puntos de recojo',
			'>Pickup locations<'                         => '>Puntos de recojo<',
			'Pickup location'                            => 'Punto de recojo',
			'>Pickup location<'                          => '>Punto de recojo<',
			'Local pickup'                               => 'Recojo en tienda',
			'>Local pickup<'                             => '>Recojo en tienda<',
			'Billing address'                            => 'Dirección de facturación',
			'>Billing address<'                          => '>Dirección de facturación<',
			'Shipping address'                           => 'Dirección de envío',
			'>Shipping address<'                         => '>Dirección de envío<',
			'Edit'                                       => 'Editar',
			'>Edit<'                                     => '>Editar<',
			'Change'                                     => 'Cambiar',
			'>Change<'                                   => '>Cambiar<',
			'First name'                                 => 'Nombres',
			'>First name<'                               => '>Nombres<',
			'Last name'                                  => 'Apellidos',
			'>Last name<'                                => '>Apellidos<',
			'Country / Region'                           => 'País / Región',
			'>Country / Region<'                         => '>País / Región<',
			'Street address'                             => 'Dirección',
			'>Street address<'                           => '>Dirección<',
			'+ Add apartment, suite, unit, etc.'         => '+ Añadir depto., piso, interior, etc.',
			'>+ Add apartment, suite, unit, etc.<'       => '>+ Añadir depto., piso, interior, etc.<',
			'Town / City'                                => 'Ciudad / Distrito',
			'>Town / City<'                              => '>Ciudad / Distrito<',
			'State / County'                             => 'Departamento / Provincia',
			'>State / County<'                           => '>Departamento / Provincia<',
			'Postcode / ZIP'                             => 'Código postal',
			'>Postcode / ZIP<'                           => '>Código postal<',
			'Phone (optional)'                           => 'Teléfono (opcional)',
			'>Phone (optional)<'                         => '>Teléfono (opcional)<',
			'Payment options'                            => 'Métodos de pago',
			'>Payment options<'                          => '>Métodos de pago<',
			'Payment method'                             => 'Método de pago',
			'>Payment method<'                           => '>Método de pago<',
			'Bank transfer / Yape / Plin'                => 'Transferencia bancaria / Yape / Plin',
			'>Bank transfer / Yape / Plin<'              => '>Transferencia bancaria / Yape / Plin<',
			'Direct bank transfer'                       => 'Transferencia bancaria',
			'>Direct bank transfer<'                     => '>Transferencia bancaria<',
			'Cash on delivery'                           => 'Pago contra entrega',
			'>Cash on delivery<'                         => '>Pago contra entrega<',
			'Add a note to your order'                   => 'Añadir nota a tu pedido',
			'>Add a note to your order<'                 => '>Añadir nota a tu pedido<',
			'Order notes'                                => 'Notas del pedido',
			'>Order notes<'                              => '>Notas del pedido<',
			'Terms and Conditions'                       => 'Términos y Condiciones',
			'>Terms and Conditions<'                     => '>Términos y Condiciones<',
			'Terms & Conditions'                         => 'Términos y Condiciones',
			'>Terms & Conditions<'                       => '>Términos y Condiciones<',
			'Privacy Policy'                             => 'Política de Privacidad',
			'>Privacy Policy<'                           => '>Política de Privacidad<',
			'Place Order'                                => 'Finalizar compra',
			'>Place Order<'                              => '>Finalizar compra<',
			'Place order'                                => 'Finalizar compra',
			'>Place order<'                              => '>Finalizar compra<',
			'Order summary'                              => 'Resumen del pedido',
			'>Order summary<'                            => '>Resumen del pedido<',
			'Summary'                                    => 'Resumen',
			'>Summary<'                                  => '>Resumen<',
			'item'                                       => 'producto',
			'items'                                      => 'productos',
			'>item<'                                     => '>producto<',
			'>items<'                                    => '>productos<',
			'Product'                                    => 'Producto',
			'>Product<'                                  => '>Producto<',
			'Total price for'                            => 'Precio total por',
			'>Total price for<'                          => '>Precio total por<',
			'Quantity'                                   => 'Cantidad',
			'>Quantity<'                                 => '>Cantidad<',
			'Grand total'                                => 'Total final',
			'>Grand total<'                              => '>Total final<',
			'Tax'                                        => 'Impuesto',
			'>Tax<'                                      => '>Impuesto<',
			'Remove coupon'                              => 'Quitar cupón',
			'>Remove coupon<'                            => '>Quitar cupón<',

			// ====== Página My Account (Mi Cuenta) hardcodeada StoreX ======
			'My Account'                                 => 'Mi Cuenta',
			'>My Account<'                               => '>Mi Cuenta<',
			'My account'                                 => 'Mi Cuenta',
			'>My account<'                               => '>Mi Cuenta<',
			'Dashboard'                                  => 'Panel',
			'>Dashboard<'                                => '>Panel<',
			'Orders'                                     => 'Pedidos',
			'>Orders<'                                   => '>Pedidos<',
			'Downloads'                                  => 'Descargas',
			'>Downloads<'                                => '>Descargas<',
			'Addresses'                                  => 'Direcciones',
			'>Addresses<'                                => '>Direcciones<',
			'Inquiries'                                  => 'Consultas',
			'>Inquiries<'                                => '>Consultas<',
			'Account details'                            => 'Detalles de la cuenta',
			'>Account details<'                          => '>Detalles de la cuenta<',
			'Log out'                                    => 'Cerrar sesión',
			'>Log out<'                                  => '>Cerrar sesión<',
			'Logout'                                     => 'Cerrar sesión',
			'Login'                                      => 'Iniciar sesión',
			'Log in'                                     => 'Iniciar sesión',
			'>Log in<'                                   => '>Iniciar sesión<',
			'>Login<'                                    => '>Iniciar sesión<',
			'Register'                                   => 'Registrarse',
			'>Register<'                                 => '>Registrarse<',
			'Create an account'                          => 'Crear una cuenta',
			'>Create an account<'                        => '>Crear una cuenta<',
			'Email address'                              => 'Correo electrónico',
			'A link to set a new password will be sent to your email address.' => 'Te enviaremos un enlace a tu correo para que establezcas tu contraseña.',
			'Your account is using a temporary password. We emailed you a link to change your password.' => 'Tu cuenta está usando una contraseña temporal. Te enviamos un enlace a tu correo para que cambies tu contraseña.',
			'>Resend<'                                   => '>Reenviar<',
			'Resend'                                     => 'Reenviar',
			'Lost your password?'                        => '¿Olvidaste tu contraseña?',
			'Lost your password'                         => '¿Olvidaste tu contraseña?',
			'Remember me'                                => 'Recuérdame',
			'Required'                                   => 'Obligatorio',
			'Username or email address'                  => 'Usuario o correo electrónico',
			'Password'                                   => 'Contraseña',
			'(not admin?'                                => '(¿no eres admin?',
		);

		foreach ( $reemplazos as $txt_original => $txt_final ) {
			// Usamos str_replace simple sin mayúsculas/minúsculas para evitar falsear HTML.
			if ( strpos( $html, $txt_original ) !== false ) {
				$html = str_replace( $txt_original, $txt_final, $html );
			}
		}

		// Casos regex: Be the first to review "TITULO"
		$html = preg_replace(
			'/Be the first to review &ldquo;(.+?)&rdquo;/',
			'Sé el primero en opinar sobre &ldquo;$1&rdquo;',
			$html
		);
		$html = preg_replace(
			'/Be the first to review &quot;(.+?)&quot;/',
			'Sé el primero en opinar sobre &ldquo;$1&rdquo;',
			$html
		);

		// Regex My Account: texto dashboard WooCommerce con links (acepta cualquier texto dentro del <a>, traducido o no)
		$html = preg_replace_callback(
			'/From your account dashboard you can view your\s*(<a[^>]*>.*?<\/a>),?\s*manage your\s*(<a[^>]*>.*?<\/a>),?\s*and\s*(<a[^>]*>.*?<\/a>)\./is',
			function( $m ) {
				return 'Desde el panel de tu cuenta puedes ver tus ' . trim( $m[1] ) . ', gestionar tus ' . trim( $m[2] ) . ' y ' . trim( $m[3] ) . '.';
			},
			$html
		);
		// Fallbacks por si la regex no match pero las frases están a medias
		$html = preg_replace( '/From your account dashboard you can view your /iu', 'Desde el panel de tu cuenta puedes ver tus ', $html );
		$html = preg_replace( '/, manage your /iu', ', gestionar tus ', $html );
		$html = preg_replace( '/, and /iu', ', y ', $html );
		$html = preg_replace( '/ and edit your password/u', ' y editar tu contraseña', $html );

		// Regex My Account #1: Hello admin ORIGINAL (sin traducir)
		$html = preg_replace_callback(
			'/Hello\s+<strong>([^<]+)<\/strong>\s*\(\s*not\s+\1\s*\?\s*(<a[^>]*>.*?<\/a>)\s*\)/i',
			function( $m ) {
				return 'Hola <strong>' . $m[1] . '</strong> (¿no eres ' . $m[1] . '? ' . $m[2] . ')';
			},
			$html
		);
		// Regex My Account #2: limpiar DUPLICADO mezclado "(not user ¿no eres user?" (bug residual)
		$html = preg_replace_callback(
			'/\(\s*not\s+([^ ()¿]+)\s+¿no eres\s+\1\s*\?/u',
			function( $m ) {
				return '(¿no eres ' . trim( $m[1] ) . '?';
			},
			$html
		);
		// Regex My Account #3: caso genérico "(not user?" sin formato strong
		$html = preg_replace_callback(
			'/\(\s*not\s+([^ ()?]+)\s*\?/u',
			function( $m ) {
				return '(¿no eres ' . trim( $m[1] ) . '?';
			},
			$html
		);

		// Breadcrumb: Inicio > My account → Inicio > Mi Cuenta (caso minúsculas "account")
		$html = preg_replace(
			'/([>›»]\s*)My account(\s*[<‹«])/i',
			'$1Mi Cuenta$2',
			$html
		);

		// Showing X-Y of Z results (HTML printed directo)
		$html = preg_replace(
			'/Showing\s+(\d+)\s*&ndash;\s*(\d+)\s+of\s+(\d+)\s+results/i',
			'Mostrando $1&ndash;$2 de $3 resultados',
			$html
		);
		$html = preg_replace(
			'/Showing\s+(\d+)\s*\-\s*(\d+)\s+of\s+(\d+)\s+results/i',
			'Mostrando $1&ndash;$2 de $3 resultados',
			$html
		);

		// ====== Regex Checkout: evitar doble "Finalizar compra | Finalizar compra" por str_replace duplicado ======
		$html = preg_replace( '/(Finalizar compra)[\s|\/\-_]+(Finalizar compra)/iu', '$1', $html );
		$html = preg_replace( '/(>Información de contacto<)(?!.*>Información de contacto<.*\1).*?\1/is', '$1', $html );
		$html = preg_replace( '/(>Entrega<)(?!.*>Entrega<.*\1).*?\1/is', '$1', $html );
		$html = preg_replace( '/(>Puntos de recojo<)(?!.*>Puntos de recojo<.*\1).*?\1/is', '$1', $html );
		$html = preg_replace( '/(>Dirección de facturación<)(?!.*>Dirección de facturación<.*\1).*?\1/is', '$1', $html );
		$html = preg_replace( '/(>Métodos de pago<)(?!.*>Métodos de pago<.*\1).*?\1/is', '$1', $html );
		$html = preg_replace( '/(>Resumen del pedido<)(?!.*>Resumen del pedido<.*\1).*?\1/is', '$1', $html );

		// Regex Checkout: "Total price for 1 TIJERA... item:" o "item:" aislado → "Precio total por 1 X producto:"
		$html = preg_replace_callback(
			'/Total price for\s+(\d+)\s+([A-Z0-9ÁÉÍÓÚÜÑáéíóúñ\- ]+)\s+items?:/iu',
			function( $m ) {
				$n = (int)$m[1];
				$word = $n <= 1 ? 'producto' : 'productos';
				return 'Precio total por ' . $n . ' ' . trim( $m[2] ) . ' ' . $word . ':';
			},
			$html
		);
		// Regex Checkout: "11 item", "1 item", "5 items" (sin contexto)
		$html = preg_replace_callback(
			'/\b(\d+)\s+(item|items)\b/i',
			function( $m ) {
				$n = (int)$m[1];
				$word = $n <= 1 ? 'producto' : 'productos';
				return $n . ' ' . $word;
			},
			$html
		);
		// Regex Checkout: error JS feo "There was an error registering... Cannot read properties undefined" → mensaje amigable
		$html = preg_replace(
			'/There was an error registering the payment method with id[^<]*<br\s*\/?>\s*TypeError:\s*Cannot read properties of undefined\s*\(reading \'length\'\)/iu',
			'El método de pago no se pudo seleccionar. Por favor, vuelve a marcar una opción de pago e inténtalo de nuevo.',
			$html
		);
		$html = preg_replace(
			'/TypeError:\s*Cannot read properties of undefined\s*\(reading \'length\'\)/iu',
			'',
			$html
		);

		// =============================================================
		// FIX FINAL (si todo lo demás falla): REGEX de texto plano del NOTICE
		// de "Your cart is currently empty / Checkout is not available..."
		// Esto ya es 100% texto final, NO depende de gettext ni textdomain.
		// =============================================================
		// 1) Título Your cart is currently empty! / .
		$html = preg_replace(
			'/Your\s+cart\s+is\s+currently\s+empty\s*[!\.]?/iu',
			'¡Tu carrito está vacío!',
			$html
		);
		// 2) Texto largo (con em dash —, con espacios, con guión regular -)
		$html = preg_replace(
			'/Checkout\s+is\s+not\s+available\s+whilst\s+your\s+cart\s+is\s+empty\s*[—\-–]\s*please\s+take\s+a\s+look\s+through\s+our\s+store\s+and\s+come\s+back\s+when\s+you\'?re\s+ready\s+to\s+place\s+an\s+order\.?/iu',
			'No es posible pagar mientras tu carrito esté vacío — por favor, revisa la tienda y vuelve cuando estés listo para finalizar tu compra.',
			$html
		);
		// 2b) Versión corta sin "place an order"
		$html = preg_replace(
			'/Checkout\s+is\s+not\s+available\s+whilst\s+your\s+cart\s+is\s+empty\.?/iu',
			'No es posible pagar mientras tu carrito esté vacío.',
			$html
		);
		// 3) Botón / link Browse store
		$html = preg_replace(
			'/>\s*Browse\s+store\s*</iu',
			'>Ver tienda<',
			$html
		);
		// 3b) Variante Browse Store con mayúscula, o <span>Browse store</span>
		$html = preg_replace_callback(
			'/([>\s])\s*Browse\s*store\s*([<\.,])/iu',
			function( $m ) {
				return $m[1] . 'Ver tienda' . $m[2];
			},
			$html
		);
		// 4) Si el div tiene clase wc-block-cart__empty-cart Y hay items en el body de algún lado,
		//    ocultarlo con style="display:none!important" inline (si el bug se sigue reproduciendo).
		$html = preg_replace(
			'/<div\s+class="([^"]*wc-block-cart__empty-cart[^"]*)"([^>]*)>/i',
			'<div class="$1 scx-cart-empty-check$2 data-scx-empty="1" style="display:none!important;visibility:hidden!important;opacity:0!important"',
			$html
		);
		$html = preg_replace(
			'/<div\s+([^>]*class="[^"]*wc-block-components-notice-banner[^"]*is-error[^"]*"[^>]*)(>\s*Your\s+cart\s+is\s+currently\s+empty)/i',
			'<div $1 data-scx-empty-notice="1" style="display:none!important;visibility:hidden!important;opacity:0!important"$2',
			$html
		);

		return $html;
	}
endif;
add_filter( 'storex_child_final_html_output', 'storex_child_traducciones_html_final', 25, 1 );
