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
  let currentSize = '';

  const updateActiveLinks = () => {
    filterLinks.forEach((link) => {
      const catSlug = link.getAttribute('data-category-slug');
      const sizeSlug = link.getAttribute('data-size-slug');

      link.classList.remove('is-active');

      if (catSlug && catSlug === currentCategory) {
        link.classList.add('is-active');
      }

      if (sizeSlug && sizeSlug === currentSize) {
        link.classList.add('is-active');
      }
    });
  };

  const loadProducts = () => {
    const body = new URLSearchParams({
      action: 'prs_filter_products',
      security: nonce,
      category_slug: currentCategory === 'todo' ? '' : currentCategory,
      size_slug: currentSize,
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
  const urlParams = new URLSearchParams(window.location.search);

  currentCategory = match ? decodeURIComponent(match[1]) : 'todo';
  currentSize = urlParams.get('size') || '';

  updateActiveLinks();

  filterLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();

      const catSlug = link.getAttribute('data-category-slug');
      const sizeSlug = link.getAttribute('data-size-slug');

      if (catSlug) {
        if (catSlug === currentCategory) return;
        currentCategory = catSlug;
      } else if (sizeSlug) {
        currentSize = sizeSlug === currentSize ? '' : sizeSlug;
      }

      updateActiveLinks();
      loadProducts();

      let newUrl = !currentCategory || currentCategory === 'todo'
        ? '/'
        : `/collections/${encodeURIComponent(currentCategory)}/`;

      if (currentSize) {
        newUrl += `?size=${encodeURIComponent(currentSize)}`;
      }

      window.history.pushState({ category: currentCategory, size: currentSize }, '', newUrl);
    });
  });

  window.addEventListener('popstate', () => {
    window.location.reload();
  });
});
