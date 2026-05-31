document.addEventListener('DOMContentLoaded', () => {
  const gallery = document.querySelector('.prs-product-gallery');
  if (!gallery) return;

  const slides = [...gallery.querySelectorAll('.prs-product-slide')];
  if (!slides.length) return;

  const dots = [...gallery.parentElement.querySelectorAll('.prs-product-dot')];
  let current = 0;

  const show = (i) => {
    slides[current].classList.remove('is-active');
    const prevImg = slides[current].querySelector('img');
    if (prevImg) {
      prevImg.style.transform = '';
      prevImg.style.transition = '';
    }
    activeScale = 1;
    activeTranslate = { x: 0, y: 0 };
    current = (i + slides.length) % slides.length;
    slides[current].classList.add('is-active');
    dots.forEach((dot, idx) => dot.classList.toggle('is-active', idx === current));
  };

  const animateHorizontal = (direction, nextIndex) => {
    const outgoing = slides[current];
    const className = direction === 'left' ? 'is-swipe-left' : 'is-swipe-right';
    outgoing.classList.add(className);
    window.setTimeout(() => {
      show(nextIndex);
      outgoing.classList.remove(className);
    }, 160);
  };

  const nextBtn = document.querySelector('.prs-next');
  const prevBtn = document.querySelector('.prs-prev');

  nextBtn?.addEventListener('click', (e) => {
    e.stopPropagation(); // que no dispare el zoom
    animateHorizontal('left', current + 1);
  });

  prevBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    animateHorizontal('right', current - 1);
  });

  // Asegurar primera imagen activa
  slides.forEach((s, i) => s.classList.toggle('is-active', i === 0));
  dots.forEach((dot, idx) => {
    dot.addEventListener('click', () => show(idx));
  });

  // ===== MODO ZOOM AL CLICAR EN LA IMAGEN =====

  const canOpenZoom = window.matchMedia('(pointer: fine)').matches;

  const openZoom = () => {
    // Crear overlay
    const overlay = document.createElement('div');
    overlay.className = 'prs-zoom-overlay';

    const closeBtn = document.createElement('button');
    closeBtn.className = 'prs-zoom-close';
    closeBtn.type = 'button';
    closeBtn.innerHTML = '&times;';

    const inner = document.createElement('div');
    inner.className = 'prs-zoom-inner';

    // Clonar todas las imágenes de la galería
    slides.forEach(slide => {
      const img = slide.querySelector('img');
      if (!img) return;
      const clone = img.cloneNode();
      // Opcional: limpiar srcset/sizes para evitar rarezas
      clone.removeAttribute('srcset');
      clone.removeAttribute('sizes');
      inner.appendChild(clone);
    });

    overlay.appendChild(closeBtn);
    overlay.appendChild(inner);
    document.body.appendChild(overlay);
    document.body.classList.add('prs-zoom-open');

    const close = () => {
      document.body.classList.remove('prs-zoom-open');
      overlay.remove();
    };

    // Cerrar al clicar en la X
    closeBtn.addEventListener('click', close);
    
    // Cerrar al clicar en cualquier parte de la pantalla de zoom
    overlay.addEventListener('click', close);
    inner.addEventListener('click', (e) => {
      e.stopPropagation();
    });

  };

  // Abrir zoom al hacer clic en la imagen
  gallery.addEventListener('click', (e) => {
    if (!canOpenZoom) return;
    if (e.target.tagName.toLowerCase() === 'img') {
      openZoom();
    }
  });

  // ===== SWIPE + PINCH (MÓVIL) =====
  let startX = 0;
  let startY = 0;
  let isPinching = false;
  let pinchStartDist = 0;
  let pinchStartScale = 1;
  let pinchStartMid = { x: 0, y: 0 };
  let pinchStartTranslate = { x: 0, y: 0 };
  let activeScale = 1;
  let activeTranslate = { x: 0, y: 0 };
  let pinchImg = null;
  let recentPinch = false;
  const swipeThreshold = 50;

  const getDistance = (t1, t2) => {
    const dx = t2.clientX - t1.clientX;
    const dy = t2.clientY - t1.clientY;
    return Math.hypot(dx, dy);
  };

  const resetPinch = () => {
    if (!pinchImg) return;
    pinchImg.style.transition = 'transform 0.2s ease';
    pinchImg.style.transform = 'translate(0px, 0px) scale(1)';
    activeScale = 1;
    activeTranslate = { x: 0, y: 0 };
    window.setTimeout(() => {
      if (pinchImg) {
        pinchImg.style.transition = '';
      }
    }, 220);
  };

  gallery.addEventListener(
    'touchstart',
    (e) => {
      if (e.touches.length === 2) {
        isPinching = true;
        pinchStartDist = getDistance(e.touches[0], e.touches[1]);
        pinchStartScale = activeScale;
        pinchStartMid = {
          x: (e.touches[0].clientX + e.touches[1].clientX) / 2,
          y: (e.touches[0].clientY + e.touches[1].clientY) / 2,
        };
        pinchStartTranslate = { ...activeTranslate };
        pinchImg = slides[current].querySelector('img');
      } else if (e.touches.length === 1) {
        isPinching = false;
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
      }
    },
    { passive: true }
  );

  gallery.addEventListener(
    'touchmove',
    (e) => {
      if (!isPinching || e.touches.length !== 2) return;
      e.preventDefault();
      const dist = getDistance(e.touches[0], e.touches[1]);
      let scale = (pinchStartScale * dist) / pinchStartDist;
      if (scale < 1) scale = 1;
      if (scale > 2.6) scale = 2.6;
      const mid = {
        x: (e.touches[0].clientX + e.touches[1].clientX) / 2,
        y: (e.touches[0].clientY + e.touches[1].clientY) / 2,
      };
      const dx = mid.x - pinchStartMid.x;
      const dy = mid.y - pinchStartMid.y;
      const translateX = pinchStartTranslate.x + dx;
      const translateY = pinchStartTranslate.y + dy;
      if (pinchImg) {
        pinchImg.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
      }
      activeScale = scale;
      activeTranslate = { x: translateX, y: translateY };
    },
    { passive: false }
  );

  const navigateWithSwipe = (direction) => {
    const nextUrl = gallery.dataset.nextUrl;
    const prevUrl = gallery.dataset.prevUrl;
    const url = direction === 'up' ? nextUrl : prevUrl;
    if (!url) return;
    gallery.classList.add(direction === 'up' ? 'is-swipe-up' : 'is-swipe-down');
    window.setTimeout(() => {
      window.location.href = url;
    }, 180);
  };

  gallery.addEventListener('touchend', (e) => {
    if (isPinching && e.touches.length < 2) {
      resetPinch();
      isPinching = false;
      recentPinch = true;
      window.setTimeout(() => {
        recentPinch = false;
      }, 300);
      return;
    }
    if (!e.changedTouches.length || isPinching || recentPinch) return;

    const endX = e.changedTouches[0].clientX;
    const endY = e.changedTouches[0].clientY;
    const dx = endX - startX;
    const dy = endY - startY;

    if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > swipeThreshold) {
      if (dx < 0) {
        animateHorizontal('left', current + 1);
      } else {
        animateHorizontal('right', current - 1);
      }
      return;
    }

    if (Math.abs(dy) > Math.abs(dx) && Math.abs(dy) > swipeThreshold) {
      if (dy < 0) {
        navigateWithSwipe('up');
      } else if (dy > 0) {
        navigateWithSwipe('down');
      }
    }
  });

  gallery.addEventListener('touchcancel', () => {
    if (isPinching) {
      resetPinch();
      isPinching = false;
    }
  });
});
