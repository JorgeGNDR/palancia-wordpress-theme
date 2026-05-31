document.addEventListener('DOMContentLoaded', () => {
  const filterLinks = document.querySelectorAll('.product-filters a, .mobile-filter-menu a');
  const productsGrid = document.querySelector('.home-products-grid');

  const mobileBtn = document.getElementById('mobile-filter-toggle');
  const mobileMenu = document.getElementById('mobile-filter-menu');

  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      mobileMenu.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
      if (!mobileMenu.contains(e.target) && !mobileBtn.contains(e.target)) {
        mobileMenu.classList.remove('open');
      }
    });

    mobileMenu.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        mobileMenu.classList.remove('open');
      });
    });
  }

  if (!filterLinks.length || !productsGrid || typeof prsFilterProducts === 'undefined') {
    return;
  }

  const { ajaxUrl, nonce } = prsFilterProducts;
  let currentCategory = '';

  const updateActiveLinks = () => {
    filterLinks.forEach((link) => {
      const catSlug = link.getAttribute('data-category-slug');

      link.classList.remove('is-active');

      if (catSlug && catSlug === currentCategory) {
        link.classList.add('is-active');
      }
    });
  };

  const loadProducts = () => {
    const body = new URLSearchParams({
      action: 'prs_filter_products',
      security: nonce,
      category_slug: currentCategory === 'todo' ? '' : currentCategory,
    });

    fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body,
    })
      .then((res) => res.text())
      .then((html) => {
        productsGrid.innerHTML = html.trim();
      })
      .catch((err) => {
        console.error('Error filtrando productos:', err);
      });
  };

  const path = window.location.pathname;
  const match = path.match(/\/collections\/([^/]+)\/?/);

  currentCategory = match ? decodeURIComponent(match[1]) : 'todo';

  updateActiveLinks();

  filterLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();

      const catSlug = link.getAttribute('data-category-slug');

      if (!catSlug || catSlug === currentCategory) return;
      currentCategory = catSlug;

      updateActiveLinks();
      loadProducts();

      const newUrl = !currentCategory || currentCategory === 'todo'
        ? '/'
        : `/collections/${encodeURIComponent(currentCategory)}/`;
      window.history.pushState({ category: currentCategory }, '', newUrl);
    });
  });

  window.addEventListener('popstate', () => {
    window.location.reload();
  });
});
