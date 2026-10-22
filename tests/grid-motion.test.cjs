const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const {JSDOM} = require('jsdom');
const tick = () => new Promise(resolve => setTimeout(resolve, 0));
const card = (id,x=0,y=0) => `<a href="/${id}" class="product-item" data-product-id="${id}" data-x="${x}" data-y="${y}"><img alt="${id}"><span>${id}</span></a>`;
function setup({reduced=false, supported=true}={}) {
  const dom = new JSDOM(`<body><div class="home-products-grid">${card('a')}${card('b',100)}${card('c',200)}</div></body>`, {runScripts:'outside-only'});
  const w=dom.window,d=w.document,grid=d.querySelector('.home-products-grid');
  const preference={matches:reduced,addEventListener(type,callback){this.change=callback}};
  w.matchMedia=()=>preference;
  w.HTMLElement.prototype.getBoundingClientRect=function(){const left=Number(this.dataset.x||0),top=Number(this.dataset.y||0);return {left,top,width:100,height:100,right:left+100,bottom:top+100}};
  const runs=[];
  if(supported)w.HTMLElement.prototype.animate=function(frames,options){
    let resolve,reject;const finished=new Promise((a,b)=>{resolve=a;reject=b});
    const animation={finished,cancel(){this.cancelled=true;reject(new Error('cancelled'))},finish(){resolve()}};
    runs.push({node:this,frames,options,animation});return animation;
  };
  w.eval(fs.readFileSync('assets/js/grid-motion.js','utf8'));
  return {dom,w,d,grid,runs,preference,motion:new w.PalanciaGridMotion(grid)};
}
test('retained products move by identity, entrants fade, exits do not affect grid layout',async()=>{
  const s=setup();s.motion.replace(card('b')+card('a',100)+card('d',200));
  assert.equal(s.grid.children.length,3);
  const moved=s.runs.find(run=>run.node.dataset.productId==='b'&&!run.node.closest('.prs-grid-motion-layer'));
  assert.equal(moved.frames[0].transform,'translate(100px, 0px) scale(1, 1)');
  const incoming=s.runs.find(run=>run.node.dataset.productId==='d');assert.equal(incoming.frames[0].opacity,0);
  const layer=s.d.querySelector('.prs-grid-motion-layer');assert.equal(layer.getAttribute('aria-hidden'),'true');
  assert.equal(layer.children[0].dataset.productId,'c');assert.equal(layer.children[0].tabIndex,-1);
  s.runs.forEach(run=>run.animation.finish());await tick();
  assert(!s.d.querySelector('.prs-grid-motion-layer'));assert.equal(s.motion.animations.size,0);s.dom.window.close();
});
test('unchanged positions and offscreen products are not animated',()=>{
  const s=setup();s.motion.replace(card('a')+card('b',100)+card('c',200)+card('d',0,5000));
  assert.equal(s.runs.length,0);s.dom.window.close();
});
test('interrupted transitions and resizing cancel all animations and exit snapshots',async()=>{
  const s=setup();s.motion.replace(card('b')+card('d',100));const first=s.runs.slice();
  s.motion.replace(card('d')+card('e',100));assert(first.every(run=>run.animation.cancelled));
  assert.equal(s.d.querySelectorAll('.prs-grid-motion-layer').length,1);
  s.w.dispatchEvent(new s.w.Event('resize'));await tick();
  assert(!s.d.querySelector('.prs-grid-motion-layer'));assert.equal(s.motion.animations.size,0);
  assert.equal(s.grid.children[0].dataset.productId,'d');s.dom.window.close();
});
test('reduced motion and browsers without animation support still replace results immediately',()=>{
  for(const options of [{reduced:true},{supported:false}]){
    const s=setup(options);s.motion.replace(card('b')+card('a',100));
    assert.equal(s.runs.length,0);assert.equal(s.grid.children[0].dataset.productId,'b');s.dom.window.close();
  }
});
test('changing motion preference cancels in-flight effects and preserves focus by product ID',async()=>{
  const s=setup();s.grid.children[1].focus();s.motion.replace(card('b')+card('a',100));
  assert.equal(s.d.activeElement.dataset.productId,'b');
  s.preference.matches=true;s.preference.change();await tick();assert.equal(s.motion.animations.size,0);
  s.motion.replace('<p>No hay productos.</p>');assert.equal(s.d.activeElement,s.grid);assert.equal(s.grid.textContent,'No hay productos.');
  s.dom.window.close();
});
test('article cards restore keyboard focus to their product link after a filter',()=>{
  const s=setup();
  const article='<article class="product-item" data-product-id="a"><a class="product-image-link" href="/a">Photo</a><button>Cart</button></article>';
  s.grid.innerHTML=article;s.grid.querySelector('button').focus();s.motion.replace(article);
  assert.equal(s.d.activeElement.className,'product-image-link');s.dom.window.close();
});
