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
			// Excepción: términos de UNA sola palabra que SÍ queremos traducir.
			// El guardián de abajo los bloquea por no tener espacios o ser cortos.
			// + frases legítimas que el regex anti-código bloquea por error (contienen 'new ').
			$permitir_exacta = in_array( $text, array(
				'Login', 'Password', 'Register', 'Username', 'Logout',
				'Dashboard', 'Orders', 'Downloads', 'Addresses', 'Resend',
				'Phone', 'optional',
				'Query', 'Actions',
				'Articles', 'Products', 'Coupons', 'Customers', 'Reports', 'Settings',
				'Announcement', 'Knowledgebase', 'Profile',
				'Notifications', 'Delete', 'Type', 'Message', 'From', 'Date', 'All', 'Show', 'entries',
				'New Product', 'New Category', 'New Order', 'New Enquiry', 'New Customer',
				'Customer', 'Processing', 'Completed', 'Cancelled', 'Canceled', 'Refunded', 'Failed', 'Online',
				'You have received an Order <b>#%s</b>',
				'Previous', 'Next', 'System', 'Processing...', 'Send',
				'You do not have any enquiry yet!',
				'%1$sResend%2$s',
				'Apartment, suite, unit, etc. (optional)',
				'Current password (leave blank to leave unchanged)',
				'New password (leave blank to leave unchanged)',
				'Confirm new password',
				'Cart', 'Checkout',
				'Country / Region', 'Country / Region *',
				'Town / City', 'Town / City *',
				'State / County', 'State / County *',
				'Postcode / ZIP', 'Postcode / ZIP *',
				'A link to set a new password will be sent to your email address.',
				'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.',
				'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)',
				'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">shipping and billing addresses</a>, and <a href="%3$s">edit your password and account details</a>.',
				'<strong>Error:</strong> The password field is empty.',
				'<strong>Error:</strong> The password you entered for the email address %s is incorrect.',
				'<strong>Error:</strong> The password you entered for the username %s is incorrect.',
				'<strong>Error:</strong> The username <strong>%s</strong> is not registered on this site. If you are unsure of your username, try your email address instead.',
				'<strong>Error:</strong> Unknown email address. Check again or try your username.',
				'Unknown email address. Check again or try your username.',
			), true );
			if ( ! $permitir_exacta && ( strlen( $text ) < 6 || $sin_espacios === $text || @preg_match( $re_no_traducir, (string)$text ) ) ) {
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
		// + woocommerce-blocks: Woo 8+ usa DOMINIO SEPARADO para bloques Gutenberg React
		//   (result count, ordenar por, shop page title de los bloques, etc.). Sin este dominio
		//   no se traduce Showing 1-16 of 39 ni Shop title del h1.page-title del archive.
		$dominios_permitidos = array( 'woocommerce', 'woocommerce-blocks', 'storex', 'storex-child', 'default', 'burger-companion', 'wc-frontend-manager' );
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
			/* ===== 2026-08-29 Nuevas traducciones manuales ===== */
			// 👉 EJEMPLO: palabra menú/link "Shop" → "Tienda"
			'Shop'                                       => 'Tienda',
			'>Shop<'                                     => '>Tienda<',        // versión segura dentro de links HTML (<a>Shop</a>)
			'The Shop'                                   => 'La Tienda',
			'Back to shop'                               => 'Volver a la tienda',
			'>Back to shop<'                             => '>Volver a la tienda<',
			'Return to shop'                             => 'Regresar a la tienda',
			'>Return to shop<'                           => '>Regresar a la tienda<',
			'Shop page'                                  => 'Página de la tienda',
			'View shop'                                  => 'Ver tienda',
		// ⚠️ 2026-08-29 FIX BUG SUBSTRING: NO usar genéricos solos.
		//   'Cart'    → Cartoon→Carritoon / Cartulina→Carritulina / Carteras / Cartón...
		//   'Product' → Productora/ProductoX dentro de nombres...
		//   'Image'   → Imagina / ImagenMagick...
		// Los >Cart< / >Product< / Add to cart / Cart totals / etc. de líneas anteriores
		// y posteriores YA cubren el 100% de los botones/menús reales.
		// 'Cart'                                       => 'Carrito',  // ❌ BUG
		'>Cart<'                                     => '>Carrito<',
		'Cart'                                       => 'Carrito',
		// 'Product'                                    => 'Producto', // ❌ BUG (también L300)
		'>Product<'                                  => '>Producto<',
		// 'Image'                                      => 'Imagen',   // ❌ BUG
		'>Image<'                                    => '>Imagen<',
		// 'Price'                                      => 'Precio',   // ❌ BUG
		'>Price<'                                    => '>Precio<',
		// 'Quantity'                                   => 'Cantidad', // ❌ BUG (también L89, L302)
		'>Quantity<'                                 => '>Cantidad<',
		// 'Qty'                                        => 'Cant.',    // ❌ BUG
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
			'Shop'                                       => 'Tienda',

			// ====== Página My Account (Mi Cuenta) ======
			'My Account'                                 => 'Mi Cuenta',
			'Dashboard'                                  => 'Panel',
			'Orders'                                     => 'Pedidos',
			'Order Status'                               => 'Estado del pedido',
			'Order status'                               => 'Estado del pedido',
			'Customer'                                   => 'Cliente',
			'Processing'                                 => 'Procesando',
			'Completed'                                  => 'Completado',
			'Cancelled'                                  => 'Cancelado',
			'Canceled'                                   => 'Cancelado',
			'Refunded'                                   => 'Reembolsado',
			'Failed'                                     => 'Fallido',
			'Pending payment'                            => 'Pendiente de pago',
			'Online'                                     => 'En linea',
			'Search By Customer'                         => 'Buscar por cliente',
			'Search By Product'                          => 'Buscar por producto',
			'Search By Billing Email'                    => 'Buscar por correo de facturacion',
			'Downloads'                                  => 'Descargas',
			'Addresses'                                  => 'Direcciones',
			'Query'                                      => 'Consulta',
			'Additional Info'                            => 'Informacion adicional',
			'Actions'                                    => 'Acciones',
			'You do not have any enquiry yet!'           => 'Aun no tienes ninguna consulta!',
			'Inquiries'                                  => 'Consultas',
			'Articles'                                   => 'Artículos',
			'Products'                                   => 'Productos',
			'Coupons'                                    => 'Cupones',
			'Customers'                                  => 'Clientes',
			'Reports'                                    => 'Reportes',
			'Settings'                                   => 'Ajustes',
			'Toggle Menu'                                => 'Mostrar/ocultar menu',
			'Notification Board'                         => 'Tablero de notificaciones',
			'Inquiry Board'                              => 'Tablero de consultas',
			'Announcement'                               => 'Avisos',
			'Knowledgebase'                              => 'Base de conocimiento',
			'Profile'                                    => 'Perfil',
			'Notifications'                              => 'Notificaciones',
			'Notification Dashboard'                     => 'Panel de notificaciones',
			'Only Unread'                                => 'Solo no leidos',
			'Only Read'                                  => 'Solo leidos',
			'All'                                        => 'Todos',
			'Direct Message'                             => 'Mensaje directo',
			'Product Review'                             => 'Resena de producto',
			'Low Stock Product'                          => 'Producto con stock bajo',
			'Withdrawal Requests'                        => 'Solicitudes de retiro',
			'Refund Requests'                            => 'Solicitudes de reembolso',
			'New Product'                                => 'Producto nuevo',
			'New Category'                               => 'Categoria nueva',
			'New Order'                                  => 'Pedido nuevo',
			'New Enquiry'                                => 'Consulta nueva',
			'New Customer'                               => 'Cliente nuevo',
			'Mark Read'                                  => 'Marcar como leido',
			'Delete'                                     => 'Eliminar',
			'Type'                                       => 'Tipo',
			'Message'                                    => 'Mensaje',
			'From'                                       => 'De',
			'Date'                                       => 'Fecha',
			'Show _MENU_ entries'                        => 'Mostrar _MENU_ registros',
			'Showing _START_ to _END_ of _TOTAL_ entries' => 'Mostrando _START_ a _END_ de _TOTAL_ registros',
			'You have received an Order <b>#%s</b>'      => 'Has recibido un pedido <b>#%s</b>',
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
			'%1$sResend%2$s'                             => '%1$sReenviar%2$s',
			'Resend link'                                => 'Reenviar enlace',
			'Lost your password?'                        => '¿Olvidaste tu contraseña?',
			'Lost your password'                         => '¿Olvidaste tu contraseña?',
			'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.' => '¿Olvidaste tu contraseña? Ingresa tu usuario o correo electrónico. Recibirás un enlace para crear una nueva contraseña por correo electrónico.',
			'Reset password'                             => 'Restablecer contraseña',
			'Username or email *'                        => 'Usuario o correo *',
			'Username or email'                          => 'Usuario o correo',
			'Remember me'                                => 'Recuérdame',
			'Username or email address *'                => 'Usuario o correo electrónico *',
			'Username or email address'                  => 'Usuario o correo electrónico',
			'Username *'                                 => 'Usuario *',
			'Username'                                   => 'Usuario',
			'Password *'                                 => 'Contraseña *',
			'Password'                                   => 'Contraseña',
			'The password field is empty.'               => 'El campo de contraseña está vacío.',
			'<strong>Error:</strong> The password field is empty.' => '<strong>Error:</strong> El campo de contraseña está vacío.',
			'The username field is empty.'               => 'El campo de usuario está vacío.',
			'Username is required.'                     => 'El usuario es obligatorio.',
			'The password you entered is incorrect.'     => 'La contraseña que ingresaste es incorrecta.',
			'No order has been made yet.'                => 'Aún no has realizado ningún pedido.',
			'No downloads available yet.'                => 'Aún no hay descargas disponibles.',
			'Browse products'                            => 'Ver productos',
			'Previous'                                   => 'Anterior',
			'Next'                                       => 'Siguiente',
			'System'                                     => 'Sistema',
			'Processing...'                              => 'Procesando...',
			'Send Direct Message'                        => 'Enviar mensaje directo',
			'To Store Vendors'                           => 'A vendedores de la tienda',
			'To Store Admin'                             => 'Al administrador de la tienda',
			'Send'                                       => 'Enviar',
			'The username or email address you entered is not registered on this site.' => 'El usuario o correo electrónico que ingresaste no está registrado en este sitio.',
			'<strong>Error:</strong> The password you entered for the email address %s is incorrect.' => '<strong>Error:</strong> La contraseña que ingresaste para el correo %s es incorrecta.',
			'<strong>Error:</strong> The password you entered for the username %s is incorrect.' => '<strong>Error:</strong> La contraseña que ingresaste para el usuario %s es incorrecta.',
			'<strong>Error:</strong> The username <strong>%s</strong> is not registered on this site. If you are unsure of your username, try your email address instead.' => '<strong>Error:</strong> El usuario <strong>%s</strong> no está registrado en este sitio. Si no estás seguro de tu usuario, intenta con tu correo electrónico.',
			'<strong>Error:</strong> Unknown email address. Check again or try your username.' => '<strong>Error:</strong> Correo electrónico desconocido. Verifícalo o intenta con tu usuario.',
			'Unknown email address. Check again or try your username.' => 'Correo electrónico desconocido. Verifícalo o intenta con tu usuario.',
			'Required'                                   => 'Obligatorio',
			'Hello %s'                                   => 'Hola %s',
			'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)' => 'Hola %1$s (¿no eres %1$s? <a href="%2$s">Cerrar sesión</a>)',
			'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">shipping and billing addresses</a>, and <a href="%3$s">edit your password and account details</a>.' => 'Desde el panel de tu cuenta puedes ver tus <a href="%1$s">pedidos recientes</a>, gestionar tus <a href="%2$s">direcciones de envío y facturación</a>, y <a href="%3$s">editar tu contraseña y los detalles de tu cuenta</a>.',
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
			'The following addresses will be used on the checkout page by default.' => 'Las siguientes direcciones se usarán por defecto en la página de pago.',
			'You have not set up this type of address yet.' => 'Aún no has configurado este tipo de dirección.',
			'Add %s'                                     => 'Agregar %s',
			'Edit %s'                                    => 'Editar %s',
			'Edit'                                       => 'Editar',
			'Change'                                     => 'Cambiar',
			'First name'                                 => 'Nombres',
			'First name *'                               => 'Nombres *',
			'Last name'                                  => 'Apellidos',
			'Last name *'                                => 'Apellidos *',
			'Display name'                               => 'Nombre para mostrar',
			'This will be how your name will be displayed in the account section and in reviews' => 'Asi se mostrara tu nombre en la seccion de tu cuenta y en las opiniones',
			'Password change'                            => 'Cambio de contrasena',
			'Current password (leave blank to leave unchanged)' => 'Contrasena actual (dejar en blanco para no cambiarla)',
			'New password (leave blank to leave unchanged)' => 'Nueva contrasena (dejar en blanco para no cambiarla)',
			'Confirm new password'                       => 'Confirmar nueva contrasena',
			'Save changes'                               => 'Guardar cambios',
			'Company name'                               => 'Empresa (opcional)',
			'Company'                                    => 'Empresa',
			'Country / Region'                           => 'Pais',
			'Country / Region *'                         => 'Pais *',
			'Street address'                             => 'Dirección',
			'Street address *'                           => 'Dirección *',
			'House number and street name'               => 'Nombre de la calle y numero',
			'Apartment, suite, unit, etc.'               => 'Depto., piso, interior, etc. (opcional)',
			'Apartment, suite, unit, etc. (optional)'     => 'Depto., piso, interior, etc. (opcional)',
			'Save address'                               => 'Guardar dirección',
			'+ Add apartment, suite, unit, etc.'         => '+ Añadir depto., piso, interior, etc.',
			'Town / City'                                => 'Provincia & distrito',
			'Town / City *'                              => 'Provincia & distrito *',
			'State / County'                             => 'Departamento/Region',
			'State / County *'                           => 'Departamento/Region *',
			'Postcode / ZIP'                             => 'Código postal (san ignacio=06846)',
			'Postcode / ZIP *'                           => 'Código postal (san ignacio=06846) *',
			'Postcode'                                   => 'Código postal',
			'Phone'                                      => 'Teléfono',
			'Phone *'                                    => 'Teléfono *',
			'Phone (optional)'                           => 'Teléfono (opcional)',
			'optional'                                   => 'opcional',
			'Payment options'                            => 'Métodos de pago',
			'Payment method'                             => 'Método de pago',
			'Payment methods'                            => 'Métodos de pago',
			'There was an error registering the payment method with id' => 'Ocurrió un error al registrar el método de pago (id)',
			'Cannot read properties of undefined (reading \'length\')' => 'Por favor, selecciona un método de pago.',
			'Bank transfer / Yape / Plin'                => 'Transferencia bancaria / Yape / Plin',
			'Direct bank transfer'                       => 'Transferencia bancaria',
			'Cash on delivery'                           => 'Pago contra entrega',
			'Add a note to your order'                   => 'Añadir nota a tu pedido',
			'Notes about your order, e.g. special notes for delivery.' => 'Notas sobre tu pedido, p. ej. notas especiales para la entrega.',
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

			// ====== Página Pedido Recibido (thank-you / order-received) ======
			'Thank you. Your order has been received.'   => 'Gracias. Tu pedido ha sido recibido.',
			'Order number:'                              => 'Número de pedido:',
			'Date:'                                      => 'Fecha:',
			'Email:'                                     => 'Correo:',
			'Total:'                                     => 'Total:',
			'Payment method:'                            => 'Método de pago:',
			'Our bank details'                           => 'Nuestros datos bancarios',
			'Account name'                               => 'Titular de la cuenta',
			'Account number'                             => 'Número de cuenta',
			'Bank name'                                  => 'Nombre del banco',
			'Sort code'                                  => 'Código de sucursal',
			'Order details'                              => 'Detalles del pedido',
			'Note:'                                      => 'Nota:',
			'Actions:'                                   => 'Acciones:',
			'Billing email'                              => 'Correo de facturación',
		// ⚠️ 2026-08-29 FIX BUG SUBSTRING genéricos sin <> (iguales motivos arriba)
		// 'Summary'                                    => 'Resumen',   // ❌ BUG
		// 'item'                                       => 'producto',  // ❌ BUG menuItem / itemCount
		// 'items'                                      => 'productos', // ❌ BUG
		// 'Product'                                    => 'Producto',  // ❌ BUG (duplicado L132)
		'Total price for'                            => 'Precio total por',
		// 'Quantity'                                   => 'Cantidad',  // ❌ BUG (duplicado L89, L138)
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
			// Excepcion: terminos de una sola palabra que SI queremos traducir (Addresses usa _n()).
			$permitir_suelta_ng = in_array( $single, array( 'Address', 'Addresses' ), true );
			if ( ! $permitir_suelta_ng && ( strlen( $single ) < 6 || $sin_esp === $single || @preg_match( $re_no_traducir_ng, (string)$single ) ) ) {
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

		$dominios_permitidos = array( 'woocommerce', 'woocommerce-blocks', 'storex', 'storex-child', 'default', 'burger-companion' );
		if ( ! in_array( $domain, $dominios_permitidos, true ) ) {
			return $translated;
		}

		if ( 'Address' === $single || 'Addresses' === $plural ) {
			return ( (int) $number > 1 ) ? 'Direcciones' : 'Direccion';
		}

		// Showing X-Y of Z results, singular/plural (incluye variantes con HTML span exactas usadas por Woo Blocks)
		if ( $single === 'Showing 1 result' || $single === 'Showing all %d results' || $plural === 'Showing %1$d&ndash;%2$d of %3$d results' || $plural === 'Showing %1$d–%2$d of %3$d results' ) {
			if ( (int)$number <= 1 ) {
				return 'Mostrando 1 resultado';
			}
			return 'Mostrando %1$d&ndash;%2$d de %3$d resultados';
		}
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
   TRADUCCIÓN ESPECÍFICA: TÍTULO PRINCIPAL <h1 class="page-title">
   El título de la página de la Tienda (Shop) NO pasa por gettext, WordPress lo
   lee DIRECTAMENTE de la DB (título del page ID asignado en Woo Settings).
   Por eso 'Shop' => 'Tienda' de la tabla gettext no lo alcanzaba nunca.
   Solución: filtro the_archive_title (is_shop / product_cat / product_tag).
   Funciona TAMBIÉN si el usuario cambia el título en Pages → Editar Tienda.
   ========================================================================== */
if ( ! function_exists( 'storex_child_titulo_tienda_espanol' ) ) :
function storex_child_titulo_tienda_espanol( $title ) {
    if ( ! is_admin() && ( function_exists( 'is_shop' ) || is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) ) {
        $t = trim( (string)$title );
        // Caso 1: Woo normal: <h1>Shop</h1> o Shop:
        if ( $t === 'Shop' || $t === ':Shop' || stripos( $t, 'Shop:' ) === 0 ) {
            return str_ireplace( 'Shop', 'Tienda', $t );
        }
        // Caso 2: Woo con sufijo
        if ( $t === 'The Shop' ) return 'La Tienda';
    }
    return $title;
}
endif;
add_filter( 'the_archive_title', 'storex_child_titulo_tienda_espanol', 999999, 1 );
add_filter( 'woocommerce_page_title',   'storex_child_titulo_tienda_espanol', 999999, 1 );
add_filter( 'wp_title', function( $t ) {
    if ( is_admin() ) return $t;
    if ( function_exists('is_shop') && ( is_shop() || is_product_category() || is_product_tag() ) ) {
        return str_ireplace( array('Shop -', 'Shop |', ' — Shop'), array('Tienda -', 'Tienda |', ' — Tienda'), $t );
    }
    return $t;
}, 999999, 1 );

/* =============================================================================
   TRADUCCIONES ESPECÍFICAS PARA ÍTEMS DEL MENÚ DE NAVEGACIÓN
   (no afectan nombres de categorías, solo <li> y <a> del menú header ul#menu-menu-1)
   Diagnóstico user: menús 'Cart', 'Shop', 'Checkout' del header aparecían sin traducir
   porque WordPress guarda título original hardcodeado y no pasa siempre por gettext.
   Solución: 2 filtros wp_nav_menu_items + wp_get_nav_menu_items.
   REGLA: nunca usar genéricos 'Cart' → siempre >Cart< o >Checkout< (solo
   dentro del HTML del link → no afectan Cartoon, Cartulina, etc.
   ========================================================================== */
if ( ! function_exists( 'storex_child_traducir_menu_nav_items' ) ) :
function storex_child_traducir_menu_nav_items( $items, $args = null ) {
	if ( is_string( $items ) && trim( $items ) === '' ) return $items;
	// 1) Forma string (wp_nav_menu_items): HTML del menú renderizado
	if ( is_string( $items ) ) {
		$buscar = array(
			'>Shop<',
			'>Cart<',
			'>Checkout<',
			'>My account<',
			'>My Account<',
			'>Wishlist',
			'>WishList',
			'>Blog<',
			'>Contact<',
			'>About us<',
			'>About Us<',
			'>Home<'
		);
		$reemplazar = array(
			'>Tienda<',
			'>Carrito<',
			'>Pagar<',
			'>Mi Cuenta<',
			'>Mi Cuenta<',
			'>Lista de deseos',
			'>Lista de deseos',
			'>Blog<',
			'>Contacto<',
			'>Sobre nosotros<',
			'>Sobre nosotros<',
			'>Inicio<'
		);
		return str_replace( $buscar, $reemplazar, $items );
	}
	// 2) Forma objetos (wp_get_nav_menu_items): array de objetos WP_Post -> title/post_title
	if ( is_array( $items ) ) {
		foreach ( $items as $k => $item ) {
			if ( is_object( $item ) && isset( $item->title ) ) {
				$title = trim( (string)$item->title );
				switch ( $title ) {
					case 'Shop':         $item->title = 'Tienda'; break;
					case 'Cart':         $item->title = 'Carrito'; break;
					case 'Checkout':     $item->title = 'Pagar'; break;
					case 'My account':
					case 'My Account':   $item->title = 'Mi Cuenta'; break;
					case 'Wishlist':
					case 'WishList':   $item->title = 'Lista de deseos'; break;
					case 'Blog':         $item->title = 'Blog'; break;
					case 'Contact':      $item->title = 'Contacto'; break;
					case 'About us':
					case 'About Us':   $item->title = 'Sobre nosotros'; break;
					case 'Home':         $item->title = 'Inicio'; break;
				}
			}
			if ( is_object( $item ) && isset( $item->post_title ) && $item->post_title !== $item->title ) {
				$pt = trim( (string)$item->post_title );
				switch ( $pt ) {
					case 'Shop':         $item->post_title = 'Tienda'; break;
					case 'Cart':         $item->post_title = 'Carrito'; break;
					case 'Checkout':     $item->post_title = 'Pagar'; break;
					case 'My account':
					case 'My Account':   $item->post_title = 'Mi Cuenta'; break;
					case 'Wishlist':
					case 'WishList':   $item->post_title = 'Lista de deseos'; break;
					case 'Blog':         $item->post_title = 'Blog'; break;
					case 'Contact':      $item->post_title = 'Contacto'; break;
					case 'About us':
					case 'About Us':   $item->post_title = 'Sobre nosotros'; break;
					case 'Home':         $item->post_title = 'Inicio'; break;
				}
			}
		}
		return $items;
	}
	return $items;
}
endif;
// Filtro 1: menú YA RENDERIZADO (string HTML) — prioridad MUY ALTA 999999
add_filter( 'wp_nav_menu_items', 'storex_child_traducir_menu_nav_items', 999999, 2 );
// Filtro 2: objetos ANTES de renderizar (por si StoreX usa wp_get_nav_menu_items() directo
add_filter( 'wp_get_nav_menu_items', 'storex_child_traducir_menu_nav_items', 999999, 2 );
// Filtro 3: widget_nav_menu (algunos temas usan este)
add_filter( 'widget_nav_menu_args', function( $nav_menu_args, $nav_menu, $args ) {
	if ( isset( $nav_menu_args['fallback_cb'] ) ) { /* noop */ }
	return $nav_menu_args;
}, 1, 3 );
add_filter( 'pre_wp_nav_menu', function( $output, $args ){
	return $output; // por si algún fallback
}, 1, 2 );
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

/* =============================================================================
   TRADUCCIONES JS PARA WOOCOMMERCE BLOCKS (React / cliente)
   Los textos de los bloques de checkout se renderizan en el navegador con React,
   por lo que el filtro gettext de PHP NO los alcanza. La forma nativa de
   WordPress es wp_set_script_translations() con un archivo JSON (formato Jed).
   ========================================================================== */
if ( ! function_exists( 'storex_child_cargar_traducciones_blocks_js' ) ) :
function storex_child_cargar_traducciones_blocks_js() {
	$json_path = get_stylesheet_directory() . '/languages/woocommerce-es_PE-blocks.json';
	if ( ! file_exists( $json_path ) ) return;
	// Registrar el JSON para los handles de scripts que usan los bloques de checkout
	wp_set_script_translations( 'wc-blocks-checkout',        'woocommerce', $json_path );
	wp_set_script_translations( 'wc-blocks-checkout-events', 'woocommerce', $json_path );
	wp_set_script_translations( 'wc-cart-checkout-base',     'woocommerce', $json_path );
	wp_set_script_translations( 'wc-cart-checkout-vendors',  'woocommerce', $json_path );
}
endif;
add_action( 'wp_enqueue_scripts', 'storex_child_cargar_traducciones_blocks_js', 999 );

/* =============================================================================
   TRADUCCION JS (React) — Textos de WooCommerce Blocks
   gettext NO alcanza los textos renderizados por React en el cliente.
   Solución: script inline que reemplaza los textos exactos usando un mapa.
   Recorre todos los nodos de texto dentro del checkout y reemplaza coincidencias
   exactas. Usa MutationObserver para textos que renderizan tarde, y se desconecta
   tras 10s para no consumir recursos.
   ========================================================================== */
add_action('wp_footer', function () {
    if ( ! is_checkout() ) return;
    ?>
<script type="text/javascript">
(function() {
    // Map ORIGINAL (inglés) => TRADUCCIÓN (español PE)
    var T = {
        // Contacto
        'Contact information': 'Información de contacto',
        'Log in': 'Iniciar sesión',
        'You are currently checking out as a guest.': 'Estás pagando como invitado.',
        'Create an account with Compuservitec': 'Crear una cuenta con Compuservitec',
        // Entrega
        'Delivery': 'Entrega',
        'Ship': 'Envío a domicilio',
        'Pickup': 'Recojo en tienda',
        'Pickup locations': 'Puntos de recojo',
        'Pickup location': 'Punto de recojo',
        'Flat rate': 'Tarifa fija',
        'Flat Rate': 'Tarifa fija',
        'No shipping options are available for this address. Please verify the address is correct or try a different address.': 'No hay opciones de envío disponibles para esta dirección. Verifica que la dirección sea correcta o prueba con una diferente.',
        'Shipping options': 'Opciones de envío',
        'Shipping option': 'Opción de envío',
        'Enter a shipping address to view shipping options.': 'Ingresa una dirección de envío para ver las opciones de envío.',
        'Free': '(Gratis)',
        'free': '(Gratis)',
        '(Free)': '(Gratis)',
        // Direcciones
        'Billing address': 'Dirección de facturación',
        'Shipping address': 'Dirección de envío',
        'Use the same address for billing': 'Usar la misma dirección para facturación',
        'Use same address for billing': 'Usar la misma dirección para facturación',
        'Use the shipping address as billing address': 'Usar la dirección de envío como dirección de facturación',
        'Billing and shipping address': 'Dirección de facturación y envío',
        'Separate billing address': 'Dirección de facturación distinta',
        // Pago
        'Payment options': 'Métodos de pago',
        'Payment method': 'Método de pago',
        'Payment methods': 'Métodos de pago',
        // Notas / Términos / Resumen
        'Add a note to your order': 'Añadir nota a tu pedido',
        'Notes about your order, e.g. special notes for delivery.': 'Notas sobre tu pedido, p. ej. notas especiales para la entrega.',
        'Order notes': 'Notas del pedido',
        'Order notes (optional)': 'Notas del pedido (opcional)',
        'Terms and Conditions': 'Términos y Condiciones',
        'Privacy Policy': 'Política de Privacidad',
        'Order summary': 'Resumen del pedido',
        'Add coupons': 'Añadir cupones',
        'Subtotal': 'Subtotal',
        'Discount': 'Descuento',
        'Taxes': 'Impuestos',
        'Grand total': 'Total final',
        'Estimated total': 'Total estimado',
        'Place Order': 'Realizar pedido',
        'Place order': 'Realizar pedido',
        // Errores de validación (formato exacto)
        'Please enter a valid email address': 'Ingresa un correo electrónico válido',
        'Please enter a valid postcode': 'Ingresa un código postal válido',
        'Please enter a valid postcode / ZIP.': 'Ingresa un código postal válido.',
        'Please enter a valid phone number': 'Ingresa un teléfono válido'
    };

    // Reemplazar texto exacto en un nodo (text node)
    function reemplazarEnNodo(node) {
        if (node.nodeType !== 3) return; // solo text nodes
        var txt = node.nodeValue;
        if (txt === null) return;
        var trimmed = txt.trim();
        if (trimmed === '') return;
        if (T.hasOwnProperty(trimmed)) {
            node.nodeValue = T[trimmed];
            return;
        }
        // Fallback: mensajes de validación tipo "Please enter a valid X"
        // (X ya viene traducido del label del campo: ej. "correo electrónico").
        var m = trimmed.match(/^Please enter a valid\s+(.+)$/i);
        if (m) {
            node.nodeValue = 'Ingresa un ' + m[1] + ' válido';
        }
    }

    // Reemplazar placeholders en inputs/textarea
    function reemplazarPlaceholders(root) {
        if (!root) return;
        var inputs = root.querySelectorAll('input, textarea');
        for (var i = 0; i < inputs.length; i++) {
            var ph = inputs[i].placeholder;
            if (ph && T.hasOwnProperty(ph)) {
                inputs[i].placeholder = T[ph];
            }
        }
    }

    // Frase de términos: "By proceeding... agree to our [link] and [link]."
    // Tiene links en medio, así que NO es un solo text node.
    // ⚠️ SIN innerHTML (eso destruía nodos de React → removeChild error).
    // Recorremos SOLO los nodos de texto y reemplazamos ahí:
    //   - nodo del prefijo  → "Al continuar tu compra aceptas nuestros"
    //   - nodo conector "and" → "y"  (solo el que es exactamente 'and')
    function traducirFraseTerminos() {
        var termsBlock = document.querySelector('.wc-block-checkout__terms');
        if (!termsBlock) return;
        var walker = document.createTreeWalker(termsBlock, NodeFilter.SHOW_TEXT, null);
        var node;
        while (node = walker.nextNode()) {
            var t = node.nodeValue;
            if (!t || t.trim() === '') continue;
            var trimmed = t.trim().toLowerCase();
            // 1) Prefijo de la frase
            if (/by proceeding with your purchase you agree to our/i.test(t)) {
                node.nodeValue = t.replace(/By proceeding with your purchase you agree to our/i, 'Al continuar tu compra aceptas nuestros');
                continue;
            }
            // 2) Conector entre los 2 links (nodo que es SOLO 'and')
            if (trimmed === 'and') {
                node.nodeValue = t.replace(/and/i, 'y');
            }
        }
    }

    // Recorrer todos los text nodes de un subárbol
    function recorrerTexto(root) {
        if (!root) return;
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: function(n) {
                // Saltar nodos dentro de script/style
                var parent = n.parentNode;
                if (parent && (parent.tagName === 'SCRIPT' || parent.tagName === 'STYLE')) {
                    return NodeFilter.FILTER_REJECT;
                }
                return NodeFilter.FILTER_ACCEPT;
            }
        });
        var node;
        while (node = walker.nextNode()) {
            reemplazarEnNodo(node);
        }
    }

    function traducirCheckout() {
        var checkout = document.querySelector('.wp-block-woocommerce-checkout') || document.body;
        recorrerTexto(checkout);
        reemplazarPlaceholders(checkout);
        traducirFraseTerminos();
    }

    var scxProcessingLabel = 'Procesando pedido...';
    var scxResetTimer = null;
    var scxPlaceOrderWasClicked = false;

    function getPlaceOrderButton() {
        return document.querySelector('button.wc-block-components-checkout-place-order-button, .wc-block-components-checkout-place-order-button button');
    }

    function hasCheckoutErrors() {
        return !!document.querySelector(
            '.wc-block-components-validation-error, ' +
            '.wc-block-components-notice-banner.is-error, ' +
            '.woocommerce-error'
        );
    }

    function isPlaceOrderProcessing(button) {
        if (!button) return false;
        return !!(
            button.disabled ||
            button.getAttribute('aria-busy') === 'true' ||
            button.classList.contains('is-busy') ||
            button.classList.contains('loading')
        );
    }

    function enablePlaceOrderFeedback() {
        var button = getPlaceOrderButton();
        if (!button) return;

        if (!button.hasAttribute('data-scx-original-aria-label') && button.hasAttribute('aria-label')) {
            button.setAttribute('data-scx-original-aria-label', button.getAttribute('aria-label'));
        }

        button.classList.add('scx-checkout-processing', 'scx-click-guard');
        button.setAttribute('data-scx-processing-label', scxProcessingLabel);
        button.setAttribute('aria-label', scxProcessingLabel);
    }

    function disablePlaceOrderFeedback() {
        var button = getPlaceOrderButton();
        if (!button) return;

        button.classList.remove('scx-checkout-processing', 'scx-click-guard');
        button.removeAttribute('data-scx-processing-label');
        scxPlaceOrderWasClicked = false;

        if (button.hasAttribute('data-scx-original-aria-label')) {
            button.setAttribute('aria-label', button.getAttribute('data-scx-original-aria-label'));
            button.removeAttribute('data-scx-original-aria-label');
        } else {
            button.removeAttribute('aria-label');
        }
    }

    function syncPlaceOrderFeedback() {
        var button = getPlaceOrderButton();

        if (!button) {
            return;
        }

        if (!scxPlaceOrderWasClicked && !button.classList.contains('scx-click-guard')) {
            disablePlaceOrderFeedback();
            return;
        }

        if (isPlaceOrderProcessing(button)) {
            enablePlaceOrderFeedback();
            return;
        }

        if (hasCheckoutErrors()) {
            disablePlaceOrderFeedback();
            return;
        }

        if (button.classList.contains('scx-click-guard')) {
            clearTimeout(scxResetTimer);
            scxResetTimer = setTimeout(function() {
                var currentButton = getPlaceOrderButton();
                if (currentButton && !isPlaceOrderProcessing(currentButton) && !hasCheckoutErrors()) {
                    disablePlaceOrderFeedback();
                }
            }, 1600);
        }
    }

    function bindPostcodeTooltipHover() {
        var wrappers = document.querySelectorAll('fieldset#shipping-fields div#shipping div.wc-block-components-address-form__postcode');
        for (var i = 0; i < wrappers.length; i++) {
            var wrapper = wrappers[i];
            if (wrapper.dataset.scxTooltipBound === '1') continue;
            wrapper.dataset.scxTooltipBound = '1';

            var shippingLabel = wrapper.querySelector('label[for="shipping-postcode"]');
            if (!shippingLabel) continue;

            shippingLabel.addEventListener('mouseenter', function() {
                if (this.parentNode) {
                    this.parentNode.classList.add('scx-postcode-icon-hover');
                }
            });
            shippingLabel.addEventListener('mouseleave', function() {
                if (this.parentNode) {
                    this.parentNode.classList.remove('scx-postcode-icon-hover');
                }
            });

            wrapper.addEventListener('mouseleave', function() {
                this.classList.remove('scx-postcode-icon-hover');
            });
        }
    }

    // Intentar inmediatamente
    traducirCheckout();
    syncPlaceOrderFeedback();
    bindPostcodeTooltipHover();

    // Observar cambios del DOM con debounce para manejar múltiples renders
    // de React SIN bucle infinito. Se desconecta al traducir y se reconecta
    // después de 300ms.
    var observer, debounceTimer;
    function startObserver() {
        observer = new MutationObserver(function() {
            observer.disconnect();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                traducirCheckout();
                syncPlaceOrderFeedback();
                bindPostcodeTooltipHover();
                startObserver(); // reconectar para futuros renders
            }, 300);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
    startObserver();

    // 🔄 Re-traducir tras interacción del usuario: React monta textos NUEVOS
    // al cambiar el método de envío (ej. "Pickup locations", "Billing address").
    // El observer se apaga a los 10s, así que estos listeners cubren esos casos.
    function retraducirConDelay() {
        setTimeout(traducirCheckout, 50);
        setTimeout(traducirCheckout, 250);
        setTimeout(traducirCheckout, 600);
        setTimeout(syncPlaceOrderFeedback, 50);
        setTimeout(syncPlaceOrderFeedback, 250);
        setTimeout(syncPlaceOrderFeedback, 600);
        setTimeout(bindPostcodeTooltipHover, 50);
        setTimeout(bindPostcodeTooltipHover, 250);
        setTimeout(bindPostcodeTooltipHover, 600);
    }
    document.addEventListener('click', retraducirConDelay, true);
    document.addEventListener('change', retraducirConDelay, true);

    document.addEventListener('click', function(event) {
        var target = event.target;
        if (!target || !target.closest) return;

        var button = target.closest('button.wc-block-components-checkout-place-order-button, .wc-block-components-checkout-place-order-button button');
        if (!button) return;

        if (button.classList.contains('scx-click-guard') || button.classList.contains('scx-checkout-processing')) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        scxPlaceOrderWasClicked = true;
        enablePlaceOrderFeedback();

        clearTimeout(scxResetTimer);
        scxResetTimer = setTimeout(function() {
            syncPlaceOrderFeedback();
        }, 150);

        setTimeout(syncPlaceOrderFeedback, 500);
        setTimeout(syncPlaceOrderFeedback, 1200);
        setTimeout(function() {
            var currentButton = getPlaceOrderButton();
            if (currentButton && !isPlaceOrderProcessing(currentButton) && !hasCheckoutErrors()) {
                disablePlaceOrderFeedback();
            }
        }, 8000);
    }, true);

    // Seguridad: desconectar después de 10s
    setTimeout(function() {
        if (observer) observer.disconnect();
        clearTimeout(debounceTimer);
        clearTimeout(scxResetTimer);
    }, 10000);
})();
</script>
    <?php
}, 999 );
