<?php
/**
 * Footer
 */
?>
</main>
<footer class="site-footer">
  <div class="footer-container">
    <p>&copy; <?php echo date( 'Y' ); ?> PALANCIA. <?php esc_html_e( 'Todos los derechos reservados.', 'palancia-shop' ); ?></p>
    <nav class="footer-navigation" aria-label="<?php esc_attr_e( 'Enlaces del pie de página', 'palancia-shop' ); ?>">
      <ul>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'palancia-shop' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/cookies' ) ); ?>">Cookies</a></li>
        <li><a href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php esc_html_e( 'Contacto', 'palancia-shop' ); ?></a></li>
        <li><a href="<?php echo esc_url( home_url( '/privacy-policy' ) ); ?>"><?php esc_html_e( 'Privacidad', 'palancia-shop' ); ?></a></li>
      </ul>
    </nav>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
