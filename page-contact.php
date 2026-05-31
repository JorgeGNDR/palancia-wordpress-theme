<?php
/**
 * Contact page template
 */

get_header();

if ( have_posts() ) {
	the_post();
}
?>

<section class="prs-contact">
  <div class="prs-contact-inner">
    <h1 class="prs-contact-title"><?php the_title(); ?></h1>
    <p class="prs-contact-intro">Escríbenos y te respondemos lo antes posible.</p>

    <?php if ( get_the_content() ) : ?>
      <div class="prs-contact-content">
        <?php the_content(); ?>
      </div>
    <?php endif; ?>

    <?php if ( isset( $_GET['contact'] ) ) : ?>
      <?php if ( 'success' === $_GET['contact'] ) : ?>
        <div class="prs-contact-notice is-success">Mensaje enviado. Gracias.</div>
      <?php else : ?>
        <div class="prs-contact-notice is-error">No se pudo enviar. Inténtalo de nuevo.</div>
      <?php endif; ?>
    <?php endif; ?>

    <form class="prs-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
      <input type="hidden" name="action" value="prs_contact">
      <?php wp_nonce_field( 'prs_contact_form', 'prs_contact_nonce' ); ?>

      <label>
        Nombre
        <input type="text" name="name" required>
      </label>

      <label>
        Email
        <input type="email" name="email" required>
      </label>

      <label>
        Mensaje
        <textarea name="message" rows="5" required></textarea>
      </label>

      <button type="submit" class="prs-contact-submit">Enviar</button>
    </form>
  </div>
</section>

<?php
get_footer();
