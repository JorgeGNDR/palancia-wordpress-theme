const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const {JSDOM} = require('jsdom');

function setup() {
  const dom = new JSDOM('<div class="home-products-grid"></div>', {runScripts: 'outside-only'});
  let updated;
  dom.window.jQuery = (target) => {
    if (typeof target === 'function') return target();
    return {on(name, handler) {
      assert.equal(name, 'wc_cart_button_updated.palanciaCatalog');
      updated = handler;
    }};
  };
  dom.window.eval(fs.readFileSync('assets/js/catalog-cart.js', 'utf8'));
  return {document: dom.window.document, update: (button) => updated({}, button ? [button] : undefined)};
}

const card = '<div class="product-card-action"><a class="button" href="#add">Añadir al carrito</a></div>';

test('successful catalog addition leaves View cart and preserves keyboard focus, including replaced grids', () => {
  const {document, update} = setup();
  const grid = document.querySelector('.home-products-grid');
  for (let replacement = 0; replacement < 2; replacement++) {
    grid.innerHTML = card;
    const button = grid.querySelector('.button');
    button.focus();
    button.classList.add('added');
    button.insertAdjacentHTML('afterend', '<a class="added_to_cart" href="#cart">Ver carrito</a>');
    update(button);
    assert.equal(button.hidden, true);
    assert.equal(document.activeElement, grid.querySelector('.added_to_cart'));
    assert.equal(document.activeElement.hidden, false);
  }
});

test('incomplete requests and unrelated buttons stay available; mouse additions do not steal focus', () => {
  const {document, update} = setup();
  document.querySelector('.home-products-grid').innerHTML = card;
  const button = document.querySelector('.button');
  update();
  update(button);
  assert.equal(button.hidden, false);
  button.classList.add('added');
  update(button);
  assert.equal(button.hidden, false, 'Do not hide without a replacement link');
  button.insertAdjacentHTML('afterend', '<a class="added_to_cart" href="#cart">Ver carrito</a>');
  const focusBefore = document.activeElement;
  update(button);
  assert.equal(button.hidden, true);
  assert.equal(document.activeElement, focusBefore);
  document.body.insertAdjacentHTML('beforeend', card);
  const outside = document.body.lastElementChild.querySelector('.button');
  outside.classList.add('added');
  outside.insertAdjacentHTML('afterend', '<a class="added_to_cart" href="#cart">Ver carrito</a>');
  update(outside);
  assert.equal(outside.hidden, false);
});
