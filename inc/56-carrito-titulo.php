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

      // 4.bis) INYECTAR el __content FALTANTE si no existe (INPUT + BOTON APLICAR CUPON)
      //      La prueba del usuario confirmo: CONTENT NULL = capa interior borrada. La recreamos.
      try {
        if ( origPanel && origPanel.querySelector ) {
          var contentHijo = origPanel.querySelector(
            '.wc-block-components-panel__content, .wc-block-components-totals-coupon__content, [class*="panel__content"]'
          );
          var hayInputAntes = origPanel.querySelector('input[type="text"], input[name="coupon_code"]');
          // Solo si NO existe content y NO existe input ya dentro: CREARLO
          if ( ! contentHijo && ! hayInputAntes ) {
            contentHijo = document.createElement('div');
            contentHijo.className = 'wc-block-components-panel__content wc-block-components-totals-coupon__content';
            // Estructura minima Woo Gutenberg: input + boton + clase prefix para que Woo lo lea
            var wrapperFlex = document.createElement('div');
            wrapperFlex.className = 'wc-block-components-totals-coupon__form';
            wrapperFlex.style.display = 'flex';
            wrapperFlex.style.gap = '12px';
            wrapperFlex.style.alignItems = 'stretch';
            wrapperFlex.style.marginTop = '10px';
            wrapperFlex.style.flexWrap = 'wrap';
            wrapperFlex.style.width = '100%';
            wrapperFlex.style.justifyContent = 'space-between';
            var inputWrap = document.createElement('div');
            inputWrap.className = 'wc-block-components-totals-coupon__input';
            inputWrap.style.flex = '1 1 auto';
            inputWrap.style.minWidth = '200px';
            var inpNew = document.createElement('input');
            inpNew.type = 'text';
            inpNew.name = 'coupon_code';
            inpNew.id = 'wc-block-components-totals-coupon__input-' + (Math.random().toString(36).slice(2,8));
            inpNew.className = 'wc-block-components-text-input wc-block-components-totals-coupon__input-field';
            inpNew.placeholder = 'Digite su cupón';
            inpNew.setAttribute('aria-label', 'Digite su cupón');
            inpNew.style.width = '100%';
            inpNew.style.padding = '10px 12px';
            inpNew.style.fontSize = '14px';
            inpNew.style.fontStyle = 'italic';
            inpNew.style.lineHeight = '1.4';
            inpNew.style.border = '1px solid #c8cbd1';
            inpNew.style.borderRadius = '10px';
            inpNew.style.outline = 'none';
            inpNew.style.boxSizing = 'border-box';
            inpNew.style.color = '#1f2937';
            /* Placeholder color: gris transparente.
               Usamos estilos inline fallback y, como los ::placeholder se hace via atributo style directamente no aplica a pseudo,
               pero agregamos CSS en el style.css a parte. Aquí simplemente definimos el color de fondo su texto normal:
            */
            inpNew.style.background = '#f9fafb';
            // Adjuntar CSS para placeholder gray italic via variable color #9aa0a6 pseudo ::placeholder inline via atributo (snippet inline fallback:
            var st = document.createElement('style');
            st.textContent = '#' + inpNew.id + '::placeholder { color: #9aa0a6 !important; font-style: italic !important; opacity: 1 !important; }';
            document.head.appendChild(st);
            inputWrap.appendChild(inpNew);
            var btnWrap = document.createElement('div');
            btnWrap.className = 'wc-block-components-totals-coupon__button-wrapper';
            btnWrap.style.display = 'flex';
            btnWrap.style.alignItems = 'center';
            btnWrap.style.flexShrink = '0';
            btnWrap.style.marginLeft = 'auto';
            var btnNew = document.createElement('button');
            btnNew.type = 'submit';
            btnNew.className = 'wc-block-components-totals-coupon__button wp-element-button wc-block-components-button wp-block-button__link contained';
            btnNew.textContent = 'Aplicar';
            btnNew.setAttribute('aria-label', 'Aplicar cupón de descuento');
            btnNew.style.padding = '7px 14px';
            btnNew.style.fontSize = '12px';
            btnNew.style.fontWeight = '400';
            btnNew.style.borderRadius = '8px';
            btnNew.style.border = '1px solid #dde0e5';
            btnNew.style.cursor = 'pointer';
            btnNew.style.color = '#6b7280';
            btnNew.style.background = '#ffffff';
            btnNew.style.transition = 'all 160ms ease';
            btnNew.style.display = 'inline-block';
            btnNew.style.boxSizing = 'border-box';
            btnNew.style.lineHeight = '1.2';
            btnNew.style.boxShadow = 'none';
            btnNew.style.textDecoration = 'none';
            // Hover MINIMO: solo oscurecer un poco borde y texto, NADA MAS. Sin saltos.
            btnNew.addEventListener('mouseenter', function(){
              btnNew.style.borderColor = '#c5c9cf';
              btnNew.style.color = '#4b5563';
              btnNew.style.background = '#fafbfc';
            });
            btnNew.addEventListener('mouseleave', function(){
              btnNew.style.borderColor = '#dde0e5';
              btnNew.style.color = '#6b7280';
              btnNew.style.background = '#ffffff';
            });
            btnWrap.appendChild(btnNew);
            wrapperFlex.appendChild(inputWrap);
            wrapperFlex.appendChild(btnWrap);
            contentHijo.appendChild(wrapperFlex);
            // METERLO COMO ULTIMO HIJO del panel original
            origPanel.appendChild(contentHijo);
            // 🔥 ENLAZAR submit: al hacer click en "Aplicar cupon" -> buscar el formulario Woo real y submittearlo.
            //    Woo 9.0+ dispara AJAX al detectar el input[name="coupon_code"] submit. Buscamos el form ancestro.
            function aplicarCupon(e){
              if(e && e.preventDefault){ e.preventDefault(); e.stopPropagation(); }
              var codigo = (inpNew.value || '').toString().trim();
              if(!codigo){ try{ inpNew.focus(); }catch(err){} return false; }
              // Buscar el panel padre que contiene el boton nativa (el order-summary)
              var formNativo = document.querySelector(
                '.wp-block-woocommerce-cart-order-summary-block form, .wc-block-cart form, .wp-block-woocommerce-cart form'
              );
              // Estrategia: Woo escucha eventos a nivel document.body sobre cualquier submit relacionado con coupon.
              // Disparamos evento personalizado de Woo: click en .wc-block-components-totals-coupon__button nativo (si hubiera),
              // o sino, reutilizar input y trigger de evento personalizado.
              var btnNativoAplicar = document.querySelector(
                '.wp-block-woocommerce-cart-order-summary-block button.wc-block-components-totals-coupon__button, .wc-block-cart button[aria-label*="cupon" i], .wc-block-cart button:has(input[name="coupon_code"]), .wp-block-woocommerce-cart button[aria-label*="cupon" i]'
              );
              if(btnNativoAplicar){
                // Settear el valor del input NATIVO (si existe uno oculto) y disparar click.
                try{
                  var inpNativo = btnNativoAplicar.closest('form, .wc-block-components-totals-coupon, .wp-block-woocommerce-cart-order-summary-coupon-form-block, .scx-coupon-block').querySelector('input[name="coupon_code"]');
                  if(inpNativo) inpNativo.value = codigo;
                }catch(err){}
                try{ btnNativoAplicar.click(); }catch(err){}
                return false;
              }
              // Fallback: buscar el CHECKOUT INPUT de cupon y trigger submit general (Woo AJAX store API)
              var inpGlobal = document.querySelectorAll('input[name="coupon_code"]');
              var usado = false;
              inpGlobal.forEach(function(inpG){
                if(inpG === inpNew) return;
                try{ inpG.value = codigo; usado = true; }catch(err){}
                // disparar submit de su form ancestro
                var f = inpG.closest('form');
                if(f){ try{ f.submit(); }catch(err){}
                } else {
                  // Intentar trigger click en button submit hermano
                  var p = inpG.parentElement;
                  while(p && p !== document.body){
                    var bt = p.querySelector('button[type="submit"], input[type="submit"]');
                    if(bt){ try{ bt.click(); return; }catch(err){} }
                    p = p.parentElement;
                  }
                }
              });
              if(usado) return false;
              // Ultimo fallback: dispatch del input para que Woo lo lea en el store
              try{
                inpNew.dispatchEvent(new Event('change', {bubbles:true}));
                inpNew.dispatchEvent(new Event('blur', {bubbles:true}));
              }catch(err){}
              return false;
            }
            btnNew.addEventListener('click', aplicarCupon, true);
            inpNew.addEventListener('keydown', function(ev){ if(ev.key === 'Enter'){ ev.preventDefault(); aplicarCupon(ev); } }, true);
          }
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
<script>
// ================================================================
// RESUMEN CARRITO - INYECTAR SUBTOTAL + DESCUENTO (ENTRE TITULO Y TOTAL)
// Datos LEIDOS del store de Woo (nunca inventados). Keys correctas Woo 9+:
//   totals.total_items, totals.total_discount, totals.total_price (minor unit)
// ================================================================
(function(){
  var INTENTOS_MAX = 90;
  var INTENTOS = 0;
  var linesCreated = false;
  var ULTIMA_ACT = 0;
  function $(sel, ctx){ return (ctx||document).querySelector(sel); }
  function $(sel, ctx){ return Array.prototype.slice.call((ctx||document).querySelectorAll(sel)); }
  function fmt(n){
    if(n===null||n===undefined||isNaN(n)){ n=0; }
    var num = Number(parseFloat(n).toFixed(2));
    var s = num.toLocaleString('es-PE', { minimumFractionDigits:2, maximumFractionDigits:2 });
    return 'S/ ' + s;
  }
  // ================= FALLBACK SYNC (DOM). Muestra datos INSTANTANEO sin esperar promesa =================
  function getCartFallbackSync(){
    var out = { itemsCount:0, subTotal:0, discountTotal:0, discountCount:0, total:0, found:false };
    try{
      var rows = $('.wc-block-cart-items__row, table.wc-block-cart-items tbody tr, .wc-block-cart-item');
      if(rows && rows.length){ out.itemsCount = rows.length; out.found = true; }
      var tEl = $('.wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-footer-item .wc-block-components-totals-item__value, .wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-item__value');
      if(tEl){ var raw = (tEl.textContent||tEl.innerText||'').replace(/[^0-9.,\-]/g,''); if(raw){ out.total = parseFloat(raw.replace(',','.'))||0; out.found = true; } }
      // Fallback subtotal = total + approx discount
      if(out.total>0 && out.subTotal===0){ out.subTotal = out.total; }
    }catch(err){}
    return out;
  }
  // ================= STORE ASYNC (Woo 9+ retorna PROMESA). Valores reales. =================
  async function getCartAsync(){
    var out = { itemsCount:0, subTotal:0, discountTotal:0, discountCount:0, taxTotal:0, total:0, found:false };
    try{
      if(window.wp && window.wp.data && typeof window.wp.data.select==='function'){
        var sel = window.wp.data.select('wc/store/cart');
        if(sel && typeof sel.getCartData==='function'){
          var cd;
          try{ cd = await sel.getCartData(); }catch(err){ cd = sel.getCartData(); }
          if(cd){
            out.itemsCount = cd.itemsCount || cd.items_count || 0;
            if(cd.coupons && Array.isArray(cd.coupons)){ out.discountCount = cd.coupons.length; }
            if(cd.totals && typeof cd.totals==='object'){
              var mu = parseInt(cd.totals.currency_minor_unit||2,10);
              var div = Math.pow(10, mu);
              // Keys REALES Woo 9+ (confirmadas por user):
              if(cd.totals.total_items !== undefined){ out.subTotal = parseFloat(cd.totals.total_items)/div; }
              else if(cd.totals.subtotal !== undefined){ out.subTotal = parseFloat(cd.totals.subtotal)/div; }
              if(cd.totals.total_discount !== undefined){ out.discountTotal = parseFloat(cd.totals.total_discount)/div; }
              else if(cd.totals.discount_total !== undefined){ out.discountTotal = parseFloat(cd.totals.discount_total)/div; }
              if(cd.totals.total_tax !== undefined){ out.taxTotal = parseFloat(cd.totals.total_tax)/div; }
              if(cd.totals.total_price !== undefined){ out.total = parseFloat(cd.totals.total_price)/div; }
              else if(cd.totals.total !== undefined){ out.total = parseFloat(cd.totals.total)/div; }
              out.found = true;
            }
          }
        }
      }
    }catch(err){}
    return out;
  }
  // ================= FIND ANCHOR INSERT =================
  function findAnchor(resumen){
    // Total final siempre = HIJO [3] wc-block-components-totals-wrapper
    // Insertar lineas PRODUCTOS/DESCUENTOS ANTES de ese hijo (justo encima del Total).
    var totalWrap = null;
    var hijosTotalsWrapper = [];
    for(var i=0;i<resumen.children.length;i++){
      var c = resumen.children[i];
      var isCoupon = c.classList && (c.classList.contains('scx-coupon-block') || c.classList.contains('wp-block-woocommerce-cart-order-summary-coupon-form-block'));
      var isShipping = c.classList && c.classList.contains('wp-block-woocommerce-cart-order-summary-totals-block');
      if(isCoupon || isShipping){ continue; }
      if(c.classList && c.classList.contains('wc-block-components-totals-wrapper')){
        hijosTotalsWrapper.push(c);
      }
      if(c.querySelector && c.querySelector('.wc-block-components-totals-footer-item')){
        totalWrap = c;
        break;
      }
    }
    if(!totalWrap){
      // El ultimo hijo de tipo wc-block-components-totals-wrapper (sin cupon / shipping) = Total final
      if(hijosTotalsWrapper.length > 0){ totalWrap = hijosTotalsWrapper[hijosTotalsWrapper.length - 1]; }
      else { totalWrap = resumen.lastElementChild; }
    }
    var anchorRef = totalWrap;
    return { totalWrap:totalWrap, anchorRef:anchorRef };
  }
  // ================= RENDER (crear lineas + actualizar valores) =================
  function render(data){
    try{
      var resumen = $('.wp-block-woocommerce-cart-order-summary-block, .wp-block-woocommerce-cart-order-summary-block.scx-coupon-wrap');
      if(!resumen){ return false; }
      if(!data){ data = getCartFallbackSync(); }
      if(!linesCreated){
        var anc = findAnchor(resumen);
        // 1. Productos
        var idP = 'scx-rl-productos';
        var p = document.getElementById(idP);
        if(!p){
          p = document.createElement('div');
          p.id = idP; p.className='wc-block-components-totals-item wc-block-components-totals-subtotal-item scx-resumen-linea';
          p.style.cssText = 'display:flex;flex-direction:row;align-items:center;justify-content:space-between;gap:12px;margin:8px 0 6px 0;padding:4px 0 10px 0;width:100%;border-bottom:1px dashed #e3e6ec;';
          p.innerHTML = '<span class="wc-block-components-totals-item__label scx-rl-label" style="font-size:14px;color:#374151;font-weight:400;flex:1 1 auto;"></span><span class="wc-block-components-totals-item__value scx-rl-value" style="font-size:14px;color:#1f2937;font-weight:500;white-space:nowrap;flex:0 0 auto;"></span>';
          try{ resumen.insertBefore(p, anc.anchorRef); }catch(err){ try{ resumen.insertBefore(p, anc.totalWrap); }catch(err2){ resumen.appendChild(p); } }
        }
        // 2. Descuentos
        var idD = 'scx-rl-descuentos';
        var d = document.getElementById(idD);
        if(!d){
          d = document.createElement('div');
          d.id = idD; d.className='wc-block-components-totals-item wc-block-components-totals-discount-item scx-resumen-linea';
          d.style.cssText = 'display:none;flex-direction:row;align-items:center;justify-content:space-between;gap:12px;margin:4px 0 8px 0;padding:4px 0 8px 0;width:100%;border-bottom:1px dashed #e3e6ec;';
          d.innerHTML = '<span class="wc-block-components-totals-item__label scx-rl-label" style="font-size:14px;color:#b91c1c;font-weight:400;flex:1 1 auto;"></span><span class="wc-block-components-totals-item__value scx-rl-value" style="font-size:14px;color:#b91c1c;font-weight:500;white-space:nowrap;flex:0 0 auto;"></span>';
          try{ if(p && p.nextElementSibling){ resumen.insertBefore(d, p.nextElementSibling); } else { resumen.insertBefore(d, anc.totalWrap); } }catch(err){ resumen.appendChild(d); }
        }
        linesCreated = true;
      }
      var pEl = document.getElementById('scx-rl-productos');
      var dEl = document.getElementById('scx-rl-descuentos');
      if(pEl){
        var pl = pEl.querySelector('.scx-rl-label'); if(pl){ pl.textContent = 'Productos (' + (data.itemsCount||0) + ')'; }
        var pv = pEl.querySelector('.scx-rl-value'); if(pv){ var st = (Number(data.subTotal)>0) ? data.subTotal : (Number(data.total||0)+Number(data.discountTotal||0)); pv.textContent = fmt(st); }
      }
      if(dEl){
        var hayD = Number(data.discountTotal) > 0.001;
        dEl.style.display = hayD ? 'flex' : 'none';
        if(hayD){
          var dl = dEl.querySelector('.scx-rl-label'); if(dl){ dl.textContent = 'Descuentos (' + (data.discountCount||1) + ')'; }
          var dv = dEl.querySelector('.scx-rl-value'); if(dv){ dv.textContent = '- ' + fmt(data.discountTotal); }
        }
      }
      // Label Total -> "Total:"
      var tl = $('.wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-footer-item .wc-block-components-totals-item__label, .wc-block-components-totals-item.wc-block-components-totals-footer-item .wc-block-components-totals-item__label');
      if(tl){ var tt=(tl.innerText||tl.textContent||'').trim(); if(tt.toLowerCase().indexOf('total')===0){ tl.textContent = 'Total:'; } }
      // Quitar texto "Including taxes"
      $('.wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-footer-item .wc-block-components-totals-item__description, .wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-item[class*="footer"] [class*="description"]').forEach(function(el){
        var tx=(el.innerText||el.textContent||'').toString().toLowerCase(); if(tx.indexOf('tax')>=0){ try{ el.remove(); }catch(err){ el.style.display='none'; } }
      });
      return true;
    }catch(err){ return false; }
  }
  // Async fetch + update
  async function fetchAct(){
    try{ var d = await getCartAsync(); render(d); ULTIMA_ACT = Date.now(); }catch(err){}
  }
  // Polling crear lineas (SIN LIMITE de intentos: NUNCA se detiene hasta crear lineas).
  // Fix bug anterior: INTENTOS_MAX=90 (10.8s) terminaba ANTES de que React pintara items (rows:2).
  var itv = setInterval(function(){
    INTENTOS++;
    var f = getCartFallbackSync();
    if(f.itemsCount>0 && render(f)){
      clearInterval(itv);
      // Disparar async real
      setTimeout(fetchAct, 300);
      // Updates: wp.data subscribe + 2.5s polling
      try{ if(window.wp && window.wp.data && typeof window.wp.data.subscribe==='function'){ window.wp.data.subscribe(function(){ if(Date.now()-ULTIMA_ACT>900){ setTimeout(fetchAct,150); } }); } }catch(err){}
      setInterval(fetchAct, 2500);
      window.addEventListener('load', function(){ setTimeout(fetchAct, 500); });
    }
  }, 120);
  // Primer intento rapido
  setTimeout(function(){ var f = getCartFallbackSync(); if(f.itemsCount>0){ render(f); fetchAct(); } }, 300);
  window.addEventListener('load', function(){ setTimeout(function(){ var f = getCartFallbackSync(); if(f.itemsCount>0){ render(f); fetchAct(); } }, 400); });
})();
</script>
<script>
// SCRIPT RESUMEN CARRITO - FORZADO SIMPLE (sin limites, sin condiciones raras)
// Inserta lineas Productos / Descuentos. Corre al final, sin conflictos con script anterior.
(function(){
  function $(s){ return document.querySelector(s); }
  function fmt(n){
    if(n===null||n===undefined||isNaN(n)) n=0;
    n = Number(parseFloat(n).toFixed(2));
    return 'S/ ' + n.toLocaleString('es-PE', { minimumFractionDigits:2, maximumFractionDigits:2 });
  }
  // Insertar lineas directo al DOM. Forzar ORDEN TOTAL con order !important en inline setProperty para GANAR a TODO CSS.
  function insertar(){
    var resumen = $('.wp-block-woocommerce-cart-order-summary-block, .wp-block-woocommerce-cart-order-summary-block.scx-coupon-wrap');
    if(!resumen) return false;
    // ==========================================
    // FORZAR ORDER A TODOS LOS HIJOS (SIN CONDICIONES RARAS)
    // Order numérico (menor = más arriba):
    // 10 = TITULO "Resumen del pedido"
    // 20 = Cupon (input + btn Aplicar / oculto por defecto)
    // 30 = Shipping oculto (wp-block-woocommerce-cart-order-summary-totals-block)
    // 70 = Productos (scx-rl-productos)
    // 75 = Descuentos (scx-rl-descuentos)
    // 99 = TOTAL FINAL (wc-block-components-totals-wrapper SIN ser cupon)
    // ==========================================
    for(var i=0;i<resumen.children.length;i++){
      var ch = resumen.children[i];
      if(!ch) continue;
      var ord = 50;
      var isTitulo = (ch.classList && (
        ch.classList.contains('wp-block-woocommerce-cart-order-summary-heading-block') ||
        ch.classList.contains('wc-block-cart__totals-title')
      )) || (ch.tagName && ch.tagName.toLowerCase() === 'h2');
      var isCupon = ch.classList && (
        ch.classList.contains('scx-coupon-block') ||
        ch.classList.contains('wp-block-woocommerce-cart-order-summary-coupon-form-block')
      );
      var isShipping = ch.classList && ch.classList.contains('wp-block-woocommerce-cart-order-summary-totals-block');
      var isProd = ch.id === 'scx-rl-productos';
      var isDesc = ch.id === 'scx-rl-descuentos';
      var esWrapperTotalsFinal = (
        ch.classList &&
        ch.classList.contains('wc-block-components-totals-wrapper') &&
        !isCupon  // NO es el wrapper del cupon
      );
      if(isTitulo){ ord = 10; }
      else if(isCupon){ ord = 20; }
      else if(isShipping){ ord = 30; }
      else if(isProd){ ord = 70; }
      else if(isDesc){ ord = 75; }
      else if(esWrapperTotalsFinal){ ord = 99; }
      // Forzar con setProperty important (gana a style="order:X" normal y a CSS externo)
      ch.style.setProperty('order', ord, 'important');
      // Por si acaso: flex-order y orden CSS antiguo
      try{ ch.style.setProperty('flex-order', ord, 'important'); }catch(err){}
      // Debug color (solo usar temporal para ver cual es cual: ch.style.outline = '2px dashed #'+String(ord*333333).padStart(6,'0'));
    }
    // Si ya existen las 2 lineas, salir
    if(document.getElementById('scx-rl-productos') && document.getElementById('scx-rl-descuentos')){
      actualizarValores();
      return true;
    }
    var rows = document.querySelectorAll('.wc-block-cart-items__row, table.wc-block-cart-items tbody tr, .wc-block-cart-item');
    if(!rows || rows.length === 0){ return false; }
    // 1) Productos
    if(!document.getElementById('scx-rl-productos')){
      var p = document.createElement('div');
      p.id = 'scx-rl-productos';
      p.className = 'wc-block-components-totals-item wc-block-components-totals-subtotal-item scx-resumen-linea';
      // Orden inline + setProperty
      p.style.cssText = 'display:flex;flex-direction:row;align-items:center;justify-content:space-between;gap:12px;margin:8px 0 6px 0;padding:4px 0 10px 0;width:100%;border-bottom:1px dashed #e3e6ec;';
      p.innerHTML = '<span class="wc-block-components-totals-item__label scx-rl-label" style="font-size:14px;color:#374151;font-weight:400;flex:1 1 auto;">Productos (0)</span><span class="wc-block-components-totals-item__value scx-rl-value" style="font-size:14px;color:#1f2937;font-weight:500;white-space:nowrap;flex:0 0 auto;">S/ 0.00</span>';
      p.style.setProperty('order', 70, 'important');
      resumen.appendChild(p);
    }
    // 2) Descuentos (display none si no hay)
    if(!document.getElementById('scx-rl-descuentos')){
      var d = document.createElement('div');
      d.id = 'scx-rl-descuentos';
      d.className = 'wc-block-components-totals-item wc-block-components-totals-discount-item scx-resumen-linea';
      d.style.cssText = 'display:none;flex-direction:row;align-items:center;justify-content:space-between;gap:12px;margin:4px 0 8px 0;padding:4px 0 8px 0;width:100%;border-bottom:1px dashed #e3e6ec;';
      d.innerHTML = '<span class="wc-block-components-totals-item__label scx-rl-label" style="font-size:14px;color:#b91c1c;font-weight:400;flex:1 1 auto;">Descuentos (1)</span><span class="wc-block-components-totals-item__value scx-rl-value" style="font-size:14px;color:#b91c1c;font-weight:500;white-space:nowrap;flex:0 0 auto;">- S/ 0.00</span>';
      d.style.setProperty('order', 75, 'important');
      resumen.appendChild(d);
    }
    // Forzar orden DESPUES de agregar los nuevos hijos tambien
    try{
      var p1 = document.getElementById('scx-rl-productos'); if(p1) p1.style.setProperty('order', 70, 'important');
      var d1 = document.getElementById('scx-rl-descuentos'); if(d1) d1.style.setProperty('order', 75, 'important');
      var tit = resumen.querySelector('h2.wc-block-cart__totals-title, .wp-block-woocommerce-cart-order-summary-heading-block'); if(tit) tit.style.setProperty('order', 10, 'important');
      var totalWrapFinal=null;
      for(var j=0;j<resumen.children.length;j++){
        var cj=resumen.children[j];
        if(cj.classList && cj.classList.contains('wc-block-components-totals-wrapper') && !(cj.classList.contains('scx-coupon-block')||cj.classList.contains('wp-block-woocommerce-cart-order-summary-coupon-form-block'))){
          totalWrapFinal = cj; break;
        }
      }
      if(totalWrapFinal){ totalWrapFinal.style.setProperty('order', 99, 'important'); }
    }catch(err){}
    actualizarValores();
    return true;
  }
  // Actualizar valores: store async + fallback DOM
  async function actualizarValores(){
    var pLbl = $('#scx-rl-productos .scx-rl-label');
    var pVal = $('#scx-rl-productos .scx-rl-value');
    var dEl = $('#scx-rl-descuentos');
    var dLbl = dEl ? dEl.querySelector('.scx-rl-label') : null;
    var dVal = dEl ? dEl.querySelector('.scx-rl-value') : null;
    var totLbl = $('.wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-footer-item .wc-block-components-totals-item__label, .wc-block-components-totals-item.wc-block-components-totals-footer-item .wc-block-components-totals-item__label');
    if(totLbl){ var txt = (totLbl.textContent||'').trim(); if(txt.toLowerCase().indexOf('total') === 0){ totLbl.textContent = 'Total:'; } }
    var descEls = document.querySelectorAll('.wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-footer-item .wc-block-components-totals-item__description, .wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-item[class*="footer"] [class*="description"]');
    descEls.forEach(function(el){
      var txt=(el.textContent||'').toLowerCase();
      if(txt.indexOf('tax') >= 0){ try{ el.remove(); }catch(err){ el.style.display='none'; } }
    });
    // Valores del store async
    var itemsCount = 0;
    var sub = 0;
    var desc = 0;
    var descs = 0;
    var total = 0;
    try{
      if(window.wp && window.wp.data && typeof window.wp.data.select === 'function'){
        var sel = window.wp.data.select('wc/store/cart');
        if(sel && typeof sel.getCartData === 'function'){
          var cd;
          try{ cd = await sel.getCartData(); }catch(err){ cd = sel.getCartData(); }
          if(cd){
            itemsCount = cd.itemsCount || cd.items_count || 0;
            if(cd.coupons && Array.isArray(cd.coupons)){ descs = cd.coupons.length; }
            if(cd.totals && typeof cd.totals === 'object'){
              var mu = parseInt(cd.totals.currency_minor_unit||2, 10);
              var div = Math.pow(10, mu);
              if(cd.totals.total_items !== undefined){ sub = parseFloat(cd.totals.total_items)/div; }
              else if(cd.totals.subtotal !== undefined){ sub = parseFloat(cd.totals.subtotal)/div; }
              if(cd.totals.total_discount !== undefined){ desc = parseFloat(cd.totals.total_discount)/div; }
              else if(cd.totals.discount_total !== undefined){ desc = parseFloat(cd.totals.discount_total)/div; }
              if(cd.totals.total_price !== undefined){ total = parseFloat(cd.totals.total_price)/div; }
              else if(cd.totals.total !== undefined){ total = parseFloat(cd.totals.total)/div; }
            }
          }
        }
      }
    }catch(err){}
    // Fallback DOM si store dio 0
    if(!itemsCount){
      var r = document.querySelectorAll('.wc-block-cart-items__row, table.wc-block-cart-items tbody tr, .wc-block-cart-item');
      if(r && r.length){ itemsCount = r.length; }
    }
    if(!sub && total){ sub = total + desc; }
    // Actualizar textos
    if(pLbl){ pLbl.textContent = 'Productos (' + (itemsCount||0) + ')'; }
    if(pVal){ pVal.textContent = fmt(sub); }
    if(dEl){
      var hayD = Number(desc) > 0.001;
      dEl.style.display = hayD ? 'flex' : 'none';
      if(hayD){
        if(dLbl){ dLbl.textContent = 'Descuentos (' + (Number(descs)||0) + ')'; }
        if(dVal){ dVal.textContent = '- ' + fmt(desc); }
      } else {
        // NO hay descuento: LIMPIAR label y value para que no aparezcan textos viejos (bug "Descuentos (1)" visible)
        if(dLbl){ dLbl.textContent = 'Descuentos (0)'; }
        if(dVal){ dVal.textContent = '- S/ 0.00'; }
      }
    }
  }
  // Polling CREAR lineas (sin limites, lo intentamos hasta que existan)
  var rl2Itv = setInterval(function(){
    if(insertar()){
      clearInterval(rl2Itv);
      // Updates cada 2.5s (solo esto, no wp.data.subscribe para no romper el actualizar cantidad de Woo)
      actualizarValores();
      setInterval(actualizarValores, 2500);
      window.addEventListener('load', function(){ setTimeout(actualizarValores, 500); });
      // ============== CORRECION ERROR "Something went wrong" ==============
      // DESACTIVADO wp.data.subscribe. Causaba error al actualizar cantidad en el carrito.
      // try{ if(window.wp && window.wp.data && typeof window.wp.data.subscribe === 'function'){ window.wp.data.subscribe(function(){ actualizarValores(); }); } }catch(err){}
      // ===================================================================
    }
  }, 200);
  // Primer intento rapido
  setTimeout(function(){ insertar(); actualizarValores(); }, 200);
})();
</script>
<?php
}, 99999 );
