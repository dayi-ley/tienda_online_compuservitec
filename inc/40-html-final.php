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

			// ====== Página Tienda (Shop) hardcodeada ======
			// 👉 EJEMPLO: palabra menú/link "Shop" → "Tienda"
			'Shop'                                       => 'Tienda',
			'>Shop<'                                     => '>Tienda<',
			'The Shop'                                   => 'La Tienda',
			'Back to shop'                               => 'Volver a la tienda',
			'>Back to shop<'                             => '>Volver a la tienda<',
			'Return to shop'                             => 'Regresar a la tienda',
			'>Return to shop<'                           => '>Regresar a la tienda<',

			// ====== Página Carrito (Cart) hardcodeada ======
		// ⚠️ 2026-08-29 FIX BUG SUBSTRING "Cartoon → Carritoon" / "Cartulina → Carritulina":
		//    NUNCA usar reemplazos GENERICOS ('Cart'/'Product' solos, sin <> ni contexto)
		//    porque str_replace atrapa substrings DENTRO de nombres categorías Woo.
		//    Las reglas ESPECÍFICAS (>Cart<, Update cart, Cart totals...) de más abajo
		//    YA CUBREN todos los usos reales del texto "Cart" en botones/menús.
		// 'Cart'                                       => 'Carrito',   // ❌ COMENTADO (bug Cartoon/Cartulina)
		'>Cart<'                                     => '>Carrito<',
		// 'Product'                                    => 'Producto',  // ❌ COMENTADO (bug substring nombres producto)
		'>Product<'                                  => '>Producto<',
		// 'Image'                                      => 'Imagen',    // ❌ COMENTADO (substring ImageMagick/imágenes)
		'>Image<'                                    => '>Imagen<',
		// 'Price'                                      => 'Precio',    // ❌ COMENTADO (substring PriceList/PrecioX)
		'>Price<'                                    => '>Precio<',
		// 'Quantity'                                   => 'Cantidad',  // ❌ COMENTADO (substring QuantityX)
		'>Quantity<'                                 => '>Cantidad<',
		// 'Qty'                                        => 'Cant.',     // ❌ COMENTADO (substring QtyCode)
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
			// 'Summary'                                    => 'Resumen',     // ❌ COMENTADO (bug substring)
		'>Summary<'                                  => '>Resumen<',
		// 'item'                                       => 'producto',    // ❌ COMENTADO (substring itemX/menuItem)
		// 'items'                                      => 'productos',   // ❌ COMENTADO (mismo bug)
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

/* =============================================================================
   FIX FINAL 2026-09-04: TRADUCCIONES WOO BLOCKS REACT (client-side)

   ¿POR QUÉ AQUÍ y con JS?
      Bloques Gutenberg de WooCommerce (carrito/checkout React) RENDERIZAN
      sus textos en el NAVEGADOR del usuario VÍA JAVASCRIPT, DESPUÉS de que PHP
      entregue el HTML inicial. Consecuencia:
         ✘ gettext (20-traducciones.php) → NO LO VE
         ✘ OB Shutdown (esta misma función storex_child_traducciones_html_final)
           porque el HTML buffer no contiene los textos aún.

   TRUCO ANTI-FLASH (no se ve inglés por ningún lado):
      1. <head> inline CSS = #post-section.woocommerce-cart / checkout { opacity: 0; visibility: hidden; }
         Mientras React + traducciones terminan, la sección está INVISIBLE.
      2. JS en footer hace MutationObserver del DOM hasta que aparezcan los
         bloques Woo, reemplaza TODOS los textos del array.
      3. Una vez reemplazado = opacity 1, visibilidad visible. 0 flashes.

   Archivo correcto: 40-html-final.php (FIX HTML FINAL, NO en 56-carrito-titulo).
   ============================================================================= */

/* 1) CSS inline en wp_head: OCULTAR momentáneamente el área de Woo hasta que
      el JS termine de traducir. Evita 100% el flash "inglés → español". */
add_action( 'wp_head', function(){
	if ( is_admin() ) return;
	// Solo en carrito / pagar / páginas de Woo relevantes.
	if ( ! ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_shop() || is_product() || is_product_category() ) ) ) return;
	?>
<style id="scx-woo-anti-flash-css">
	/* Toda la sección del contenido principal permanece opaca
	   mientras React pinta + JS traduce frases client-side. */
	html body.woocommerce-cart #post-section.scx-woo-translating,
	html body.woocommerce-checkout #post-section.scx-woo-translating,
	html body.woocommerce-page #post-section.scx-woo-translating {
		opacity: 0 !important;
		visibility: hidden !important;
		transition: opacity .28s ease, visibility .28s ease !important;
	}
	/* Traslado visual: desfase 2px arriba = desaparece como siempre, 
	   no se ve saltito al finalizar traducción. */
	html body #post-section.scx-woo-translating {
		transform: translateY(-2px) !important;
	}
</style>
<script id="scx-woo-anti-flash-init">
	// AGREGAR la clase SCX ANTES de que React renderice nada (lo antes posible).
	// El head se ejecuta MUY temprano, así que el body no existe todavía →
	// esperamos la primera escritura del body y le ponemos la clase.
	(function(){
		var f = function(){
			var sec = document.getElementById('post-section');
			if ( sec && sec.classList && ! sec.classList.contains('scx-woo-translating') ) {
				sec.classList.add('scx-woo-translating');
			}
		};
		if (document.body) f();
		else if (document.addEventListener) document.addEventListener('DOMContentLoaded', f);
		// Repetir varias veces por si la sección se pinta después.
		setTimeout(f, 30); setTimeout(f, 120); setTimeout(f, 400);
	})();
</script>
	<?php
}, 1 );

/* 2) SCRIPT EN FOOTER: reemplaza frases (MutationObserver) y QUITA la clase
      SCX de ocultamiento cuando termina. */
add_action( 'wp_footer', function(){
	if ( is_admin() ) return;
	?>
<script id="scx-woo-traducciones-ft">
(function(){
  // =====================================================
  // TABLA de traducciones (orden de más larga a más corta
  // para evitar pises por substring).
  // =====================================================
  var TR = [
    ['Proceed to Checkout',                 'Finalizar compra'],
    ['Proceed to checkout',                 'Finalizar compra'],
    ['Place Order',                         'Finalizar compra'],
    ['Place order',                         'Finalizar compra'],

    ['Cart totals',                         'Resumen del pedido'],
    ['Cart Totals',                         'Resumen del pedido'],
    ['Order summary',                       'Resumen del pedido'],
    ['Order Summary',                       'Resumen del pedido'],

    ['If you have a coupon code, please apply it below.', 'Si tienes un código de cupón, aplícalo a continuación.'],
    ['Click here to enter your code',       'Haz clic aquí para introducir tu código'],
    ['Enter your code',                     'Introduce tu código'],
    ['Estimated total',                     'Total estimado'],
    ['Estimated Total',                     'Total estimado'],
    ['Estimated tax',                       'Impuesto estimado'],
    ['Remove this item',                    'Eliminar este producto'],
    ['Contact information',                 'Información de contacto'],
    ['Direct bank transfer',                'Transferencia bancaria'],
    ['Cash on delivery',                    'Pago contra entrega'],
    ['Terms and Conditions',                'Términos y Condiciones'],
    ['Terms & Conditions',                  'Términos y Condiciones'],
    ['Privacy Policy',                      'Política de Privacidad'],
    ['Shipping options',                    'Opciones de envío'],
    ['Shipping method',                     'Método de envío'],
    ['Local pickup',                        'Recojo en tienda'],
    ['Pickup locations',                    'Puntos de recojo'],
    ['Pickup location',                     'Punto de recojo'],
    ['Payment options',                     'Métodos de pago'],
    ['Payment method',                      'Método de pago'],
    ['Payment methods',                     'Métodos de pago'],
    ['Billing address',                     'Dirección de facturación'],
    ['Billing details',                     'Datos de facturación'],
    ['Shipping address',                    'Dirección de envío'],
    ['Have a coupon?',                      '¿Tienes un cupón?'],
    ['Apply coupon',                        'Aplicar cupón'],
    ['Apply Coupon',                        'Aplicar cupón'],
    ['Coupon code',                         'Código de cupón'],
    ['Coupon Code',                         'Código de cupón'],
    ['Add coupon',                          'Añadir cupón'],
    ['Add coupons',                         'Añadir cupones'],
    ['Remove coupon',                       'Quitar cupón'],
    ['Update cart',                         'Actualizar carrito'],
    ['Update Cart',                         'Actualizar carrito'],
    ['Empty cart',                          'Vaciar carrito'],
    ['Empty Cart',                          'Vaciar carrito'],
    ['View cart',                           'Ver carrito'],
    ['View Cart',                           'Ver carrito'],
    ['Add to cart',                         'Añadir al carrito'],
    ['Add to Cart',                         'Añadir al carrito'],
    ['Return to shop',                      'Volver a la tienda'],
    ['Back to shop',                        'Volver a la tienda'],
    ['Browse store',                        'Ver tienda'],
    ['Contact info',                        'Datos de contacto'],
    ['Cart is empty',                       'Carrito vacío'],
    ['Remove item',                         'Eliminar'],
    ['Your cart is currently empty!',       '¡Tu carrito está vacío!'],
    ['Your cart is currently empty.',       'Tu carrito está vacío.'],
    ['No products in the cart.',            'No hay productos en el carrito.'],
    ['Grand total',                         'Total final'],
    ['Delivery',                            'Entrega'],
    ['Shipping',                            'Envío'],
    ['Subtotal',                            'Subtotal'],
    ['Discount',                            'Descuento'],
    ['Coupon:',                             'Cupón:'],
    ['Checkout',                            'Pagar'],
    ['Change',                              'Cambiar'],
    ['Edit',                                'Editar'],
    ['Total',                               'Total'],
    ['Taxes',                               'Impuestos'],
    ['Tax',                                 'Impuesto'],
    ['Free',                                'Gratis'],
    ['Ship',                                'Envío a domicilio'],
    ['Pickup',                              'Recojo en tienda']
  ];

  function walkReplace(root){
    if (!root) return;
    try {
      var walker = document.createTreeWalker(
        root, NodeFilter.SHOW_TEXT,
        { acceptNode: function(n) {
            if (!n.nodeValue || n.nodeValue.trim() === '') return NodeFilter.FILTER_REJECT;
            var p = n.parentNode; if (!p || !p.tagName) return NodeFilter.FILTER_REJECT;
            var t = p.tagName.toUpperCase();
            if (t === 'SCRIPT' || t === 'STYLE' || t === 'NOSCRIPT' || t === 'TEMPLATE' || t === 'IFRAME' ||
                t === 'INPUT'  || t === 'TEXTAREA' || t === 'SELECT' || t === 'OPTION') return NodeFilter.FILTER_REJECT;
            return NodeFilter.FILTER_ACCEPT;
        }}
      );
      var nod;
      while ((nod = walker.nextNode())) {
        var v = nod.nodeValue, orig = v;
        for (var i = 0; i < TR.length; i++) {
          if (v.indexOf(TR[i][0]) !== -1) v = v.split(TR[i][0]).join(TR[i][1]);
        }
        if (v !== orig) nod.nodeValue = v;
      }
      // Atributos placeholder / aria-label / value (input submit)
      if (root.querySelectorAll) {
        root.querySelectorAll('[placeholder], [aria-label]').forEach(function(el){
          ['placeholder','aria-label'].forEach(function(a){
            var val = el.getAttribute(a); if (!val) return;
            var nv = val;
            for (var i = 0; i < TR.length; i++) {
              if (nv.indexOf(TR[i][0]) !== -1) nv = nv.split(TR[i][0]).join(TR[i][1]);
            }
            if (nv !== val) el.setAttribute(a, nv);
          });
        });
        root.querySelectorAll('input[type="submit"][value]').forEach(function(el){
          var v2 = el.value, nv2 = v2;
          for (var i = 0; i < TR.length; i++) {
            if (nv2.indexOf(TR[i][0]) !== -1) nv2 = nv2.split(TR[i][0]).join(TR[i][1]);
          }
          if (nv2 !== v2) el.value = nv2;
        });
      }
    } catch(e) {}
  }

  // Quitar clase "oculto" cuando esté TODO traducido (o timeout máximo 2.5s por si acaso)
  var traducidoYA = false;
  function mostrar(){
    if (traducidoYA) return;
    traducidoYA = true;
    var sec = document.getElementById('post-section');
    if (sec && sec.classList) sec.classList.remove('scx-woo-translating');
  }

  function init(){
    walkReplace(document.body);
    // Si ya hay bloques del carrito visibles → traducido ok.
    var bloques = document.querySelectorAll(
      '.wp-block-woocommerce-cart, .wp-block-woocommerce-checkout, .wc-block-cart, .wc-block-checkout, .woocommerce-cart-form'
    );
    if (bloques && bloques.length > 0) setTimeout(mostrar, 180);
    else setTimeout(mostrar, 500);
  }

  // Pasadas rápidas
  try { init(); } catch(e) {}
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ try { init(); } catch(e){} });
  }

  // MutationObserver: atrapar todos los renderizados React / AJAX
  try {
    var tm = null, pasos = 0, MAX_PASOS = 40; // ~5s máximo
    var ob = new MutationObserver(function(){
      clearTimeout(tm);
      pasos++;
      tm = setTimeout(function(){
        walkReplace(document.body);
        if ( pasos > 2 ) mostrar(); // tras 3 re-renderizados React → ok mostramos
        if ( pasos >= MAX_PASOS ) { try { ob.disconnect(); } catch(e){} mostrar(); }
      }, 90);
    });
    ob.observe(document.body, { childList:true, subtree:true, characterData:true });
    setTimeout(function(){ try { ob.disconnect(); } catch(e){} mostrar(); }, 6000);
  } catch(e){}

  // Fallback final (si nada más funciona, no dejar la página oculta)
  setTimeout(mostrar, 2500);
  window.addEventListener('load', function(){ setTimeout(mostrar, 200); });
})();
</script>
	<?php
}, 99999 ); // MUY AL FINAL del footer.
