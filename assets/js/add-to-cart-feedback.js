(() => {
  const buttons = document.querySelectorAll('.single_add_to_cart_button');
  if (!buttons.length) return;

  const resetAfter = 1200;

  const trigger = (button) => {
    button.classList.add('is-added');
    window.clearTimeout(button._prsAddTimer);
    button._prsAddTimer = window.setTimeout(() => {
      button.classList.remove('is-added');
    }, resetAfter);
  };

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      if (button.disabled || button.getAttribute('aria-disabled') === 'true') {
        return;
      }
      trigger(button);
    });
  });
})();