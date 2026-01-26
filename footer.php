<?php
/**
 * Footer
 */
?>
</main>
<footer class="site-footer">
  <div class="footer-container">
    <p>&copy; <?php echo date( 'Y' ); ?> PALANCIA. All rights reserved.</p>
    <nav class="footer-navigation">
      <ul>
        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
        <li><a href="<?php echo esc_url( home_url( '/cookies' ) ); ?>">Cookies</a></li>
        <li><a href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact</a></li>
        <li><a href="<?php echo esc_url( home_url( '/privacy-policy' ) ); ?>">Privacy Policy</a></li>
      </ul>
    </nav>
  </div>
<?php wp_footer(); ?>
</body>
</html>
