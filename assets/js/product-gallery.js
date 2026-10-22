document.addEventListener('DOMContentLoaded', () => {
  const gallery = document.querySelector('.prs-product-gallery');
  if (!gallery) return;
  const slides = [...gallery.querySelectorAll('.prs-product-slide')];
  if (!slides.length) return;
  const panels = new window.PalanciaPanels();
  const dots = [...gallery.parentElement.querySelectorAll('.prs-product-dot')];
  let current = 0;
  let galleryTouch = null;
  let skipGalleryClick = false;

  const show = (index) => {
    current = (index + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      slide.classList.toggle('is-active', i === current);
      slide.setAttribute('aria-hidden', String(i !== current));
      const source = slide.querySelector('img');
      if (source) source.tabIndex = i === current ? 0 : -1;
    });
    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === current));
  };
  gallery.querySelector('.prs-next')?.addEventListener('click', () => show(current + 1));
  gallery.querySelector('.prs-prev')?.addEventListener('click', () => show(current - 1));
  dots.forEach((dot, i) => dot.addEventListener('click', () => show(i)));
  show(0);

  // One viewer and one lifecycle registration per product, on every viewport.
  const overlay = document.createElement('div');
  overlay.className = 'prs-zoom-overlay';
  overlay.setAttribute('role', 'dialog');
  overlay.setAttribute('aria-modal', 'true');
  overlay.setAttribute('aria-label', 'Galería del producto');
  overlay.innerHTML = `
    <button type="button" class="product-filter-close prs-zoom-close" data-panel-close>Cerrar</button>
    <button type="button" class="prs-zoom-stage" aria-pressed="false" aria-label="Ampliar imagen"><img alt="" draggable="false"></button>
    <div class="prs-zoom-navigation">
      <button type="button" class="prs-zoom-prev" aria-label="Imagen anterior">&#8592;</button>
      <span class="prs-zoom-counter" aria-live="polite" aria-atomic="true"></span>
      <button type="button" class="prs-zoom-next" aria-label="Imagen siguiente">&#8594;</button>
    </div>`;
  document.body.appendChild(overlay);
  const stage = overlay.querySelector('.prs-zoom-stage');
  const image = stage.querySelector('img');
  const counter = overlay.querySelector('.prs-zoom-counter');
  const previous = overlay.querySelector('.prs-zoom-prev');
  const next = overlay.querySelector('.prs-zoom-next');
  previous.hidden = next.hidden = slides.length === 1;
  let zoomed = false;
  let gesture = null;
  let lastPoint = null;
  const zoomScale = 2.5;
  stage.style.setProperty('--zoom-scale', String(zoomScale));

  const pointZoom = (x, y) => {
    lastPoint = {x, y};
    const rect = stage.getBoundingClientRect();
    if (!rect.width || !rect.height || !image.naturalWidth || !image.naturalHeight) return;
    // Bound panning to the photo itself, excluding the object-fit letterboxing.
    const fit = Math.min(rect.width / image.naturalWidth, rect.height / image.naturalHeight);
    const maxX = Math.max(0, (image.naturalWidth * fit * zoomScale - rect.width) / 2);
    const maxY = Math.max(0, (image.naturalHeight * fit * zoomScale - rect.height) / 2);
    const clamp = (n) => Math.max(0, Math.min(1, n));
    image.style.setProperty('--zoom-x', `${(0.5 - clamp((x - rect.left) / rect.width)) * 2 * maxX}px`);
    image.style.setProperty('--zoom-y', `${(0.5 - clamp((y - rect.top) / rect.height)) * 2 * maxY}px`);
  };
  const setZoom = (value) => {
    zoomed = value;
    stage.classList.toggle('is-zoomed', value);
    stage.setAttribute('aria-pressed', String(value));
    stage.setAttribute('aria-label', value ? 'Quitar zoom' : 'Ampliar imagen');
    if (!value) {
      lastPoint = null;
      image.style.removeProperty('--zoom-x');
      image.style.removeProperty('--zoom-y');
    }
  };
  image.addEventListener('load', () => { if (zoomed && lastPoint) pointZoom(lastPoint.x, lastPoint.y); });
  const render = (index) => {
    show(index);
    setZoom(false);
    gesture = null;
    const source = slides[current].querySelector('img');
    image.src = source.dataset.fullSrc || source.currentSrc || source.src;
    image.alt = source.alt;
    counter.textContent = `${current + 1} / ${slides.length}`;
  };
  panels.register('gallery', overlay, [], {
    bodyClass: 'prs-zoom-open',
    onClose: () => {
      setZoom(false);
      gesture = null;
      slides[current].querySelector('img')?.focus();
    },
  });
  previous.addEventListener('click', () => render(current - 1));
  next.addEventListener('click', () => render(current + 1));
  overlay.addEventListener('keydown', (event) => {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
    event.preventDefault();
    render(current + (event.key === 'ArrowRight' ? 1 : -1));
  });
  stage.addEventListener('click', (event) => {
    if (event.detail > 0 && gesture?.moved) return;
    setZoom(!zoomed);
    if (zoomed && event.detail > 0) pointZoom(event.clientX, event.clientY);
  });
  stage.addEventListener('pointerdown', (event) => {
    if (!event.isPrimary || event.button !== 0) return;
    gesture = {id: event.pointerId, x: event.clientX, y: event.clientY, moved: false, wasZoomed: zoomed};
    if (event.pointerType === 'touch') stage.setPointerCapture(event.pointerId);
  });
  stage.addEventListener('pointermove', (event) => {
    if (gesture?.id === event.pointerId && Math.hypot(event.clientX - gesture.x, event.clientY - gesture.y) > 10) gesture.moved = true;
    if (zoomed && (event.pointerType === 'mouse' || gesture?.id === event.pointerId)) pointZoom(event.clientX, event.clientY);
  });
  stage.addEventListener('pointerup', (event) => {
    if (gesture?.id !== event.pointerId) return;
    const {x, y, wasZoomed} = gesture;
    const dx = event.clientX - x, dy = event.clientY - y;
    if (!wasZoomed && Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
      render(current + (dx < 0 ? 1 : -1));
      gesture = {moved: true}; // The same gesture must not also toggle zoom.
    }
  });
  stage.addEventListener('pointercancel', () => { gesture = {moved: true}; });
  window.addEventListener('resize', () => setZoom(false));

  slides.forEach((slide, index) => {
    const source = slide.querySelector('img');
    if (!source) return;
    source.tabIndex = index === current ? 0 : -1;
    source.draggable = false;
    source.setAttribute('role', 'button');
    source.setAttribute('aria-haspopup', 'dialog');
    source.setAttribute('aria-label', `Abrir imagen: ${source.alt || 'producto'}`);
    source.addEventListener('click', (event) => {
      if (event.detail > 0 && skipGalleryClick) return;
      render(index);
      panels.open('gallery', source);
    });
    source.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); source.click(); }
    });
  });
  gallery.addEventListener('pointerdown', () => { skipGalleryClick = false; });
  gallery.addEventListener('touchstart', (event) => {
    skipGalleryClick = false;
    galleryTouch = event.touches.length === 1 ? {x: event.touches[0].clientX, y: event.touches[0].clientY} : null;
  }, {passive: true});
  gallery.addEventListener('touchend', (event) => {
    if (!galleryTouch || !event.changedTouches.length) return;
    const dx = event.changedTouches[0].clientX - galleryTouch.x;
    const dy = event.changedTouches[0].clientY - galleryTouch.y;
    galleryTouch = null;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) { skipGalleryClick = true; show(current + (dx < 0 ? 1 : -1)); }
  });
  gallery.addEventListener('touchcancel', () => { galleryTouch = null; });
});
