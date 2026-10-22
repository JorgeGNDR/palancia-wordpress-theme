/* Catalog state and transport. Panels live in shop-panels.js. */
document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('[data-catalog]');
  if (!root || typeof prsFilterProducts === 'undefined') return;
  const config = JSON.parse(root.dataset.catalog);
  const grid = root.querySelector('.home-products-grid');
  const motion = new window.PalanciaGridMotion(grid);
  const links = [...root.querySelectorAll('a[data-category-slug]')];
  const sizes = [...document.querySelectorAll('[data-size-slug]')];
  const stocks = [...document.querySelectorAll('[data-stock-filter]')];
  const sorts = [...document.querySelectorAll('[data-sort]')];
  const error = root.querySelector('[data-catalog-error]');
  let state = structuredClone(config.state), committed = structuredClone(state);
  let draft = {size: [...state.size], soldout: state.soldout};
  let request = null;
  const categoryUrl = (category) => links.find((link) => link.dataset.categorySlug === (category || 'todo'))?.href || config.homeUrl;
  const render = () => {
    links.forEach((link) => link.classList.toggle('is-active', link.dataset.categorySlug === (state.category || 'todo')));
    sizes.forEach((button) => {
      const active = draft.size.includes(button.dataset.sizeSlug);
      button.classList.toggle('is-active', active); button.setAttribute('aria-pressed', String(active));
    });
    stocks.forEach((button) => {
      const active = button.dataset.stockFilter === draft.soldout;
      button.classList.toggle('is-active', active); button.setAttribute('aria-pressed', String(active));
    });
    sorts.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.sort === state.sort)));
  };
  const updateUrl = () => {
    const url = new URL(categoryUrl(state.category), window.location.href);
    const currentParams = new URL(window.location.href).searchParams;
    for (const key of new Set(currentParams.keys())) {
      if (url.searchParams.has(key) || (config.routeKeys || []).includes(key)) continue;
      currentParams.getAll(key).forEach((value) => url.searchParams.append(key, value));
    }
    ['size', 'soldout', 'sort'].forEach((key) => url.searchParams.delete(key));
    if (state.size.length) url.searchParams.set('size', state.size.join(','));
    if (state.soldout === 'hide') url.searchParams.set('soldout', 'hide');
    if (state.sort !== 'categories') url.searchParams.set('sort', state.sort);
    window.history.pushState({}, '', url);
  };
  const load = async () => {
    request?.abort();
    const current = new AbortController(); request = current;
    const snapshot = structuredClone(state);
    grid.classList.add('is-loading'); grid.setAttribute('aria-busy', 'true'); error.hidden = true;
    try {
      const response = await fetch(prsFilterProducts.ajaxUrl, {
        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, signal: current.signal,
        body: new URLSearchParams({action: 'prs_filter_products', category_slug: snapshot.category,
          size_slugs: snapshot.size.join(','), stock_filter: snapshot.soldout, sort: snapshot.sort}),
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const result = await response.json();
      if (request !== current) return;
      if (!result.success || typeof result.data?.html !== 'string') throw new Error('Invalid catalog response');
      state = result.data.state || snapshot; committed = structuredClone(state);
      draft = {size: [...state.size], soldout: state.soldout};
      motion.replace(result.data.html); updateUrl(); render();
      document.dispatchEvent(new CustomEvent('catalog:committed'));
    } catch (failure) {
      if (failure.name === 'AbortError' || request !== current) return;
      state = structuredClone(committed); draft = {size: [...state.size], soldout: state.soldout}; render();
      error.hidden = false;
    } finally {
      if (request === current) { request = null; grid.classList.remove('is-loading'); grid.setAttribute('aria-busy', 'false'); }
    }
  };
  document.getElementById('product-size-filter-modal')?.addEventListener('panel:beforeopen', () => {
    draft = {size: [...state.size], soldout: state.soldout}; render();
  });
  links.forEach((link) => link.addEventListener('click', (event) => {
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault(); state.category = link.dataset.categorySlug === 'todo' ? '' : link.dataset.categorySlug; render(); load();
  }));
  sizes.forEach((button) => button.addEventListener('click', () => {
    const slug = button.dataset.sizeSlug;
    draft.size = draft.size.includes(slug) ? draft.size.filter((item) => item !== slug) : [...draft.size, slug]; render();
  }));
  stocks.forEach((button) => button.addEventListener('click', () => { draft.soldout = button.dataset.stockFilter; render(); }));
  document.querySelectorAll('.product-size-clear').forEach((button) => button.addEventListener('click', () => {
    draft = {size: [], soldout: 'show'}; render();
  }));
  document.querySelectorAll('.product-filter-apply').forEach((button) => button.addEventListener('click', () => {
    state = {...state, size: [...draft.size], soldout: draft.soldout}; load();
  }));
  sorts.forEach((button) => button.addEventListener('click', () => { state.sort = button.dataset.sort; render(); load(); }));
  window.addEventListener('popstate', () => window.location.reload()); render();
}, {once: true});
