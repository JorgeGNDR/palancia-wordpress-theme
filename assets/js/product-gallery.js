document.addEventListener('DOMContentLoaded', () => {
  const gallery = document.querySelector('.prs-product-gallery');
  if (!gallery) return;

  const slides = [...gallery.querySelectorAll('.prs-product-slide')];
  if (!slides.length) return;

  const dots = [...gallery.parentElement.querySelectorAll('.prs-product-dot')];
  let current = 0;
  let activeScale = 1;
  let activeTranslate = { x: 0, y: 0 };

  const getDistance = (t1, t2) => {
    const dx = t2.clientX - t1.clientX;
    const dy = t2.clientY - t1.clientY;
    return Math.hypot(dx, dy);
  };

  const resetActiveImage = () => {
    const img = slides[current]?.querySelector('img');
    if (img) {
      img.style.transform = '';
      img.style.transition = '';
    }
    activeScale = 1;
    activeTranslate = { x: 0, y: 0 };
  };

  const show = (i) => {
    slides[current].classList.remove('is-active');
    resetActiveImage();
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

  document.querySelector('.prs-next')?.addEventListener('click', (e) => {
    e.stopPropagation();
    animateHorizontal('left', current + 1);
  });

  document.querySelector('.prs-prev')?.addEventListener('click', (e) => {
    e.stopPropagation();
    animateHorizontal('right', current - 1);
  });

  slides.forEach((slide, index) => slide.classList.toggle('is-active', index === 0));
  dots.forEach((dot, index) => {
    dot.addEventListener('click', () => show(index));
  });

  const openZoom = () => {
    const activeImg = slides[current]?.querySelector('img');
    if (!activeImg) return;

    const overlay = document.createElement('div');
    overlay.className = 'prs-zoom-overlay';

    const closeBtn = document.createElement('button');
    closeBtn.className = 'prs-zoom-close';
    closeBtn.type = 'button';
    closeBtn.innerHTML = '&times;';

    const inner = document.createElement('div');
    inner.className = 'prs-zoom-inner';

    const clone = activeImg.cloneNode();
    clone.removeAttribute('srcset');
    clone.removeAttribute('sizes');
    inner.appendChild(clone);

    overlay.appendChild(closeBtn);
    overlay.appendChild(inner);
    document.body.appendChild(overlay);
    document.body.classList.add('prs-zoom-open');

    let zoomScale = 1;
    let zoomTranslate = { x: 0, y: 0 };
    let zoomStartDist = 0;
    let zoomStartScale = 1;
    let zoomStartMid = { x: 0, y: 0 };
    let zoomStartTranslate = { x: 0, y: 0 };

    const applyZoom = () => {
      clone.style.transform = `translate(${zoomTranslate.x}px, ${zoomTranslate.y}px) scale(${zoomScale})`;
    };

    const close = () => {
      document.body.classList.remove('prs-zoom-open');
      overlay.remove();
    };

    closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', close);
    inner.addEventListener('click', (e) => e.stopPropagation());

    inner.addEventListener(
      'touchstart',
      (e) => {
        if (e.touches.length !== 2) return;
        e.preventDefault();
        zoomStartDist = getDistance(e.touches[0], e.touches[1]);
        zoomStartScale = zoomScale;
        zoomStartMid = {
          x: (e.touches[0].clientX + e.touches[1].clientX) / 2,
          y: (e.touches[0].clientY + e.touches[1].clientY) / 2,
        };
        zoomStartTranslate = { ...zoomTranslate };
      },
      { passive: false }
    );

    inner.addEventListener(
      'touchmove',
      (e) => {
        if (e.touches.length !== 2 || !zoomStartDist) return;
        e.preventDefault();
        const dist = getDistance(e.touches[0], e.touches[1]);
        const mid = {
          x: (e.touches[0].clientX + e.touches[1].clientX) / 2,
          y: (e.touches[0].clientY + e.touches[1].clientY) / 2,
        };

        zoomScale = Math.min(4, Math.max(1, (zoomStartScale * dist) / zoomStartDist));
        zoomTranslate = {
          x: zoomStartTranslate.x + mid.x - zoomStartMid.x,
          y: zoomStartTranslate.y + mid.y - zoomStartMid.y,
        };
        applyZoom();
      },
      { passive: false }
    );

    inner.addEventListener('touchend', () => {
      zoomStartDist = 0;
      if (zoomScale < 1.02) {
        zoomScale = 1;
        zoomTranslate = { x: 0, y: 0 };
        applyZoom();
      }
    });
  };

  gallery.addEventListener('click', (e) => {
    if (e.target?.tagName?.toLowerCase() === 'img') {
      openZoom();
    }
  });

  let startX = 0;
  let startY = 0;
  let isPinching = false;
  let pinchStartDist = 0;
  let pinchStartScale = 1;
  let pinchStartMid = { x: 0, y: 0 };
  let pinchStartTranslate = { x: 0, y: 0 };
  let pinchImg = null;
  let recentPinch = false;
  const swipeThreshold = 50;

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
      const mid = {
        x: (e.touches[0].clientX + e.touches[1].clientX) / 2,
        y: (e.touches[0].clientY + e.touches[1].clientY) / 2,
      };

      activeScale = Math.min(2.6, Math.max(1, (pinchStartScale * dist) / pinchStartDist));
      activeTranslate = {
        x: pinchStartTranslate.x + mid.x - pinchStartMid.x,
        y: pinchStartTranslate.y + mid.y - pinchStartMid.y,
      };

      if (pinchImg) {
        pinchImg.style.transform = `translate(${activeTranslate.x}px, ${activeTranslate.y}px) scale(${activeScale})`;
      }
    },
    { passive: false }
  );

  gallery.addEventListener('touchend', (e) => {
    if (isPinching && e.touches.length < 2) {
      isPinching = false;
      recentPinch = true;
      window.setTimeout(() => {
        recentPinch = false;
      }, 300);
      return;
    }
    if (!e.changedTouches.length || isPinching || recentPinch || activeScale > 1.02) return;

    const endX = e.changedTouches[0].clientX;
    const endY = e.changedTouches[0].clientY;
    const dx = endX - startX;
    const dy = endY - startY;

    if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > swipeThreshold) {
      animateHorizontal(dx < 0 ? 'left' : 'right', dx < 0 ? current + 1 : current - 1);
    }
  });

  gallery.addEventListener('touchcancel', () => {
    isPinching = false;
  });
});
