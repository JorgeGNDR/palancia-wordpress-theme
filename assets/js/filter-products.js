document.addEventListener('DOMContentLoaded', () => {
  const filterLinks = document.querySelectorAll('.product-filters a, .mobile-filter-menu a');
  const productsGrid = document.querySelector('.home-products-grid');
  const sizeButtons = document.querySelectorAll('[data-size-slug]');
  const stockButtons = document.querySelectorAll('[data-stock-filter]');
  const clearButtons = document.querySelectorAll('.product-size-clear');
  const applyButtons = document.querySelectorAll('.product-filter-apply');
  const filterTriggers = document.querySelectorAll('.product-filter-trigger');
  const filterModal = document.getElementById('product-size-filter-modal');
  const filterCloseControls = document.querySelectorAll('[data-filter-close]');

  const mobileBtn = document.getElementById('mobile-filter-toggle');
  const mobileMenu = document.getElementById('mobile-filter-menu');
  let resetDraftFilters = () => {};

  const openFilterModal = () => {
    if (!filterModal) return;
    resetDraftFilters();
    filterModal.classList.add('is-open');
    filterModal.setAttribute('aria-hidden', 'false');
    filterTriggers.forEach((trigger) => trigger.setAttribute('aria-expanded', 'true'));
    document.body.classList.add('product-filter-open');
  };

  const closeFilterModal = () => {
    if (!filterModal) return;
    filterModal.classList.remove('is-open');
    filterModal.setAttribute('aria-hidden', 'true');
    filterTriggers.forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'));
    document.body.classList.remove('product-filter-open');
  };

  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      mobileMenu.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
      if (
        !mobileMenu.contains(e.target)
        && !mobileBtn.contains(e.target)
        && !filterModal?.contains(e.target)
      ) {
        mobileMenu.classList.remove('open');
      }
    });

    mobileMenu.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        mobileMenu.classList.remove('open');
      });
    });
  }

  filterTriggers.forEach((trigger) => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      openFilterModal();
    });
  });

  filterCloseControls.forEach((control) => {
    control.addEventListener('click', closeFilterModal);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeFilterModal();
    }
  });

  if (!filterLinks.length || !productsGrid || typeof prsFilterProducts === 'undefined') {
    return;
  }

  const { ajaxUrl } = prsFilterProducts;
  let currentCategory = '';
  let appliedSizes = new Set();
  let draftSizes = new Set();
  let appliedStock = 'show';
  let draftStock = 'show';
  let activeRequest = null;

  const revealGrid = () => {
    closeFilterModal();
    if (window.matchMedia('(max-width: 768px)').matches) {
      mobileMenu?.classList.remove('open');
    }
  };

  const updateActiveLinks = () => {
    filterLinks.forEach((link) => {
      const catSlug = link.getAttribute('data-category-slug');

      link.classList.remove('is-active');

      if (catSlug && catSlug === currentCategory) {
        link.classList.add('is-active');
      }
    });

    sizeButtons.forEach((button) => {
      const sizeSlug = button.getAttribute('data-size-slug');
      button.classList.toggle('is-active', sizeSlug && draftSizes.has(sizeSlug));
    });

    stockButtons.forEach((button) => {
      button.classList.toggle('is-active', button.getAttribute('data-stock-filter') === draftStock);
    });
  };

  resetDraftFilters = () => {
    draftSizes = new Set(appliedSizes);
    draftStock = appliedStock;
    updateActiveLinks();
  };

  const loadProducts = () => {
    if (activeRequest) {
      activeRequest.abort();
    }

    const request = new AbortController();
    activeRequest = request;
    productsGrid.classList.add('is-loading');

    const body = new URLSearchParams({
      action: 'prs_filter_products',
      category_slug: currentCategory === 'todo' ? '' : currentCategory,
      size_slugs: [...appliedSizes].join(','),
      stock_filter: appliedStock,
    });

    fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body,
      signal: request.signal,
    })
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
      })
      .then((response) => {
        const html = response?.success ? response.data?.html : '';
        if (!html) throw new Error('Respuesta de filtro no válida');
        productsGrid.innerHTML = html;
      })
      .catch((err) => {
        if (err.name === 'AbortError') return;
        console.error('Error filtrando productos:', err);
        productsGrid.innerHTML = '<p class="prs-products-empty">No se han podido cargar los productos. Inténtalo de nuevo.</p>';
      })
      .finally(() => {
        if (activeRequest === request) {
          productsGrid.classList.remove('is-loading');
          activeRequest = null;
        }
      });
  };

  const updateUrl = () => {
    const params = new URLSearchParams();
    if (appliedSizes.size) {
      params.set('size', [...appliedSizes].join(','));
    }
    if (appliedStock === 'hide') {
      params.set('soldout', 'hide');
    }

    const baseUrl = !currentCategory || currentCategory === 'todo'
      ? '/'
      : `/collections/${encodeURIComponent(currentCategory)}/`;
    const query = params.toString();
    window.history.pushState(
      {
        category: currentCategory,
        size: [...appliedSizes],
        soldout: appliedStock,
      },
      '',
      query ? `${baseUrl}?${query}` : baseUrl
    );
  };

  const path = window.location.pathname;
  const match = path.match(/\/collections\/([^/]+)\/?/);
  const params = new URLSearchParams(window.location.search);

  currentCategory = match ? decodeURIComponent(match[1]) : 'todo';
  appliedSizes = new Set(
    (params.get('size') || '')
      .split(',')
      .map((item) => item.trim())
      .filter(Boolean)
  );
  appliedStock = params.get('soldout') === 'hide' ? 'hide' : 'show';
  draftSizes = new Set(appliedSizes);
  draftStock = appliedStock;

  updateActiveLinks();

  filterLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();

      const catSlug = link.getAttribute('data-category-slug');

      if (!catSlug || catSlug === currentCategory) return;
      currentCategory = catSlug;

      updateActiveLinks();
      loadProducts();
      updateUrl();
    });
  });

  sizeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const sizeSlug = button.getAttribute('data-size-slug');
      if (!sizeSlug) return;

      if (draftSizes.has(sizeSlug)) {
        draftSizes.delete(sizeSlug);
      } else {
        draftSizes.add(sizeSlug);
      }

      updateActiveLinks();
    });
  });

  stockButtons.forEach((button) => {
    button.addEventListener('click', () => {
      draftStock = button.getAttribute('data-stock-filter') === 'hide' ? 'hide' : 'show';
      updateActiveLinks();
    });
  });

  clearButtons.forEach((button) => {
    button.addEventListener('click', () => {
      draftSizes.clear();
      draftStock = 'show';
      updateActiveLinks();
    });
  });

  applyButtons.forEach((button) => {
    button.addEventListener('click', () => {
      appliedSizes = new Set(draftSizes);
      appliedStock = draftStock;
      revealGrid();
      loadProducts();
      updateUrl();
    });
  });

  window.addEventListener('popstate', () => {
    window.location.reload();
  });
});
