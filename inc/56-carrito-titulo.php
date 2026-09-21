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
    // [ELIMINADO 17/09] BLOQUE FORZAR ORDER POR JS.
    // AHORA ORDER ES SOLO POR CSS (style.css, selector por clase):
    // 10 Título, 20 Cupón, 30 Shipping, 70 Productos, 75 Descuentos, 99 Total.
    // Motivo: style.setProperty('order',X,'important') cada ciclo creaba race condition
    // con React (quien borra/crea el nodo en cada repintado del resumen) = ERROR insertBefore
    // Node is not child of this node. NUNCA MAS JS toca el order.
    // ==========================================
    // Si ya existen las 2 lineas, salir y solo actualizar textos (NO tocar el DOM)
    if(document.getElementById('scx-rl-productos') && document.getElementById('scx-rl-descuentos')){
      actualizarValores();
      return true;
    }
    var rows = document.querySelectorAll('.wc-block-cart-items__row, table.wc-block-cart-items tbody tr, .wc-block-cart-item');
    if(!rows || rows.length === 0){ return false; }
    // 1) Productos (CREAR UNA SOLA VEZ. NUNCA mas order por JS = CSS lo hace.)
    if(!document.getElementById('scx-rl-productos')){
      var p = document.createElement('div');
      p.id = 'scx-rl-productos';
      p.className = 'wc-block-components-totals-item wc-block-components-totals-subtotal-item scx-resumen-linea';
      // Order 70 LO ASIGNA style.css por selector #scx-rl-productos. NUNCA JS order inline.
      p.style.cssText = 'display:flex;flex-direction:row;align-items:center;justify-content:space-between;gap:12px;margin:8px 0 6px 0;padding:4px 0 10px 0;width:100%;border-bottom:1px dashed #e3e6ec;';
      p.innerHTML = '<span class="wc-block-components-totals-item__label scx-rl-label" style="font-size:14px;color:#374151;font-weight:400;flex:1 1 auto;">Productos (0)</span><span class="wc-block-components-totals-item__value scx-rl-value" style="font-size:14px;color:#1f2937;font-weight:500;white-space:nowrap;flex:0 0 auto;">S/ 0.00</span>';
      resumen.appendChild(p);
    }
    // 2) Descuentos (CREAR UNA SOLA VEZ. NUNCA mas order por JS).
    if(!document.getElementById('scx-rl-descuentos')){
      var d = document.createElement('div');
      d.id = 'scx-rl-descuentos';
      d.className = 'wc-block-components-totals-item wc-block-components-totals-discount-item scx-resumen-linea';
      // Order 75 LO ASIGNA style.css por selector #scx-rl-descuentos. NUNCA JS order inline.
      d.style.cssText = 'display:none;flex-direction:row;align-items:center;justify-content:space-between;gap:12px;margin:4px 0 8px 0;padding:4px 0 8px 0;width:100%;border-bottom:1px dashed #e3e6ec;';
      d.innerHTML = '<span class="wc-block-components-totals-item__label scx-rl-label" style="font-size:14px;color:#b91c1c;font-weight:400;flex:1 1 auto;">Descuentos (0)</span><span class="wc-block-components-totals-item__value scx-rl-value" style="font-size:14px;color:#b91c1c;font-weight:500;white-space:nowrap;flex:0 0 auto;">- S/ 0.00</span>';
      resumen.appendChild(d);
    }
    // [ELIMINADO 17/09] "Forzar orden DESPUES de agregar los nuevos hijos" (causaba crash insertBefore).
    // Order ahora es SOLO por CSS. NUNCA MAS JS toca style.order de ningun hijo.
    actualizarValores();
    return true;
  }
  // Actualizar valores: SOLO FALLBACK DOM (copiar valores YA actualizados por Woo React en sus nodos nativos).
  // NUNCA mas wp.data.select() -> retorna datos CACHEADOS (no actualiza al cambiar cantidad hasta F5).
  function actualizarValores(){
    var pLbl = $('#scx-rl-productos .scx-rl-label');
    var pVal = $('#scx-rl-productos .scx-rl-value');
    var dEl  = $('#scx-rl-descuentos');
    var dLbl = dEl ? dEl.querySelector('.scx-rl-label') : null;
    var dVal = dEl ? dEl.querySelector('.scx-rl-value') : null;
    // Total label "Subtotal / Total" -> solo "Total:"
    var totLbl = $('.wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-footer-item .wc-block-components-totals-item__label, .wc-block-components-totals-item.wc-block-components-totals-footer-item .wc-block-components-totals-item__label');
    if(totLbl){ var txtL = (totLbl.textContent||'').trim(); if(txtL.toLowerCase().indexOf('total') === 0){ totLbl.textContent = 'Total:'; } }
    // Taxes description ocultar (SOLO display, no remove() para no romper React)
    var descEls = document.querySelectorAll('.wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-footer-item .wc-block-components-totals-item__description, .wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-item[class*="footer"] [class*="description"]');
    descEls.forEach(function(el){
      var tx=(el.textContent||'').toLowerCase();
      if(tx.indexOf('tax') >= 0 || tx.indexOf('impuesto') >= 0){ try{ el.style.display='none'; }catch(err){} }
    });
    // ==========================================
    // OBTENER VALORES (SÓLO DOM, SIN wp.data = SIN cache.)
    // Los nodos de Woo React (nativos) se actualizan AUTOMATICAMENTE cuando termina update-item 200 OK.
    // Nosotros SÓLO COPIAMOS su textContent a nuestras lineas (Productos / Descuentos).
    // ==========================================
    var itemsCount = 0;
    var sub = 0;
    var desc = 0;
    var descs = 0;
    var rows = document.querySelectorAll('.wc-block-cart-items__row, table.wc-block-cart-items tbody tr');
    if(rows && rows.length){
      for(var ix=0; ix<rows.length; ix++){
        var row = rows[ix];
        var qInput = row.querySelector('input[type="number"][class*="quantity"], input.wc-block-components-quantity-selector__input, input[name*="quantity"], [class*="quantity-selector"] input');
        if(qInput){ var qty = parseInt(qInput.value||qInput.getAttribute('value')||'1',10); itemsCount += (qty>0 && !isNaN(qty)) ? qty : 1; }
        else { itemsCount += 1; }
        var moneyEls = row.querySelectorAll('span.woocommerce-Price-amount, span[class*="price"], td:last-child, [class*="price-amount"]');
        var lineTot = 0;
        if(moneyEls && moneyEls.length){ lineTot = parseMoney((moneyEls[moneyEls.length-1].textContent||'').trim()); }
        if(lineTot===0){
          var rowText = (row.textContent||'').replace(/\s+/g,' ');
          var re = /S\/\s*([0-9.,]+)/g; var match; var lastMatch = null;
          while( (match = re.exec(rowText)) !== null ){ lastMatch = match; }
          if(lastMatch){ lineTot = parseMoney(lastMatch[0]); }
        }
        if(lineTot>0){ sub += lineTot; }
      }
    }
    // 2) Subtotal: buscar nodo WOO NATIVO "Subtotal" / subtotal-item en resumen padre
    var natSub = document.querySelector('.wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-subtotal-item .wc-block-components-totals-item__value, .wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-item[class*="subtotal"] .wc-block-components-totals-item__value, .wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-wrapper > *:first-child .wc-block-components-totals-item__value, .wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-item .wc-block-components-totals-item__value');
    if(natSub && sub===0){ sub = parseMoney( (natSub.textContent||'').trim() ); }
    // 3) Descuento: buscar nodo WOO NATIVO "Descuentos" / discount-item en resumen padre.
    //    Ademas, contar coupons aplicados (descs) para el label "Descuentos (N)".
    var natDesc = document.querySelectorAll('.wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-discount-item, .wp-block-woocommerce-cart-order-summary-block [class*="-discount"]');
    if(natDesc && natDesc.length && sub===0){
      descs = natDesc.length;
      for(var k=0;k<natDesc.length;k++){
        var vEl = natDesc[k].querySelector('.wc-block-components-totals-item__value');
        if(vEl){ var vx = parseMoney( (vEl.textContent||'').trim() ); if(vx>0){ desc += vx; } }
      }
    }
    // 4) Contar cupones aplicados (alternativa mas exacta: nodos .wc-block-components-totals-coupon)
    var coupAplic = document.querySelectorAll('.wp-block-woocommerce-cart-order-summary-block [class*="coupon"] .wc-block-components-totals-item__value, .wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-coupon, .wp-block-woocommerce-cart-order-summary-block [data-coupon]');
    if(coupAplic && coupAplic.length && descs===0){ descs = coupAplic.length; }
    // DESCUENTO CALCULADO (preferido a natDesc, que no existe en tu resumen):
    // Subtotal (por filas) - Total del resumen Woo = Descuento total aplicado:
    if(sub > 0 && total > 0 && sub > total + 0.01 && desc === 0){
      desc = sub - total;
      descs = 1;
      var chips = document.querySelectorAll('.wp-block-woocommerce-cart-order-summary-block .wc-block-components-chip, .wp-block-woocommerce-cart-order-summary-block [data-coupon]');
      if(chips && chips.length){ descs = chips.length; }
    }
    // Fallback si itemsCount y sub son 0 pero Total no es 0 (carrito con datos):
    var natTot = document.querySelector('.wp-block-woocommerce-cart-order-summary-block .wc-block-components-totals-footer-item .wc-block-components-totals-item__value');
    var total = natTot ? parseMoney( (natTot.textContent||'').trim() ) : 0;
    if(itemsCount===0 && total>0){ itemsCount = 1; }
    if(sub===0 && total>0){ sub = total + desc; }
    // ==========================================
    // FIN: ACTUALIZAR SOLO textContent y display. NUNCA tocar order/create/remove.
    // ==========================================
    if(pLbl){ pLbl.textContent = 'Productos (' + (itemsCount||0) + ')'; }
    if(pVal){ pVal.textContent = fmt(sub); }
    var hayD = Number(desc) > 0.001;
    if(dEl){
      dEl.style.display = hayD ? 'flex' : 'none';
      if(hayD){
        if(dLbl){ dLbl.textContent = 'Descuentos (' + (Number(descs)||0) + ')'; }
        if(dVal){ dVal.textContent = '- ' + fmt(desc); }
      } else {
        if(dLbl){ dLbl.textContent = 'Descuentos (0)'; }
        if(dVal){ dVal.textContent = '- S/ 0.00'; }
      }
    }
    // ============================================================
    // ACTUALIZAR TITULO "Carrito (N)" (h2#scx-carrito-titulo)
    // PHP lo crea 1 sola vez al cargar la pagina. Se quedaba congelado.
    // Actualizamos su numero y label "producto(s)" aqui.
    // ============================================================
    var titH2 = document.getElementById('scx-carrito-titulo');
    if(titH2){
      var n = Math.max(0, parseInt(itemsCount||0,10));
      titH2.setAttribute('data-scx-count', String(n));
      var spnCount = titH2.querySelector('.scx-carrito-titulo__count');
      var spnLbl = titH2.querySelector('.scx-carrito-titulo__lbl');
      if(spnCount){ spnCount.textContent = String(n); }
      if(spnLbl){ spnLbl.textContent = (n === 1 ? 'producto' : 'productos'); }
    }
    // ============================================================
    // UX NIVEL 2: Mensaje mini "No hay mas unidades" por fila carrito.
    // Si input quantity VALUE == MAX (ej: max=1 value=1 ultimo stock)
    // mostrar bajo el selector: "⚠ No hay más unidades en stock"
    // Evita que usuario piense que el boton + esta roto.
    // ============================================================
    try{
      var filas = document.querySelectorAll('.wc-block-cart-items__row, table.wc-block-cart-items tbody tr');
      if(filas && filas.length){
        for(var ix=0; ix<filas.length; ix++){
          var row = filas[ix];
          var qInp = row.querySelector('.wc-block-cart-item__quantity input[type="number"], .wc-block-components-quantity-selector__input, [class*="quantity-selector"] input[type="number"]');
          if(!qInp) continue;
          var v = parseInt(qInp.value||'0',10);
          var mx = parseInt(qInp.getAttribute('max')||'0',10);
          var mn = parseInt(qInp.getAttribute('min')||'0',10);
          // Contenedor CORRECTO para mostrar el msj: el bloque .wc-block-cart-item__quantity (TIENE overflow visible
          // para mostrar children POR FUERA del flex box; el quantity selector interno TIENE overflow:hidden = cortaba el span).
          var boxContainer = row.querySelector('.wc-block-cart-item__quantity') || qInp.closest('.wc-block-cart-item__quantity') || qInp.closest('.wc-block-components-quantity-selector') || qInp.parentElement;
          if(!boxContainer) continue;
          var msg = boxContainer.querySelector('.scx-no-stock-msg');
          var debeMostrar = (mx>0 && v >= mx) || (mn===mx && mx>0 && v>0);
          if(debeMostrar){
            if(!msg){
              msg = document.createElement('span');
              msg.className = 'scx-no-stock-msg';
              msg.textContent = '\u26A0 Ya no hay m\u00E1s unidades disponibles';
              msg.setAttribute('role','status');
              msg.setAttribute('aria-live','polite');
              // Styles FUERA de cualquier overflow:hidden => width auto, alineado a la izquierda.
              msg.style.cssText = 'display:block;margin:5px 0 0 0;padding:0;font-size:11px;font-weight:500;color:#b91c1c;line-height:1.3;letter-spacing:0;white-space:nowrap;width:auto;flex:0 0 auto;text-align:left;';
              boxContainer.appendChild(msg);
            }
            try{ msg.style.display = 'block'; }catch(err){}
          } else {
            if(msg){ try{ msg.style.display = 'none'; }catch(err){} }
          }
        }
      }
    }catch(err){}
    // ============================================================
    // TRADUCCION UI (SOLO LECTOR - SOLO textContent, sin tocar DOM)
    // Traduce los textos VISIBLES de la interfaz que siguen en ingles.
    // Se actualiza en cada ciclo por si React re-renderiza (cambios cantidad).
    // ============================================================
    try{
      // 1) Tabla lista productos: th/div header con texto "Product" -> "Producto"
      var tbl = document.querySelector('table.wc-block-cart-items, .wp-block-woocommerce-cart-line-items-block');
      if(tbl){
        var headerEls = tbl.querySelectorAll('th, thead *');
        headerEls.forEach(function(el){
          for(var k=0;k<el.childNodes.length;k++){
            var tn = el.childNodes[k];
            if(tn.nodeType===3){ var tx=(tn.nodeValue||'').replace(/\s+/g,' ').trim(); if(tx==='Product'){ tn.nodeValue='Producto'; break; } }
          }
        });
        // Fallback: si no es th, buscar div/span que tenga solo "Product" como texto visible.
        var divsHd = tbl.querySelectorAll('[class*=header], [class*=title]');
        divsHd.forEach(function(el){
          var cur=(el.textContent||'').replace(/\s+/g,' ').trim();
          if(cur==='Product'){ el.textContent='Producto'; }
        });
      }
    }catch(err){}
  }
  // parseMoney: extraer numero correcto (1,234.56 INT o 1.234,56 PE). Evita bug: replace(/\./g,'') => "30,000.00" = 3,000,000 MAL.
  function parseMoney(t){
    if(!t) return 0;
    // Si tiene patron "S/ ..." tomar de ahi
    var mt = t.match(/S\/\s*([0-9.,]+)/);
    var s = mt ? mt[1] : String(t).replace(/[^0-9,\.-]/g,'');
    if(!s) return 0;
    var lastComma = s.lastIndexOf(',');
    var lastDot = s.lastIndexOf('.');
    if(lastComma === -1 && lastDot === -1){ var n1 = parseInt(s,10); return isNaN(n1)?0:n1; }
    if(lastDot > lastComma){ // '.' es el ultimo = decimal (formato INT): "30,000.00" -> quitar ',' -> parseFloat
      s = s.replace(/,/g,'');
    } else if(lastComma > lastDot){ // ',' es el ultimo = decimal (formato PE): "30.000,00" -> quitar '.' -> cambiar ',' por '.'
      s = s.replace(/\./g,'').replace(',','.');
    } else if(lastComma !== -1 && lastDot === -1){ // solo ',' -> "1234,56" -> cambiar ',' por '.'
      s = s.replace(',','.');
    }
    var n = parseFloat(s); return (n && !isNaN(n)) ? n : 0;
  }
  // Polling CREAR lineas (sin limites, lo intentamos hasta que existan)
  var rl2Itv = setInterval(function(){
    if(insertar()){
      clearInterval(rl2Itv);
      // Updates cada 1.3s (mas rapido que 2.5s, SIN wp.data.subscribe)
      actualizarValores();
      setInterval(actualizarValores, 1300);
      window.addEventListener('load', function(){ setTimeout(actualizarValores, 400); });
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
