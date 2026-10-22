/* Overlay lifecycle shared by all shop panels. */
(() => {
  const visible = (node) => node && node.isConnected && node.getClientRects().length > 0;
  const focusable = (root) => [...root.querySelectorAll('a[href], button, input, select, textarea, [tabindex]')]
    .filter((node) => !node.disabled && node.tabIndex >= 0 && visible(node) && !node.closest('[inert]'));
  class Panels {
    constructor() {
      this.panels = new Map(); this.stack = []; this.inertState = new Map();
      document.addEventListener('keydown', (event) => this.onKey(event));
      document.addEventListener('click', (event) => {
        const panel = this.top();
        if (!panel || !panel.outsideClose) return;
        const trigger = panel.triggers.some((node) => node.contains(event.target));
        const inside = panel.root.contains(event.target);
        const empty = inside && !event.target.closest('a, button, input, select, summary, textarea');
        if ((!inside && !trigger) || empty) this.close(panel.id);
      }, {capture: true});
    }
    top() { return this.panels.get(this.stack.at(-1)); }
    register(id, root, triggers, options = {}) {
      if (!root) return;
      const panel = {id, root, triggers: [...triggers], openClass: 'is-open', ...options};
      this.panels.set(id, panel);
      panel.triggers.forEach((trigger) => trigger.addEventListener('click', () => {
        this.stack.includes(id) ? this.close(id) : this.open(id, trigger);
      }));
      root.querySelectorAll('[data-panel-close]').forEach((control) => control.addEventListener('click', () => this.close(id)));
      this.render();
    }
    open(id, opener) {
      const panel = this.panels.get(id);
      if (!panel || this.stack.includes(id)) return;
      panel.root.dispatchEvent(new CustomEvent('panel:beforeopen'));
      panel.opener = opener || document.activeElement;
      this.stack.push(id); this.render();
      const selected = panel.root.querySelector('[aria-pressed="true"], .is-active');
      (visible(selected) && selected.matches('button, a') ? selected : focusable(panel.root)[0])?.focus();
    }
    close(id) {
      const index = this.stack.indexOf(id);
      if (index < 0) return;
      const panel = this.panels.get(id);
      const removed = this.stack.splice(index); this.render();
      const fallback = this.top();
      (visible(panel.opener) ? panel.opener : fallback ? focusable(fallback.root)[0] : panel.triggers.find(visible))?.focus();
      removed.forEach((key) => this.panels.get(key).onClose?.());
    }
    closeAll() { if (this.stack.length) this.close(this.stack[0]); }
    render() {
      this.inertState.forEach((value, node) => { node.inert = value; }); this.inertState.clear();
      this.panels.forEach((panel, id) => {
        const open = this.stack.includes(id);
        panel.root.style.setProperty('--panel-depth', String(Math.max(0, this.stack.indexOf(id))));
        panel.root.classList.toggle(panel.openClass, open);
        panel.root.setAttribute('aria-hidden', String(!open));
        if (panel.bodyClass) document.body.classList.toggle(panel.bodyClass, open);
        panel.triggers.forEach((trigger) => {
          trigger.setAttribute('aria-expanded', String(open));
          if (panel.toggleLabels) trigger.setAttribute('aria-label', panel.toggleLabels[Number(open)]);
        });
      });
      document.body.classList.toggle('prs-panel-open', this.stack.length > 0);
      const top = this.top(); if (!top) return;
      const allowed = [top.root, ...(top.extraRoots || [])].filter(Boolean);
      const visit = (parent) => [...parent.children].forEach((node) => {
        if (allowed.includes(node) || node.matches('script, style, link')) return;
        if (allowed.some((root) => node.contains(root))) visit(node);
        else { this.inertState.set(node, node.inert); node.inert = true; }
      });
      visit(document.body);
    }
    onKey(event) {
      const panel = this.top(); if (!panel) return;
      if (event.key === 'Escape') { event.preventDefault(); this.close(panel.id); }
      else if (event.key === 'Tab') {
        const controls = [...focusable(panel.root), ...(panel.extraRoots || []).flatMap(focusable)];
        const first = controls[0], last = controls.at(-1); if (!first) return;
        if (!controls.includes(document.activeElement) || (event.shiftKey && document.activeElement === first)) {
          event.preventDefault(); (event.shiftKey ? last : first).focus();
        } else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      }
    }
  }
  window.PalanciaPanels = Panels;
})();
