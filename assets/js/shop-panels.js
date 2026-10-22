document.addEventListener('DOMContentLoaded', () => {
  const panels = new window.PalanciaPanels();
  const header = document.querySelector('.site-header');
  const menu = document.getElementById('mobile-filter-menu');
  panels.register('mobile-menu', menu, document.querySelectorAll('#mobile-filter-toggle'), {
    openClass: 'open', bodyClass: 'mobile-shop-menu-open', outsideClose: true,
    extraRoots: header ? [header] : [], toggleLabels: ['Abrir menú de categorías', 'Cerrar menú de categorías'],
  });
  panels.register('filters', document.getElementById('product-size-filter-modal'), document.querySelectorAll('.product-filter-trigger'));
  panels.register('sort', document.getElementById('product-sort-modal'), document.querySelectorAll('.product-sort-trigger'));
  document.addEventListener('catalog:committed', () => panels.closeAll());
  menu?.querySelectorAll('a[data-category-slug]').forEach((link) => link.addEventListener('click', (event) => {
    if (!event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) panels.closeAll();
  }));
  window.matchMedia('(max-width: 768px)').addEventListener('change', () => panels.closeAll());
  if (header) {
    const measure = () => document.documentElement.style.setProperty('--actual-header-height', `${header.getBoundingClientRect().height}px`);
    measure();
    if (typeof ResizeObserver !== 'undefined') new ResizeObserver(measure).observe(header);
    else window.addEventListener('resize', measure);
  }
}, {once: true});
