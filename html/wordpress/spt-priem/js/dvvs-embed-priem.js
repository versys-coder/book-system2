(function(){
  if (window.__DVVS_PRIEM_EMBED_MOUNTED__) return;
  window.__DVVS_PRIEM_EMBED_MOUNTED__ = true;

  var TARGET_URL = 'https://price.dvvs-ekb.ru/spt-priem/priem.html';
  var MIN_H = 560, HARD_MAX = 12000;
  var iframe;

  function clamp(h) {
    h = Number(h)||0;
    if (h < MIN_H) h = MIN_H;
    if (h > HARD_MAX) h = HARD_MAX;
    return Math.round(h);
  }

  function requestHeightPing(){
    if (!iframe || !iframe.contentWindow) return;
    [0, 80, 180, 350, 700].forEach(function(d){
      setTimeout(function(){
        try {
          iframe.contentWindow.postMessage({ type:'dvvs-priem:get-height' }, 'https://price.dvvs-ekb.ru');
        } catch(e){}
      }, d);
    });
  }

  function ensureWrap(){
    var accRoot = document.querySelector('.stage-accordeon');
    if (accRoot) accRoot.style.setProperty('display','none','important');
    var container = document.querySelector('.intelligence .container') || document.querySelector('.container') || document.body;
    var wrap = document.getElementById('dvvs-priem-wrap');
    if (!wrap){
      wrap = document.createElement('div');
      wrap.id = 'dvvs-priem-wrap';
      wrap.style.cssText = 'margin:0;padding:0;width:100%;';
      if (accRoot && accRoot.parentNode) accRoot.parentNode.insertBefore(wrap, accRoot.nextSibling);
      else container.appendChild(wrap);
    }
    return wrap;
  }

  function mount(){
    var wrap = ensureWrap();
    iframe = wrap.querySelector('iframe');
    if (!iframe){
      iframe = document.createElement('iframe');
      iframe.id = 'dvvs-priem-iframe';
      iframe.src = TARGET_URL + (location.hash || '');
      iframe.loading = 'eager';
      iframe.allow = 'fullscreen';
      iframe.setAttribute('referrerpolicy','no-referrer-when-downgrade');
      iframe.style.cssText = 'display:block;width:100%;height:'+MIN_H+'px;border:0;border-radius:12px;background:#fff;margin:0;padding:0;';
      wrap.appendChild(iframe);

      iframe.addEventListener('load', requestHeightPing);
      iframe.addEventListener('pointerdown', requestHeightPing);
    }

    window.addEventListener('message', function(e){
      var d = e && e.data;
      if (!d || d.source !== 'dvvs-priem') return;
      var h = clamp(d.height);
      if (!h) return;
      iframe.style.setProperty('height', h + 'px', 'important');
    }, false);

    window.addEventListener('resize', requestHeightPing);
    document.addEventListener('visibilitychange', function(){ if (!document.hidden) requestHeightPing(); });

    window.addEventListener('hashchange', function(){
      try { if (iframe && iframe.contentWindow) iframe.contentWindow.location.hash = location.hash; } catch(e){}
      requestHeightPing();
    });

    setTimeout(requestHeightPing, 200);
    setTimeout(requestHeightPing, 800);
    setTimeout(requestHeightPing, 1600);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
  else mount();

  window.addEventListener('livewire:navigated', function(){ try { mount(); } catch(e){} });
})();