/* Present WooCommerce's successful result without duplicating its cart logic. */
jQuery(() => {
  jQuery(document.body).on('wc_cart_button_updated.palanciaCatalog', (event, $button) => {
    const button = $button?.[0];
    const action = button?.closest('.home-products-grid .product-card-action');
    const viewCart = action?.querySelector('a.added_to_cart');
    if (!button?.classList.contains('added') || !viewCart) return;

    // Keep keyboard focus in the card before hiding the completed action.
    if (document.activeElement === button) viewCart.focus();
    button.hidden = true;
  });
});
