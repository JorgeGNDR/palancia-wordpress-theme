const {test} = require('node:test');
const assert = require('node:assert/strict');
const {JSDOM} = require('jsdom');
const fs = require('node:fs');
const state = {category: '', size: [], soldout: 'show', sort: 'categories'};
const tick = () => new Promise(resolve => setTimeout(resolve, 0));
function shop() {
  const dom = new JSDOM(`<!doctype html><html><body>
    <header class="site-header"><button id="mobile-filter-toggle">Menu</button></header>
    <main><section data-catalog='${JSON.stringify({state,homeUrl:'https://shop.test/sub/'})}'>
      <nav id="mobile-filter-menu"><a href="/sub/" data-category-slug="todo">Todo</a><a href="/sub/prendas/chaquetas/" data-category-slug="chaquetas">Chaquetas</a><button class="product-filter-trigger">Filtro</button><button class="product-sort-trigger">Orden</button></nav>
      <aside><button class="product-sort-trigger" id="desktop-sort">Orden</button></aside>
      <div class="home-products-grid"><a href="/old">Old product</a></div><p data-catalog-error hidden>Error</p>
      <div id="product-size-filter-modal"><div data-panel-close></div><div role="dialog"><button data-panel-close>Cerrar</button><button data-size-slug="xl">XL</button><button data-stock-filter="hide">Hide</button><button class="product-size-clear">Clear</button><button class="product-filter-apply">Aplicar</button></div></div>
      <div id="product-sort-modal"><div data-panel-close></div><div role="dialog"><button data-panel-close>Cerrar</button>${['categories','latest','price-asc','price-desc'].map(sort=>`<button data-sort="${sort}" aria-pressed="${sort==='categories'}">${sort}</button>`).join('')}</div></div>
    </section></main><footer><a href="/contact">Contact</a></footer></body></html>`, {url:'https://shop.test/sub/?utm_source=test', runScripts:'outside-only'});
  const w=dom.window, d=w.document;
  Object.defineProperty(w.HTMLElement.prototype,'inert',{get(){return this.hasAttribute('inert')},set(value){this.toggleAttribute('inert',value)}});
  w.HTMLElement.prototype.getClientRects=function(){return this.closest('[aria-hidden="true"]')?[]:[{}]};
  w.structuredClone=structuredClone;
  let viewport;
  w.matchMedia=()=>({addEventListener(type,callback){viewport=callback}});
  const requests=[];
  w.fetch=(url,options)=>new Promise((resolve,reject)=>requests.push({url,options,resolve,reject}));
  w.prsFilterProducts={ajaxUrl:'/wp-admin/admin-ajax.php'};
  for(const file of ['panels','shop-panels','filter-products'])w.eval(fs.readFileSync(`assets/js/${file}.js`,'utf8'));
  d.dispatchEvent(new w.Event('DOMContentLoaded'));
  const click=(s)=>d.querySelector(s).click();
  const escape=()=>d.dispatchEvent(new w.KeyboardEvent('keydown',{key:'Escape',bubbles:true,cancelable:true}));
  const success=(index,html='<a>New products</a>')=>requests[index].resolve({ok:true,json:async()=>({success:true,data:{html}})});
  return {dom,w,d,requests,click,escape,success,resize:()=>viewport()};
}
test('desktop click opens sort; bubbling cannot immediately close it', async()=>{
  const s=shop();s.click('#desktop-sort');
  assert(s.d.querySelector('#product-sort-modal').classList.contains('is-open'));
  assert(s.d.querySelector('header').inert);
  s.escape();assert(!s.d.body.classList.contains('prs-panel-open'));
  assert.equal(s.d.activeElement.id,'desktop-sort');s.dom.window.close();
});
test('nested mobile panels share Escape, focus, backdrop and viewport cleanup',()=>{
  const s=shop();s.click('#mobile-filter-toggle');s.click('.product-filter-trigger');
  assert.equal(s.d.activeElement.textContent,'Cerrar');
  s.escape();assert(s.d.querySelector('#mobile-filter-menu').classList.contains('open'));
  assert.equal(s.d.activeElement.className,'product-filter-trigger');
  s.click('.product-sort-trigger');s.click('#product-sort-modal [data-panel-close]');
  assert(s.d.querySelector('#mobile-filter-menu').classList.contains('open'));
  s.resize();assert(!s.d.body.classList.contains('prs-panel-open'));
  assert(!s.d.querySelector('footer').inert);s.dom.window.close();
});
test('filter traps Tab, cancel discards draft, apply preserves URL base and foreign params',async()=>{
  const s=shop();s.click('#mobile-filter-toggle');s.click('.product-filter-trigger');
  s.d.querySelector('.product-filter-apply').focus();
  s.d.dispatchEvent(new s.w.KeyboardEvent('keydown',{key:'Tab',cancelable:true}));
  assert.equal(s.d.activeElement.textContent,'Cerrar');
  s.click('[data-size-slug]');s.escape();s.click('.product-filter-trigger');
  assert.equal(s.d.querySelector('[data-size-slug]').getAttribute('aria-pressed'),'false');
  s.click('[data-size-slug]');s.click('.product-filter-apply');s.success(0);await tick();
  assert.equal(new URL(s.w.location.href).searchParams.get('size'),'xl');
  assert.equal(new URL(s.w.location.href).searchParams.get('utm_source'),'test');
  s.click('[data-category-slug="chaquetas"]');s.success(1);await tick();
  assert.equal(s.w.location.pathname,'/sub/prendas/chaquetas/');
  assert.equal(new URLSearchParams(s.requests[1].options.body).get('size_slugs'),'xl');s.dom.window.close();
});
test('stale responses cannot replace newer results; failures retain grid and URL',async()=>{
  const s=shop();s.click('#desktop-sort');s.click('[data-sort="latest"]');s.click('[data-sort="price-asc"]');
  assert(s.requests[0].options.signal.aborted);
  s.success(1,'<a>Ascending</a>');await tick();s.success(0,'<a>Stale</a>');await tick();
  assert.equal(s.d.querySelector('.home-products-grid').textContent,'Ascending');
  const url=s.w.location.href;s.click('#desktop-sort');s.click('[data-sort="price-desc"]');s.requests[2].reject(new Error('Offline'));await tick();
  assert.equal(s.w.location.href,url);assert.equal(s.d.querySelector('.home-products-grid').textContent,'Ascending');
  assert(!s.d.querySelector('[data-catalog-error]').hidden);assert.equal(s.d.querySelector('[data-sort="price-asc"]').getAttribute('aria-pressed'),'true');s.dom.window.close();
});
