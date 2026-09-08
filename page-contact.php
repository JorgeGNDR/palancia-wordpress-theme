<?php
/**
 * Contact page template.
 */

get_header();

if ( have_posts() ) {
    the_post();
}

$contact_status = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : '';
$contact_email  = sanitize_email( get_option( 'admin_email' ) );
?>

<section class="prs-contact">
  <div class="prs-contact-inner">
    <header class="prs-contact-hero">
      <div class="prs-contact-kicker">
        <span>PALANCIA / 01</span>
        <span><?php echo esc_html__( 'Atención al cliente', 'palancia-shop' ); ?></span>
      </div>
      <h1 class="prs-contact-title"><?php echo esc_html__( 'Hablemos.', 'palancia-shop' ); ?></h1>
      <p class="prs-contact-intro"><?php echo esc_html__( 'Producto, pedidos, envíos o devoluciones. Escríbenos con toda la información y te responderemos lo antes posible.', 'palancia-shop' ); ?></p>
    </header>

    <div class="prs-contact-layout">
      <aside class="prs-contact-aside">
        <div class="prs-contact-aside-index" aria-hidden="true">CONTACT / SUPPORT</div>

        <dl class="prs-contact-details">
          <div>
            <dt><?php echo esc_html__( 'Respuesta', 'palancia-shop' ); ?></dt>
            <dd><?php echo esc_html__( '24-48 horas laborables', 'palancia-shop' ); ?></dd>
          </div>
          <div>
            <dt><?php echo esc_html__( 'Consultas', 'palancia-shop' ); ?></dt>
            <dd><?php echo esc_html__( 'Pedidos / producto / devoluciones', 'palancia-shop' ); ?></dd>
          </div>
          <?php if ( $contact_email ) : ?>
            <div>
              <dt>Email</dt>
              <dd><a href="mailto:<?php echo esc_attr( $contact_email ); ?>"><?php echo esc_html( $contact_email ); ?></a></dd>
            </div>
          <?php endif; ?>
        </dl>

        <?php if ( trim( get_the_content() ) ) : ?>
          <div class="prs-contact-content">
            <?php the_content(); ?>
          </div>
        <?php endif; ?>
      </aside>

      <div class="prs-contact-main">
        <?php if ( $contact_status ) : ?>
          <?php if ( 'success' === $contact_status ) : ?>
            <div class="prs-contact-notice is-success" role="status"><?php echo esc_html__( 'Mensaje enviado. Te responderemos pronto.', 'palancia-shop' ); ?></div>
          <?php else : ?>
            <div class="prs-contact-notice is-error" role="alert"><?php echo esc_html__( 'No se pudo enviar el mensaje. Revisa los datos e inténtalo de nuevo.', 'palancia-shop' ); ?></div>
          <?php endif; ?>
        <?php endif; ?>

        <form class="prs-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
          <input type="hidden" name="action" value="prs_contact">
          <?php wp_nonce_field( 'prs_contact_form', 'prs_contact_nonce' ); ?>

          <div class="prs-contact-hp" aria-hidden="true">
            <label for="prs-contact-website">Website</label>
            <input id="prs-contact-website" type="text" name="website" tabindex="-1" autocomplete="off">
          </div>

          <label class="prs-contact-field" for="prs-contact-name">
            <span class="prs-contact-field-index">01</span>
            <span class="prs-contact-field-body">
              <span class="prs-contact-label"><?php echo esc_html__( 'Nombre', 'palancia-shop' ); ?></span>
              <input id="prs-contact-name" type="text" name="name" autocomplete="name" required>
            </span>
          </label>

          <label class="prs-contact-field" for="prs-contact-email">
            <span class="prs-contact-field-index">02</span>
            <span class="prs-contact-field-body">
              <span class="prs-contact-label">Email</span>
              <input id="prs-contact-email" type="email" name="email" autocomplete="email" inputmode="email" required>
            </span>
          </label>

          <label class="prs-contact-field" for="prs-contact-subject">
            <span class="prs-contact-field-index">03</span>
            <span class="prs-contact-field-body">
              <span class="prs-contact-label"><?php echo esc_html__( 'Asunto', 'palancia-shop' ); ?></span>
              <select id="prs-contact-subject" name="subject" required>
                <option value="product"><?php echo esc_html__( 'Producto', 'palancia-shop' ); ?></option>
                <option value="order"><?php echo esc_html__( 'Pedido', 'palancia-shop' ); ?></option>
                <option value="shipping"><?php echo esc_html__( 'Envío', 'palancia-shop' ); ?></option>
                <option value="return"><?php echo esc_html__( 'Devolución', 'palancia-shop' ); ?></option>
                <option value="other"><?php echo esc_html__( 'Otro', 'palancia-shop' ); ?></option>
              </select>
            </span>
          </label>

          <label class="prs-contact-field" for="prs-contact-order">
            <span class="prs-contact-field-index">04</span>
            <span class="prs-contact-field-body">
              <span class="prs-contact-label"><?php echo esc_html__( 'Número de pedido / opcional', 'palancia-shop' ); ?></span>
              <input id="prs-contact-order" type="text" name="order_number" autocomplete="off">
            </span>
          </label>

          <label class="prs-contact-field is-message" for="prs-contact-message">
            <span class="prs-contact-field-index">05</span>
            <span class="prs-contact-field-body">
              <span class="prs-contact-label"><?php echo esc_html__( 'Mensaje', 'palancia-shop' ); ?></span>
              <textarea id="prs-contact-message" name="message" rows="7" required></textarea>
            </span>
          </label>

          <button type="submit" class="prs-contact-submit">
            <span><?php echo esc_html__( 'Enviar mensaje', 'palancia-shop' ); ?></span>
            <span aria-hidden="true">&rarr;</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php
get_footer();
