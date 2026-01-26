document.addEventListener('DOMContentLoaded', () => {
  const categoryLinks = document.querySelectorAll('.product-filters a');
  const productsGrid = document.querySelector('.home-products-grid');

  // --- Menú filtro móvil ---
  const mobileBtn = document.getElementById('mobile-filter-toggle');
  const mobileMenu = document.getElementById('mobile-filter-menu');
  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      mobileMenu.classList.toggle('open');
    });
    // Cerrar menú al hacer click fuera
    document.addEventListener('click', (e) => {
      if (!mobileMenu.contains(e.target) && !mobileBtn.contains(e.target)) {
        mobileMenu.classList.remove('open');
      }
    });
    // Cerrar menú al seleccionar una categoría
    mobileMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        mobileMenu.classList.remove('open');
      });
    });
  }

  // Solo actuamos en páginas con filtros + grid (home y taxonomy-product_cat)
  if (!categoryLinks.length || !productsGrid) return;

  const ajaxUrl = prsFilterProducts.ajaxUrl;
  const nonce = prsFilterProducts.nonce;

  let currentSlug = '';

  const setActiveLink = (slug) => {
    categoryLinks.forEach(link => {
      const linkSlug = link.getAttribute('data-category-slug') || '';
      if (linkSlug === slug) {
        link.classList.add('is-active');
      } else {
        link.classList.remove('is-active');
      }
    });
  };

  const loadProducts = (slug) => {
    const body = new URLSearchParams({
      action: 'prs_filter_products',
      security: nonce,
      category_slug: slug || '',
    });

    fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body,
    })
      .then(res => res.text())
      .then(html => {
        productsGrid.innerHTML = html;
      })
      .catch(err => {
        console.error('Error filtrando productos:', err);
      });
  };

  // 1) Detectar slug inicial desde la URL: /collections/{slug}/
  const path = window.location.pathname; // ej: /, /collections/tops/
  const match = path.match(/\/collections\/([^/]+)\/?/);
  const initialSlug = match ? match[1] : '';

  currentSlug = initialSlug;
  setActiveLink(initialSlug);
  // NO llamamos a loadProducts aquí: dejamos el grid inicial que viene de PHP.

  // 2) Manejar clic en filtros
  categoryLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();

      const slug = link.getAttribute('data-category-slug') || '';

      if (slug === currentSlug) return;

      currentSlug = slug;
      setActiveLink(slug);
      loadProducts(slug);

      let newUrl;
      if (!slug || slug === 'todo') {
        newUrl = `/`;
      } else {
        newUrl = `/collections/${encodeURIComponent(slug)}/`;
      }

      
      window.history.pushState({ category: slug }, '', newUrl);
    });
  });

  // 3) Navegación atrás/adelante mantiene el filtro
  window.addEventListener('popstate', (event) => {
    const slug = event.state && event.state.category ? event.state.category : '';
    currentSlug = slug;
    setActiveLink(slug);
    loadProducts(slug);
  });
});
