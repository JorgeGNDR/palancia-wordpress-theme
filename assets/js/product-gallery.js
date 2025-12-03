document.addEventListener('DOMContentLoaded', () => {
  const gallery = document.querySelector('.prs-product-gallery');
  if (!gallery) return;

  const slides = [...gallery.querySelectorAll('.prs-product-slide')];
  if (!slides.length) return;

  let current = 0;

  const show = (i) => {
    slides[current].classList.remove('is-active');
    current = (i + slides.length) % slides.length;
    slides[current].classList.add('is-active');
  };

  const nextBtn = document.querySelector('.prs-next');
  const prevBtn = document.querySelector('.prs-prev');

  nextBtn?.addEventListener('click', (e) => {
    e.stopPropagation(); // que no dispare el zoom
    show(current + 1);
  });

  prevBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    show(current - 1);
  });

  // Asegurar primera imagen activa
  slides.forEach((s, i) => s.classList.toggle('is-active', i === 0));

  // ===== MODO ZOOM AL CLICAR EN LA IMAGEN =====

  const openZoom = () => {
    // Crear overlay
    const overlay = document.createElement('div');
    overlay.className = 'prs-zoom-overlay';

    const closeBtn = document.createElement('button');
    closeBtn.className = 'prs-zoom-close';
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

  };

  // Abrir zoom al hacer clic en la imagen
  gallery.addEventListener('click', (e) => {
    if (e.target.tagName.toLowerCase() === 'img') {
      openZoom();
    }
  });
});
