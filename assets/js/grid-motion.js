/* Presentation only: catalog state and requests remain in filter-products.js. */
(() => {
  class GridMotion {
    constructor(grid) {
      this.grid = grid;
      this.animations = new Set();
      this.layer = null;
      this.preference = window.matchMedia('(prefers-reduced-motion: reduce)');
      this.preference.addEventListener?.('change', () => this.cancel());
      window.addEventListener('resize', () => this.cancel());
      window.addEventListener('scroll', () => this.cancel(), {passive: true});
    }
    cards() {
      return new Map([...this.grid.querySelectorAll(':scope > .product-item[data-product-id]')]
        .map(node => [node.dataset.productId, node]));
    }
    visible(rect) {
      return rect.width > 0 && rect.height > 0 && rect.bottom > 0 && rect.right > 0 && rect.top < window.innerHeight && rect.left < window.innerWidth;
    }
    cancel() {
      this.animations.forEach(animation => animation.cancel());
      this.animations.clear();
      this.layer?.remove();
      this.layer = null;
    }
    play(node, frames, options, ghost = false) {
      const animation = node.animate(frames, {duration: 280, easing: 'cubic-bezier(.2,.7,.2,1)', fill: 'both', ...options});
      this.animations.add(animation);
      const cleanup = () => {
        this.animations.delete(animation);
        animation.cancel();
        if (ghost) {
          node.remove();
          if (this.layer && !this.layer.children.length) { this.layer.remove(); this.layer = null; }
        }
      };
      animation.finished.then(cleanup, cleanup);
    }
    leave(node, rect, opacity) {
      if (!this.layer) {
        this.layer = document.createElement('div');
        this.layer.className = 'home-products-grid prs-grid-motion-layer';
        this.layer.setAttribute('aria-hidden', 'true');
        this.layer.inert = true;
        document.body.appendChild(this.layer);
      }
      const ghost = node.cloneNode(true);
      ghost.tabIndex = -1;
      ghost.removeAttribute('id');
      ghost.querySelectorAll('[id]').forEach(child => child.removeAttribute('id'));
      Object.assign(ghost.style, {position: 'absolute', left: `${rect.left}px`, top: `${rect.top}px`, width: `${rect.width}px`, height: `${rect.height}px`});
      this.layer.appendChild(ghost);
      this.play(ghost, [{opacity, transform: 'scale(1)'}, {opacity: 0, transform: 'scale(.98)'}], {duration: 140}, true);
    }
    replace(html) {
      // Read current visual positions before cancelling an interrupted transition.
      const old = this.cards();
      const before = new Map([...old].map(([id, node]) => [id, node.getBoundingClientRect()]));
      const opacity = Number(window.getComputedStyle(this.grid).opacity || '1');
      const focusId = this.grid.contains(document.activeElement) ? document.activeElement.closest('[data-product-id]')?.dataset.productId : null;
      this.cancel();
      this.grid.innerHTML = html;
      const next = this.cards();
      const after = new Map([...next].map(([id, node]) => [id, node.getBoundingClientRect()]));
      if (focusId) {
        if (next.has(focusId)) {
          const card = next.get(focusId);
          (card.querySelector('.product-image-link') || card).focus({preventScroll: true});
        }
        else { this.grid.tabIndex = -1; this.grid.focus({preventScroll: true}); }
      }
      if (this.preference.matches || typeof this.grid.animate !== 'function') return;
      for (const [id, node] of old) {
        if (this.visible(before.get(id)) && (!after.has(id) || !this.visible(after.get(id)))) this.leave(node, before.get(id), opacity);
      }
      let entering = 0;
      for (const [id, node] of next) {
        const end = after.get(id), start = before.get(id);
        if (!this.visible(end)) continue;
        if (!start || !this.visible(start)) {
          this.play(node, [{opacity: 0, transform: 'translateY(8px)'}, {opacity: 1, transform: 'translateY(0)'}], {duration: 220, delay: Math.min(entering++, 4) * 18});
        } else {
          const x = start.left - end.left, y = start.top - end.top;
          const sx = start.width / end.width, sy = start.height / end.height;
          if (Math.abs(x) + Math.abs(y) < 1 && Math.abs(sx - 1) + Math.abs(sy - 1) < .01) continue;
          this.play(node, [
            {transformOrigin: '0 0', transform: `translate(${x}px, ${y}px) scale(${sx}, ${sy})`},
            {transformOrigin: '0 0', transform: 'translate(0, 0) scale(1, 1)'},
          ]);
        }
      }
    }
  }
  window.PalanciaGridMotion = GridMotion;
})();
