<?php
/**
 * Módulo 56 · Título "Carrito (X productos)" H2 semántico en página /cart/
 * ============================================================
 * 6 formas de insertar el título (Fuerza Bruta):
 *    • 2 hooks clásicos WooCommerce (before_cart / before_cart_table)
 *    • 2 hooks Block Gutenberg Woo (render_block 'woocommerce/cart' / woocommerce/cart-items)
 *    • 1 Filtro al OB del child (storex_child_final_html_output) — reemplazo regex
 *    • 1 JS inline en wp_footer — Fallback: si ninguno de los anteriores pintó el nodo
 *
 * Condición EXACTA del usuario (NO SE MUESTRA si carrito vacío):
 *    if ( ! function_exists( 'WC' ) || WC()->cart->is_empty() ) return;
 * ============================================================
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   FUNCIONES COMUNES
   ============================================================ */
function scx_carrito_titulo_conteo(){
    if ( ! function_exists( 'WC' ) || empty( WC()->cart ) ) return 0;
    return (int) WC()->cart->get_cart_contents_count();
}

/** Devuelve el HTML completo del H2. Si carrito vacío = string vacío. */
function scx_carrito_titulo_html(){
    if ( ! function_exists( 'WC' ) || empty( WC()->cart ) ) return '';
    if ( WC()->cart->is_empty() ) return '';  // Condición del usuario
    $n = scx_carrito_titulo_conteo();
    return sprintf(
        '<%1$s class="scx-carrito-titulo" id="scx-carrito-titulo" data-scx-count="%2$d">' .
            '<span class="scx-carrito-titulo__txt">Carrito</span>' .
            ' <span class="scx-carrito-titulo__sep">(</span>' .
            '<span class="scx-carrito-titulo__count">%2$d</span>' .
            ' <span class="scx-carrito-titulo__lbl">producto%3$s</span>' .
            '<span class="scx-carrito-titulo__sep">)</span>' .
        '</%1$s>',
        'h2',
        $n,
        $n === 1 ? '' : 's'
    );
}

/* ============================================================
   FORMA 1 y 2 · HOOKS CLÁSICOS WOOCOMMERCE (Shortcode [woocommerce_cart])
   ============================================================ */
add_action( 'woocommerce_before_cart', function(){
    echo scx_carrito_titulo_html();
}, 5 );

add_action( 'woocommerce_before_cart_table', function(){
    echo scx_carrito_titulo_html();
}, 5 );

/* ============================================================
   FORMA 3 y 4 · HOOKS BLOCK GUTENBERG WOO (wp:woocommerce/cart)
   ============================================================ */
add_filter( 'render_block_woocommerce/cart', function( $block_content, $block ){
    $h2 = scx_carrito_titulo_html();
    if ( $h2 === '' ) return $block_content;
    if ( strpos( $block_content, 'scx-carrito-titulo' ) !== false ) return $block_content;
    // Inyectar ANTES del <table class="wc-block-cart-items">
    $block_content = preg_replace(
        '/(<table\b[^>]*class="[^"]*wc-block-cart-items[^"]*"[^>]*>)/i',
        $h2 . '$1',
        $block_content,
        1
    );
    // Si no hubo table (carrito lleno), inyectar antes de <div class="wc-block-components-main...">
    if ( strpos( $block_content, 'scx-carrito-titulo' ) === false ) {
        $block_content = preg_replace(
            '/(<div\b[^>]*class="[^"]*wc-block-components-main[^"]*"[^>]*>)/i',
            '$1' . $h2,
            $block_content,
            1
        );
    }
    return $block_content;
}, 20, 2 );

add_filter( 'render_block_woocommerce/cart-items', function( $block_content, $block ){
    $h2 = scx_carrito_titulo_html();
    if ( $h2 === '' ) return $block_content;
    if ( strpos( $block_content, 'scx-carrito-titulo' ) !== false ) return $block_content;
    // Poner el título al PRINCIPIO del bloque items
    return $h2 . $block_content;
}, 20, 2 );

/* ============================================================
   FORMA 5 · OUTPUT BUFFER (filtro HTML final del child)
   ============================================================ */
add_filter( 'storex_child_final_html_output', function( $html ){
    $h2 = scx_carrito_titulo_html();
    if ( $h2 === '' ) return $html;
    // Si ya existe un <h2 class="scx-carrito-titulo"> en el HTML → no duplicar
    if ( strpos( $html, 'scx-carrito-titulo' ) !== false ) return $html;

    // Regex 1: justo después de <div class="...wc-block-components-main...">
    $html = preg_replace(
        '/(<div\b[^>]*class="[^"]*wc-block-components-main[^"]*wp-block-woocommerce-cart-items-block[^"]*"[^>]*>)/is',
        '$1' . $h2,
        $html,
        1
    );
    if ( strpos( $html, 'scx-carrito-titulo' ) !== false ) return $html;

    // Regex 2: antes de <table class="...wc-block-cart-items...">
    $html = preg_replace(
        '/(<table\b[^>]*class="[^"]*wc-block-cart-items[^"]*"[^>]*>)/is',
        $h2 . '$1',
        $html,
        1
    );
    if ( strpos( $html, 'scx-carrito-titulo' ) !== false ) return $html;

    // Regex 3: Fallback antes del <div class="wc-block-cart">
    $html = preg_replace(
        '/(<div\b[^>]*class="[^"]*wc-block-components-sidebar-layout[^"]*"[^>]*>)/is',
        $h2 . '$1',
        $html,
        1
    );
    return $html;
}, 22 );

/* ============================================================
   FORMA 6 · FALLBACK JAVASCRIPT INLINE (footer).
   Si NINGUNA de las 5 anteriores pintó el h2 → JS lo pinta después del DOM ready.
   ============================================================ */
add_action( 'wp_footer', function(){
    if ( ! function_exists( 'WC' ) || empty( WC()->cart ) ) return;
    if ( WC()->cart->is_empty() ) return;   // Condición del usuario: vacío = no pintar
    $n = scx_carrito_titulo_conteo();
    $lbl = $n === 1 ? 'producto' : 'productos';
    ?>
<script id="scx-fallback-carrito-titulo" data-scx-count="<?php echo (int)$n; ?>">
(function(){
  var id = 'scx-carrito-titulo';
  if ( document.getElementById(id) ) return;  // ya lo pintó PHP → no duplicar

  var container = null;
  var targets = [
    document.querySelector('.wc-block-components-main.wc-block-cart__main.wp-block-woocommerce-cart-items-block'),
    document.querySelector('.wc-block-cart__main'),
    document.querySelector('.wp-block-woocommerce-cart-items-block'),
    document.querySelector('.wc-block-components-main')
  ];
  for (var i = 0; i < targets.length; i++) if (targets[i]) { container = targets[i]; break; }
  if (!container) return;

  var h2 = document.createElement('h2');
  h2.id = id;
  h2.className = 'scx-carrito-titulo';
  h2.setAttribute('data-scx-count', '<?php echo (int)$n; ?>');
  h2.innerHTML = '<span class="scx-carrito-titulo__txt">Carrito</span> ' +
                 '<span class="scx-carrito-titulo__sep">(</span>' +
                 '<span class="scx-carrito-titulo__count"><?php echo (int)$n; ?></span> ' +
                 '<span class="scx-carrito-titulo__lbl"><?php echo esc_html($lbl); ?></span>' +
                 '<span class="scx-carrito-titulo__sep">)</span>';

  // Si el primer hijo es <table> → insertar ANTES. Sino prepend.
  var first = container.firstElementChild;
  if ( first && first.tagName && first.tagName.toLowerCase() === 'table' ) {
    container.insertBefore(h2, first);
  } else {
    container.insertBefore(h2, container.firstChild);
  }
})();
</script>

<?php
/* ============================================================
   CUPÓN DESCUENTO: reorganizar COMO ENLACE DISCRETO ANTES del botón Finalizar.
   ✅ Orden final: ... TOTAL → [CUPÓN OCULTO] → [LINK ▸ ¿Tienes cupón?] → [Finalizar Compra]
   - Woo Blocks React renderiza ASÍNCRONO: usamos MutationObserver + reintentos.
   - Oculta el panel/header ORIGINAL de Woo (wc-block-components-panel__button).
   - Sólo NUESTRO link abre/cierra el input cupón, con height transition.
   ============================================================ */
?>
<script id="scx-coupon-toggle-init">
(function(){
  if ( ! document.body || ! document.body.classList ) return;
  if ( ! document.body.classList.contains('woocommerce-cart') ) return;

  /* --------- FUNCIÓN PRINCIPAL: se ejecuta cuando el DOM ya tiene los bloques --------- */
  function scxInitCuponToggle(){
    // Padre FLEX REAL = .wp-block-woocommerce-cart-order-summary-block
    var orderSummaries = document.querySelectorAll('.wp-block-woocommerce-cart-order-summary-block');
    if ( ! orderSummaries || orderSummaries.length === 0 ) return false;

    var todoOK = false;
    orderSummaries.forEach(function(orderSum){
      if ( orderSum.querySelector('.scx-coupon-toggle') ) { todoOK = true; return; }

      // 1) Buscar componente cupón real (Woo Gutenberg Block o totals-coupon)
      var couponBlock = null;
      var couponSelectors = [
        '.wp-block-woocommerce-cart-order-summary-coupon-form-block',
        '.wc-block-components-totals-coupon',
        '.wp-block-woocommerce-cart-coupon',
        '[class*="order-summary-coupon"]',
        '.wc-block-components-panel:has([class*="coupon"] input[type="text"])'
      ];
      for (var s = 0; s < couponSelectors.length; s++) {
        var f = null;
        try { f = orderSum.querySelector(couponSelectors[s]); } catch(e){ f = null; }
        if ( f ) { couponBlock = f; break; }
      }
      if ( ! couponBlock ) {
        // fallback: buscar por input cupón hacia arriba
        var inpAny = orderSum.querySelector('input[name="coupon_code"], input[placeholder*="cupon" i], input[placeholder*="coupon" i]');
        if ( inpAny ) {
          couponBlock = inpAny.closest('[class*="coupon"], [class*="panel"], [class*="Coupon"]') || inpAny.parentElement;
        }
      }

      // 2) Buscar botón FINALIZAR COMPRA (padre wp-block-woocommerce-cart-actions)
      var checkoutBlock = orderSum.querySelector(
        '.wp-block-woocommerce-cart-actions, .wc-block-cart__submit, .wc-block-cart__submit-button-container'
      );
      // Si está FUERA del order-summary (hermano superior), usar el totals-block completo
      var insertParent = orderSum;
      if ( ! checkoutBlock ) {
        var totalsBlock = orderSum.closest('.wp-block-woocommerce-cart-totals-block');
        if ( totalsBlock ) {
          checkoutBlock = totalsBlock.querySelector(
            '.wp-block-woocommerce-cart-actions, .wc-block-cart__submit, .wc-block-cart__submit-button-container'
          );
          if ( checkoutBlock ) insertParent = totalsBlock;
        }
      }

      // 3) Marcar clases para el CSS
      if ( couponBlock ) couponBlock.classList.add('scx-coupon-block');
      orderSum.classList.add('scx-coupon-wrap');
      if ( insertParent !== orderSum && insertParent.classList ) {
        insertParent.classList.add('scx-coupon-wrap');
      }

      // 4) MATAR el panel original de Woo (forzar cerrado + no clickeable)
      try {
        var origBtn = orderSum.querySelector('button.wc-block-components-panel__button, .wc-block-components-panel button[aria-expanded]');
        var origPanel = couponBlock && couponBlock.classList.contains('wc-block-components-panel')
          ? couponBlock
          : orderSum.querySelector('.wc-block-components-panel');
        if ( origPanel ) {
          if ( origPanel.hasAttribute && origPanel.hasAttribute('open') ) {
            try { origPanel.removeAttribute('open'); } catch(e){}
          }
          if ( origPanel.setAttribute ) {
            try { origPanel.setAttribute('data-scx-panel-killed','1'); } catch(e){}
          }
        }
        if ( origBtn ) {
          // Quitar listeners clonando nodo (pierde event listeners adjuntados con addEventListener)
          try {
            origBtn.setAttribute('disabled','disabled');
            origBtn.setAttribute('tabindex','-1');
            origBtn.setAttribute('aria-hidden','true');
            origBtn.style.pointerEvents = 'none';
          } catch(e){}
        }
      } catch(e){}

      // 5) CREAR LINK TOGGLE DISCRETO
      var a = document.createElement('a');
      a.href = 'javascript:void(0)';
      a.setAttribute('role', 'button');
      a.setAttribute('tabindex', '0');
      a.className = 'scx-coupon-toggle';
      a.innerHTML =
        '<span class="scx-coupon-toggle__icon">▸</span>' +
        '<span class="scx-coupon-toggle__label">¿Tienes cupón? Haz clic aquí para aplicarlo</span>';

      var toggleTarget = insertParent;
      function toggleCupon(e){
        if ( e ) e.preventDefault();
        var open = toggleTarget.classList.toggle('scx-coupon-open');
        if ( toggleTarget !== orderSum ) {
          if ( open ) orderSum.classList.add('scx-coupon-open');
          else orderSum.classList.remove('scx-coupon-open');
        }
        var icon = a.querySelector('.scx-coupon-toggle__icon');
        if ( icon ) icon.textContent = open ? '▾' : '▸';
        if ( open && couponBlock ) {
          var inp = couponBlock.querySelector('input[type="text"], input[name="coupon_code"]');
          if ( inp ) {
            setTimeout(function(){ try { inp.focus(); inp.click && inp.click(); } catch(e){} }, 260);
          }
        }
      }
      a.addEventListener('click', toggleCupon);
      a.addEventListener('keydown', function(e){
        if ( e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32 ) {
          e.preventDefault();
          toggleCupon(e);
        }
      });

      // 6) ✅ INSERTAR ANTES DEL BOTÓN FINALIZAR COMPRA (no al final)
      if ( checkoutBlock && checkoutBlock.parentNode ) {
        checkoutBlock.parentNode.insertBefore(a, checkoutBlock);
      } else {
        // fallback: al final del order-summary
        orderSum.appendChild(a);
      }

      todoOK = true;
    });
    return todoOK;
  }

  /* --------- MUTATION OBSERVER: esperar a que React renderice todo --------- */
  var maxTries = 20;
  var tries = 0;
  function tryInit(){
    tries++;
    var ok = false;
    try { ok = scxInitCuponToggle(); } catch(e){ ok = false; }
    if ( ok || tries >= maxTries ) return true;
    return false;
  }

  // Intento 1 inmediato
  if ( tryInit() ) return;

  // Intento 2 en DOMContentLoaded
  if ( document.readyState === 'loading' ) {
    document.addEventListener('DOMContentLoaded', function(){ tryInit(); });
  } else {
    tryInit();
  }

  // Intento 3: MutationObserver observando todo el body hasta encontrar order-summary
  try {
    var mo = new MutationObserver(function(){
      if ( tryInit() ) mo.disconnect();
    });
    mo.observe(document.documentElement || document.body, {
      childList: true,
      subtree: true
    });
    setTimeout(function(){ mo.disconnect(); }, 8000);
  } catch(e){}

  // Intento 4 (final): polling cada 350ms hasta 8s
  var intervalId = setInterval(function(){
    if ( tryInit() || tries >= maxTries ) clearInterval(intervalId);
  }, 350);

  // Intento 5: window.load por si React tarda MUCHO
  window.addEventListener('load', function(){ tryInit(); clearInterval(intervalId); });
})();
</script>
<?php
}, 99999 );
