(function(){
  function currentHeight(){
    var de=document.documentElement,b=document.body;
    return Math.max(
      b.scrollHeight,de.scrollHeight,
      b.offsetHeight,de.offsetHeight,
      b.clientHeight,de.clientHeight
    );
  }
  function postHeight(){
    var h=currentHeight();
    try {
      window.parent && window.parent.postMessage({source:'dvvs-priem',height:h},'*');
    } catch(e){}
  }

  // Базовые события
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', postHeight);
  else postHeight();
  window.addEventListener('load', postHeight);
  window.addEventListener('hashchange', function(){ setTimeout(postHeight,60); });

  // ResizeObserver на html и body
  try{
    var ro=new ResizeObserver(function(){ postHeight(); });
    if(document.documentElement) ro.observe(document.documentElement);
    if(document.body) ro.observe(document.body);
  }catch(e){}

  // MutationObserver на весь документ — реагируем на любые изменения
  try{
    var moTimer=null;
    var mo=new MutationObserver(function(){
      if(moTimer) clearTimeout(moTimer);
      moTimer=setTimeout(postHeight,50);
    });
    mo.observe(document.documentElement,{childList:true,subtree:true,attributes:true,characterData:true});
  }catch(e){}

  // Клики/анимации/загрузки изображений
  document.addEventListener('click', function(){ setTimeout(postHeight,80); }, true);
  document.addEventListener('transitionend', function(){ setTimeout(postHeight,80); }, true);
  document.addEventListener('load', function(e){
    if(e && e.target && e.target.tagName==='IMG') setTimeout(postHeight,20);
  }, true);

  // Ответ на запрос родителя
  window.addEventListener('message', function(e){
    var d=e && e.data;
    if(d && d.type==='dvvs-priem:get-height') postHeight();
  }, false);
})();