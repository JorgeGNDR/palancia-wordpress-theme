document.addEventListener('DOMContentLoaded', () => {
  const gallery = document.querySelector('.prs-product-gallery');
  if (!gallery) return;

  const slides = [...gallery.querySelectorAll('.prs-product-slide')];
  if (!slides.length) return;

  const wrapper = gallery.parentElement;
  const dots = [...wrapper.querySelectorAll('.prs-product-dot')];
  const coarsePointer = window.matchMedia('(pointer: coarse)');
  let current = 0;
  let suppressClickUntil = 0;

  const getDistance = (t1, t2) => {
    const dx = t2.clientX - t1.clientX;
    const dy = t2.clientY - t1.clientY;
    return Math.hypot(dx, dy);
  };

  const show = (i) => {
    slides[current].classList.remove('is-active');
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

  const cloneImage = (img) => {
    const clone = img.cloneNode();
    clone.removeAttribute('srcset');
    clone.removeAttribute('sizes');
    clone.removeAttribute('loading');
    return clone;
  };

  const installBoundedPinchZoom = (frame, img, markGesture) => {
    let scale = 1;
    let translate = { x: 0, y: 0 };
    let startDist = 0;
    let startScale = 1;
    let startMid = { x: 0, y: 0 };
    let startTranslate = { x: 0, y: 0 };
    let dragStart = null;

    const clampTranslate = () => {
      const frameRect = frame.getBoundingClientRect();
      const imgRect = img.getBoundingClientRect();
      const baseWidth = imgRect.width / scale;
      const baseHeight = imgRect.height / scale;
      const maxX = Math.max(0, (baseWidth * scale - frameRect.width) / 2);
      const maxY = Math.max(0, (baseHeight * scale - frameRect.height) / 2);

      translate.x = Math.min(maxX, Math.max(-maxX, translate.x));
      translate.y = Math.min(maxY, Math.max(-maxY, translate.y));
    };

    const applyZoom = () => {
      clampTranslate();
      img.style.transform = `translate(${translate.x}px, ${translate.y}px) scale(${scale})`;
    };

    frame.addEventListener(
      'touchstart',
      (e) => {
        if (e.touches.length === 1 && scale > 1.02) {
          e.preventDefault();
          e.stopPropagation();
          markGesture();
          dragStart = {
            x: e.touches[0].clientX,
            y: e.touches[0].clientY,
            translate: { ...translate },
          };
          return;
        }

        if (e.touches.length !== 2) return;
        e.preventDefault();
        e.stopPropagation();
        markGesture();
        dragStart = null;
        startDist = getDistance(e.touches[0], e.touches[1]);
        startScale = scale;
        startMid = {
          x: (e.touches[0].clientX + e.touches[1].clientX) / 2,
          y: (e.touches[0].clientY + e.touches[1].clientY) / 2,
        };
        startTranslate = { ...translate };
      },
      { passive: false }
    );

    frame.addEventListener(
      'touchmove',
      (e) => {
        if (e.touches.length === 1 && dragStart && scale > 1.02) {
          e.preventDefault();
          e.stopPropagation();
          markGesture();
          translate = {
            x: dragStart.translate.x + e.touches[0].clientX - dragStart.x,
            y: dragStart.translate.y + e.touches[0].clientY - dragStart.y,
          };
          applyZoom();
          return;
        }

        if (e.touches.length !== 2 || !startDist) return;
        e.preventDefault();
        e.stopPropagation();
        markGesture();

        const dist = getDistance(e.touches[0], e.touches[1]);
        const mid = {
          x: (e.touches[0].clientX + e.touches[1].clientX) / 2,
          y: (e.touches[0].clientY + e.touches[1].clientY) / 2,
        };

        scale = Math.min(4, Math.max(1, (startScale * dist) / startDist));
        translate = {
          x: startTranslate.x + mid.x - startMid.x,
          y: startTranslate.y + mid.y - startMid.y,
        };
        applyZoom();
      },
      { passive: false }
    );

    frame.addEventListener('touchend', () => {
      startDist = 0;
      dragStart = null;
      if (scale < 1.02) {
        scale = 1;
        translate = { x: 0, y: 0 };
      }
      applyZoom();
      markGesture();
    });
  };

  const openProductStrip = () => {
    const overlay = document.createElement('div');
    overlay.className = 'prs-zoom-overlay is-strip';

    const closeBtn = document.createElement('button');
    closeBtn.className = 'prs-zoom-close';
    closeBtn.type = 'button';
    closeBtn.innerHTML = '&times;';
    closeBtn.setAttribute('aria-label', 'Cerrar galeria');

    const strip = document.createElement('div');
    strip.className = 'prs-zoom-strip';

    let ignoreCloseUntil = 0;
    const markGesture = () => {
      ignoreCloseUntil = Date.now() + 350;
    };

    slides.forEach((slide) => {
      const img = slide.querySelector('img');
      if (!img) return;

      const frame = document.createElement('div');
      frame.className = 'prs-zoom-strip-item';
      const clone = cloneImage(img);
      frame.appendChild(clone);
      strip.appendChild(frame);
      installBoundedPinchZoom(frame, clone, markGesture);
    });

    overlay.appendChild(closeBtn);
    overlay.appendChild(strip);
    document.body.appendChild(overlay);
    document.body.classList.add('prs-zoom-open');

    const close = () => {
      document.body.classList.remove('prs-zoom-open');
      overlay.remove();
    };

    overlay.addEventListener('click', () => {
      if (Date.now() < ignoreCloseUntil) return;
      close();
    });

    closeBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      close();
    });
  };

  const openSingleImage = (index) => {
    const source = slides[index]?.querySelector('img');
    if (!source) return;

    const overlay = document.createElement('div');
    overlay.className = 'prs-zoom-overlay is-single';

    const closeBtn = document.createElement('button');
    closeBtn.className = 'prs-zoom-close';
    closeBtn.type = 'button';
    closeBtn.innerHTML = '&times;';
    closeBtn.setAttribute('aria-label', 'Cerrar imagen');

    const frame = document.createElement('div');
    frame.className = 'prs-zoom-single-frame';
    const clone = cloneImage(source);
    frame.appendChild(clone);

    let ignoreCloseUntil = 0;
    const markGesture = () => {
      ignoreCloseUntil = Date.now() + 350;
    };

    installBoundedPinchZoom(frame, clone, markGesture);
    overlay.appendChild(frame);
    overlay.appendChild(closeBtn);
    document.body.appendChild(overlay);
    document.body.classList.add('prs-zoom-open');

    const close = () => {
      document.body.classList.remove('prs-zoom-open');
      overlay.remove();
    };

    overlay.addEventListener('click', (e) => {
      if (Date.now() < ignoreCloseUntil || e.target === clone) return;
      close();
    });

    closeBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      close();
    });
  };

  gallery.addEventListener('click', (e) => {
    const img = e.target?.closest?.('img');
    if (!img) return;
    if (Date.now() < suppressClickUntil) return;

    if (coarsePointer.matches) {
      openSingleImage(current);
      return;
    }

    openProductStrip();
  });

  let startX = 0;
  let startY = 0;
  const swipeThreshold = 50;

  gallery.addEventListener(
    'touchstart',
    (e) => {
      if (e.touches.length !== 1) return;
      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
    },
    { passive: true }
  );

  gallery.addEventListener('touchend', (e) => {
    if (!e.changedTouches.length) return;

    const endX = e.changedTouches[0].clientX;
    const endY = e.changedTouches[0].clientY;
    const dx = endX - startX;
    const dy = endY - startY;

    if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > swipeThreshold) {
      suppressClickUntil = Date.now() + 400;
      animateHorizontal(dx < 0 ? 'left' : 'right', dx < 0 ? current + 1 : current - 1);
    }
  });
});
