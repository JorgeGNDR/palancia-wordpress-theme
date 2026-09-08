<?php

function prs_get_theme_version() {
    static $version = null;

    if ( null === $version ) {
        $version = wp_get_theme()->get( 'Version' );
        $version = $version ?: '0.0.0';
    }

    return $version;
}

function prs_get_asset_version( $relative_path ) {
    $absolute_path = get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' );
    $modified      = file_exists( $absolute_path ) ? filemtime( $absolute_path ) : 0;

    return prs_get_theme_version() . ( $modified ? '.' . $modified : '' );
}

function prs_output_theme_version_marker() {
    $version = prs_get_theme_version();

    echo '<meta name="palancia-theme-version" content="' . esc_attr( $version ) . '">' . "\n";
    echo '<!-- Palancia Web Shop v' . esc_html( $version ) . ' -->' . "\n";
}
add_action( 'wp_head', 'prs_output_theme_version_marker', 1 );

function prs_add_theme_version_to_admin_bar( $admin_bar ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $admin_bar->add_node( [
        'id'    => 'prs-theme-version',
        'title' => 'PALANCIA v' . prs_get_theme_version(),
        'href'  => admin_url( 'themes.php' ),
        'meta'  => [
            'title' => __( 'Version activa del tema Palancia', 'palancia-shop' ),
        ],
    ] );
}
add_action( 'admin_bar_menu', 'prs_add_theme_version_to_admin_bar', 100 );

// Cargar CSS y JS del tema
function prs_enqueue_assets() {
    // CSS principal
    wp_enqueue_style(
        'palancia-retro-shop-style',
        get_stylesheet_uri(),
        [],
        prs_get_asset_version( 'style.css' )
    );

    // Material Symbols (icons)
    wp_enqueue_style(
        'palancia-material-symbols',
        'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=menu,shopping_bag,add_shopping_cart,add&display=swap',
        [],
        null
    );

    // JS del slider solo en ficha de producto
    if ( is_product() ) {
        wp_enqueue_script(
            'prs-product-gallery',
            get_stylesheet_directory_uri() . '/assets/js/product-gallery.js',
            [],
            prs_get_asset_version( 'assets/js/product-gallery.js' ),
            true
        );

        wp_enqueue_script(
            'prs-add-to-cart-feedback',
            get_stylesheet_directory_uri() . '/assets/js/add-to-cart-feedback.js',
            [],
            prs_get_asset_version( 'assets/js/add-to-cart-feedback.js' ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'prs_enqueue_assets' );

// Encolar el script de filtrado de productos con AJAX
function prs_enqueue_filter_script() {
    if ( ! is_front_page() && ! is_tax( 'product_cat' ) ) {
        return;
    }

    wp_enqueue_script(
        'prs-filter-products',
        get_stylesheet_directory_uri() . '/assets/js/filter-products.js',
        [],
        prs_get_asset_version( 'assets/js/filter-products.js' ),
        true
    );

    wp_localize_script('prs-filter-products', 'prsFilterProducts', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'prs_enqueue_filter_script');

// Soporte de tema
function prs_setup_theme() {
    add_theme_support( 'woocommerce' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'custom-logo', [
        'height'      => 400,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ] );
}
add_action( 'after_setup_theme', 'prs_setup_theme' );

function prs_sanitize_css_size( $value ) {
    $value = trim( (string) $value );

    if ( preg_match( '/^\d+(\.\d+)?(px|rem|em|vw|vh|%)$/', $value ) ) {
        return $value;
    }

    if ( preg_match( '/^(clamp|min|max|calc)\([0-9a-zA-Z\s.,+\-*\/()%]+\)$/', $value ) ) {
        return $value;
    }

    return '';
}

function prs_sanitize_positive_int( $value ) {
    return max( 1, absint( $value ) );
}

function prs_customize_register_legacy_disabled( $wp_customize ) {
    $wp_customize->add_section( 'prs_design_settings', [
        'title'       => __( 'Palancia Design', 'palancia-shop' ),
        'priority'    => 35,
        'description' => __( 'Ajustes visuales del tema. Los valores por defecto mantienen el diseño actual.', 'palancia-shop' ),
    ] );

    $settings = [
        'prs_primary_color' => [
            'label'   => __( 'Color principal', 'palancia-shop' ),
            'default' => '#233549',
            'type'    => 'color',
        ],
        'prs_text_color' => [
            'label'   => __( 'Color de texto', 'palancia-shop' ),
            'default' => '#111111',
            'type'    => 'color',
        ],
        'prs_button_bg' => [
            'label'   => __( 'Fondo botones principales', 'palancia-shop' ),
            'default' => '#000000',
            'type'    => 'color',
        ],
        'prs_button_text' => [
            'label'   => __( 'Texto botones principales', 'palancia-shop' ),
            'default' => '#ffffff',
            'type'    => 'color',
        ],
    ];

    foreach ( $settings as $id => $setting ) {
        $wp_customize->add_setting( $id, [
            'default'           => $setting['default'],
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ] );

        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, [
            'label'   => $setting['label'],
            'section' => 'prs_design_settings',
        ] ) );
    }

    $number_settings = [
        'prs_logo_height_desktop' => [ __( 'Logo desktop alto (px)', 'palancia-shop' ), 100, 40, 160 ],
        'prs_logo_height_mobile'  => [ __( 'Logo móvil alto (px)', 'palancia-shop' ), 72, 40, 120 ],
        'prs_header_height'       => [ __( 'Header desktop alto (px)', 'palancia-shop' ), 100, 64, 180 ],
        'prs_header_height_mobile'=> [ __( 'Header móvil alto (px)', 'palancia-shop' ), 96, 64, 150 ],
        'prs_grid_desktop_cols'   => [ __( 'Columnas grid desktop', 'palancia-shop' ), 6, 3, 8 ],
        'prs_grid_tablet_cols'    => [ __( 'Columnas grid tablet', 'palancia-shop' ), 5, 2, 6 ],
        'prs_grid_mobile_cols'    => [ __( 'Columnas grid móvil', 'palancia-shop' ), 2, 1, 3 ],
    ];

    foreach ( $number_settings as $id => $args ) {
        $wp_customize->add_setting( $id, [
            'default'           => $args[1],
            'sanitize_callback' => 'prs_sanitize_positive_int',
            'transport'         => 'refresh',
        ] );

        $wp_customize->add_control( $id, [
            'label'       => $args[0],
            'section'     => 'prs_design_settings',
            'type'        => 'number',
            'input_attrs' => [
                'min'  => $args[2],
                'max'  => $args[3],
                'step' => 1,
            ],
        ] );
    }

    $size_settings = [
        'prs_nav_column_width' => [ __( 'Ancho columna navegación', 'palancia-shop' ), '12vw' ],
        'prs_nav_column_width_tablet' => [ __( 'Ancho navegación tablet', 'palancia-shop' ), '132px' ],
        'prs_side_padding'     => [ __( 'Padding lateral desktop', 'palancia-shop' ), '2.5rem' ],
        'prs_side_padding_mobile' => [ __( 'Padding lateral móvil', 'palancia-shop' ), '0.75rem' ],
        'prs_layout_gap'       => [ __( 'Separación layout', 'palancia-shop' ), '10px' ],
        'prs_product_card_max' => [ __( 'Ancho máximo tarjeta producto', 'palancia-shop' ), '100%' ],
        'prs_nav_font_size'    => [ __( 'Tamaño texto navegación', 'palancia-shop' ), '0.92rem' ],
        'prs_button_font_size' => [ __( 'Tamaño texto botones', 'palancia-shop' ), '0.8rem' ],
    ];

    foreach ( $size_settings as $id => $args ) {
        $wp_customize->add_setting( $id, [
            'default'           => $args[1],
            'sanitize_callback' => 'prs_sanitize_css_size',
            'transport'         => 'refresh',
        ] );

        $wp_customize->add_control( $id, [
            'label'   => $args[0],
            'section' => 'prs_design_settings',
            'type'    => 'text',
        ] );
    }
}
add_action( 'customize_register', 'prs_customize_register' );

function prs_customizer_css_legacy_disabled() {
    $primary     = get_theme_mod( 'prs_primary_color', '#233549' );
    $text        = get_theme_mod( 'prs_text_color', '#111111' );
    $button_bg   = get_theme_mod( 'prs_button_bg', '#000000' );
    $button_text = get_theme_mod( 'prs_button_text', '#ffffff' );

    $logo_desktop = absint( get_theme_mod( 'prs_logo_height_desktop', 100 ) );
    $logo_mobile  = absint( get_theme_mod( 'prs_logo_height_mobile', 72 ) );
    $header       = absint( get_theme_mod( 'prs_header_height', 100 ) );
    $header_mob   = absint( get_theme_mod( 'prs_header_height_mobile', 96 ) );

    $nav_width = prs_sanitize_css_size( get_theme_mod( 'prs_nav_column_width', '12vw' ) ) ?: '12vw';
    $side_pad  = prs_sanitize_css_size( get_theme_mod( 'prs_side_padding', '2.5rem' ) ) ?: '2.5rem';
    $side_pad_mobile = prs_sanitize_css_size( get_theme_mod( 'prs_side_padding_mobile', '0.75rem' ) ) ?: '0.75rem';
    $gap       = prs_sanitize_css_size( get_theme_mod( 'prs_layout_gap', '10px' ) ) ?: '10px';
    $card_max  = prs_sanitize_css_size( get_theme_mod( 'prs_product_card_max', '100%' ) ) ?: '100%';
    $nav_width_tablet = prs_sanitize_css_size( get_theme_mod( 'prs_nav_column_width_tablet', '132px' ) ) ?: '132px';
    ?>
    <style id="prs-customizer-css">
      :root {
        --primary-color: <?php echo esc_html( $primary ); ?>;
        --text-color: <?php echo esc_html( $text ); ?>;
        --button-bg: <?php echo esc_html( $button_bg ); ?>;
        --button-text: <?php echo esc_html( $button_text ); ?>;
        --header-height: <?php echo esc_html( $header ); ?>px;
        --logo-height-desktop: <?php echo esc_html( $logo_desktop ); ?>px;
        --logo-height-mobile: <?php echo esc_html( $logo_mobile ); ?>px;
        --nav-column-width: <?php echo esc_html( $nav_width ); ?>;
        --nav-column-width-tablet: <?php echo esc_html( $nav_width_tablet ); ?>;
        --side-padding: <?php echo esc_html( $side_pad ); ?>;
        --side-padding-mobile: <?php echo esc_html( $side_pad_mobile ); ?>;
        --layout-gap: <?php echo esc_html( $gap ); ?>;
        --product-card-max-width: <?php echo esc_html( $card_max ); ?>;
        --nav-font-size: <?php echo esc_html( prs_sanitize_css_size( get_theme_mod( 'prs_nav_font_size', '0.92rem' ) ) ?: '0.92rem' ); ?>;
        --button-font-size: <?php echo esc_html( prs_sanitize_css_size( get_theme_mod( 'prs_button_font_size', '0.8rem' ) ) ?: '0.8rem' ); ?>;
        --grid-desktop-columns: <?php echo esc_html( prs_sanitize_positive_int( get_theme_mod( 'prs_grid_desktop_cols', 6 ) ) ); ?>;
        --grid-tablet-columns: <?php echo esc_html( prs_sanitize_positive_int( get_theme_mod( 'prs_grid_tablet_cols', 5 ) ) ); ?>;
        --grid-mobile-columns: <?php echo esc_html( prs_sanitize_positive_int( get_theme_mod( 'prs_grid_mobile_cols', 2 ) ) ); ?>;
      }
      @media (max-width: 768px) {
        :root { --header-height: <?php echo esc_html( $header_mob ); ?>px; }
      }
    </style>
    <?php
}
add_action( 'wp_head', 'prs_customizer_css', 20 );

function prs_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'prs_design_settings', [
        'title'       => __( 'Palancia Design', 'palancia-shop' ),
        'priority'    => 35,
        'description' => __( 'Ajustes visuales del tema. Los valores por defecto mantienen el diseno brutalist actual.', 'palancia-shop' ),
    ] );

    $color_settings = [
        'prs_bg_color' => [
            'label'   => __( 'Fondo de pagina', 'palancia-shop' ),
            'default' => '#ffffff',
        ],
        'prs_paper_color' => [
            'label'   => __( 'Fondo de bloques', 'palancia-shop' ),
            'default' => '#ffffff',
        ],
        'prs_ink_color' => [
            'label'   => __( 'Color de texto y lineas', 'palancia-shop' ),
            'default' => '#0a0a0a',
        ],
        'prs_muted_color' => [
            'label'   => __( 'Color secundario', 'palancia-shop' ),
            'default' => '#575757',
        ],
        'prs_accent_color' => [
            'label'   => __( 'Color categoria activa', 'palancia-shop' ),
            'default' => '#24364b',
        ],
    ];

    foreach ( $color_settings as $id => $setting ) {
        $wp_customize->add_setting( $id, [
            'default'           => $setting['default'],
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ] );

        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, [
            'label'   => $setting['label'],
            'section' => 'prs_design_settings',
        ] ) );
    }

    $number_settings = [
        'prs_logo_height_desktop' => [ __( 'Logo desktop alto (px)', 'palancia-shop' ), 70, 40, 180, 'range' ],
        'prs_logo_height_mobile'  => [ __( 'Logo movil alto (px)', 'palancia-shop' ), 62, 36, 140, 'range' ],
        'prs_header_height'       => [ __( 'Header desktop alto (px)', 'palancia-shop' ), 78, 64, 180 ],
        'prs_header_height_mobile'=> [ __( 'Header movil alto (px)', 'palancia-shop' ), 72, 56, 150 ],
        'prs_grid_desktop_cols'   => [ __( 'Columnas grid desktop', 'palancia-shop' ), 6, 3, 8 ],
        'prs_grid_tablet_cols'    => [ __( 'Columnas grid tablet', 'palancia-shop' ), 4, 2, 6 ],
        'prs_grid_mid_cols'       => [ __( 'Columnas grid intermedio', 'palancia-shop' ), 4, 2, 6 ],
        'prs_grid_mobile_cols'    => [ __( 'Columnas grid movil', 'palancia-shop' ), 2, 1, 3 ],
    ];

    foreach ( $number_settings as $id => $args ) {
        $wp_customize->add_setting( $id, [
            'default'           => $args[1],
            'sanitize_callback' => 'prs_sanitize_positive_int',
            'transport'         => 'refresh',
        ] );

        $wp_customize->add_control( $id, [
            'label'       => $args[0],
            'section'     => 'prs_design_settings',
            'type'        => $args[4] ?? 'number',
            'input_attrs' => [
                'min'  => $args[2],
                'max'  => $args[3],
                'step' => 1,
            ],
        ] );
    }

    $size_settings = [
        'prs_nav_column_width' => [ __( 'Ancho columna navegacion', 'palancia-shop' ), 'clamp(8rem, 12vw, 11.125rem)' ],
        'prs_nav_column_width_tablet' => [ __( 'Ancho navegacion tablet', 'palancia-shop' ), '8.25rem' ],
        'prs_side_padding'     => [ __( 'Padding lateral desktop', 'palancia-shop' ), 'clamp(0.8rem, 2vw, 2rem)' ],
        'prs_side_padding_mobile' => [ __( 'Padding lateral movil', 'palancia-shop' ), '0.65rem' ],
        'prs_layout_gap'       => [ __( 'Separacion layout', 'palancia-shop' ), 'clamp(0.55rem, 1.2vw, 1rem)' ],
        'prs_layout_gap_mobile'=> [ __( 'Separacion layout movil', 'palancia-shop' ), '0.65rem' ],
    ];

    foreach ( $size_settings as $id => $args ) {
        $wp_customize->add_setting( $id, [
            'default'           => $args[1],
            'sanitize_callback' => 'prs_sanitize_css_size',
            'transport'         => 'refresh',
        ] );

        $wp_customize->add_control( $id, [
            'label'   => $args[0],
            'section' => 'prs_design_settings',
            'type'    => 'text',
        ] );
    }
}

function prs_customizer_css() {
    $bg     = get_theme_mod( 'prs_bg_color', '#ffffff' );
    $paper  = get_theme_mod( 'prs_paper_color', '#ffffff' );
    $ink    = get_theme_mod( 'prs_ink_color', '#0a0a0a' );
    $muted  = get_theme_mod( 'prs_muted_color', '#575757' );
    $accent = get_theme_mod( 'prs_accent_color', '#24364b' );

    $logo_desktop = absint( get_theme_mod( 'prs_logo_height_desktop', 70 ) );
    $logo_mobile  = absint( get_theme_mod( 'prs_logo_height_mobile', 62 ) );
    $header       = absint( get_theme_mod( 'prs_header_height', 78 ) );
    $header_mob   = absint( get_theme_mod( 'prs_header_height_mobile', 72 ) );

    $nav_width = prs_sanitize_css_size( get_theme_mod( 'prs_nav_column_width', 'clamp(8rem, 12vw, 11.125rem)' ) ) ?: 'clamp(8rem, 12vw, 11.125rem)';
    $nav_width_tablet = prs_sanitize_css_size( get_theme_mod( 'prs_nav_column_width_tablet', '8.25rem' ) ) ?: '8.25rem';
    $side_pad  = prs_sanitize_css_size( get_theme_mod( 'prs_side_padding', 'clamp(0.8rem, 2vw, 2rem)' ) ) ?: 'clamp(0.8rem, 2vw, 2rem)';
    $side_pad_mobile = prs_sanitize_css_size( get_theme_mod( 'prs_side_padding_mobile', '0.65rem' ) ) ?: '0.65rem';
    $gap       = prs_sanitize_css_size( get_theme_mod( 'prs_layout_gap', 'clamp(0.55rem, 1.2vw, 1rem)' ) ) ?: 'clamp(0.55rem, 1.2vw, 1rem)';
    $gap_mobile = prs_sanitize_css_size( get_theme_mod( 'prs_layout_gap_mobile', '0.65rem' ) ) ?: '0.65rem';
    ?>
    <style id="prs-customizer-css">
      :root {
        --bg: <?php echo esc_html( $bg ); ?>;
        --paper: <?php echo esc_html( $paper ); ?>;
        --ink: <?php echo esc_html( $ink ); ?>;
        --line: <?php echo esc_html( $ink ); ?>;
        --muted: <?php echo esc_html( $muted ); ?>;
        --accent: <?php echo esc_html( $accent ); ?>;
        --header-height: <?php echo esc_html( $header ); ?>px;
        --logo-height-desktop: <?php echo esc_html( $logo_desktop ); ?>px;
        --logo-height-mobile: <?php echo esc_html( $logo_mobile ); ?>px;
        --nav-column-width: <?php echo esc_html( $nav_width ); ?>;
        --nav-column-width-tablet: <?php echo esc_html( $nav_width_tablet ); ?>;
        --side-padding: <?php echo esc_html( $side_pad ); ?>;
        --side-padding-mobile: <?php echo esc_html( $side_pad_mobile ); ?>;
        --layout-gap: <?php echo esc_html( $gap ); ?>;
        --layout-gap-mobile: <?php echo esc_html( $gap_mobile ); ?>;
        --grid-desktop-columns: <?php echo esc_html( prs_sanitize_positive_int( get_theme_mod( 'prs_grid_desktop_cols', 6 ) ) ); ?>;
        --grid-tablet-columns: <?php echo esc_html( prs_sanitize_positive_int( get_theme_mod( 'prs_grid_tablet_cols', 4 ) ) ); ?>;
        --grid-mid-columns: <?php echo esc_html( prs_sanitize_positive_int( get_theme_mod( 'prs_grid_mid_cols', 4 ) ) ); ?>;
        --grid-mobile-columns: <?php echo esc_html( prs_sanitize_positive_int( get_theme_mod( 'prs_grid_mobile_cols', 2 ) ) ); ?>;
      }
      @media (max-width: 768px) {
        :root { --header-height: <?php echo esc_html( $header_mob ); ?>px; }
      }
    </style>
    <?php
}


// ------------ LIMPIEZA FICHA PRODUCTO ------------ //

// Limpiar cosas del resumen y tabs
function prs_cleanup_single_summary_hooks() {
    // Quitar rating, meta, compartir
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

    // Quitar descripción corta por defecto (excerpt)
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );

    // Quitar tabs, upsells y relacionados de abajo
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

    // Quitar breadcrumbs (Inicio / Categoría / Producto)
    remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

    // Quitar sidebar de WooCommerce (Buscar, Páginas, Archivos, Categorías)
    remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
}
add_action( 'init', 'prs_cleanup_single_summary_hooks' );

// Mostrar talla debajo del título (atributo pa_talla)
function prs_show_product_size() {
    global $product;

    if ( ! $product ) {
        return;
    }

    $size_terms = wc_get_product_terms( $product->get_id(), 'pa_talla', [ 'fields' => 'names' ] );

    if ( ! empty( $size_terms ) ) {
        $tallas = implode( ', ', $size_terms );
        echo '<div class="prs-product-size"><span>Talla:</span> ' . esc_html( $tallas ) . '</div>';
    }
}
add_action( 'woocommerce_single_product_summary', 'prs_show_product_size', 6 );

// Descripción larga en el resumen, como texto simple
function prs_product_long_description() {
    global $post;

    if ( ! $post ) {
        return;
    }

    $content = apply_filters( 'the_content', $post->post_content );

    if ( ! $content ) {
        return;
    }

    echo '<div class="prs-product-long-description">' . $content . '</div>';
}
// La ponemos debajo del precio y el botón de añadir al carrito.
add_action( 'woocommerce_single_product_summary', 'prs_product_long_description', 35 );


// ------------ GALERÍA PERSONALIZADA ------------ //

// Sustituir galería nativa por la nuestra
function prs_override_wc_gallery() {
    // Elimina la galería por defecto de WooCommerce
    remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
    // Añade nuestra galería en el mismo hook
    add_action( 'woocommerce_before_single_product_summary', 'prs_custom_product_gallery', 20 );
}
add_action( 'init', 'prs_override_wc_gallery' );

// Galería centrada, sin miniaturas, con flechas y loop
function prs_custom_product_gallery() {
    if ( ! is_product() ) return;

    global $product;
    if ( ! $product ) return;

    $prev_url = '';
    $next_url = '';
    $ordered_ids = prs_get_home_ordered_product_ids();
    $current_id  = $product->get_id();
    $total_ids   = count( $ordered_ids );

    if ( $total_ids > 1 ) {
        $current_index = array_search( $current_id, $ordered_ids, true );
        if ( $current_index !== false ) {
            $prev_index = ( $current_index - 1 + $total_ids ) % $total_ids;
            $next_index = ( $current_index + 1 ) % $total_ids;
            $prev_url   = get_permalink( $ordered_ids[ $prev_index ] );
            $next_url   = get_permalink( $ordered_ids[ $next_index ] );
        }
    }

    $image_ids = [];
    $featured = $product->get_image_id();
    if ( $featured ) $image_ids[] = $featured;

    $gallery = $product->get_gallery_image_ids();
    if ( $gallery ) $image_ids = array_merge($image_ids, $gallery);

    if ( empty( $image_ids ) ) {
        $placeholder_url = wc_placeholder_img_src( 'large' );
        echo '<div class="prs-product-gallery" data-total="1" data-prev-url="' . esc_url( $prev_url ) . '" data-next-url="' . esc_url( $next_url ) . '">';
        echo '<div class="prs-product-slide is-active" data-index="0">';
        echo '<img src="' . esc_url( $placeholder_url ) . '" alt="' . esc_attr( $product->get_name() ) . '">';
        echo '</div></div>';
        return;
    }

    echo '<div class="prs-product-gallery" data-total="' . count( $image_ids ) . '" data-prev-url="' . esc_url( $prev_url ) . '" data-next-url="' . esc_url( $next_url ) . '">';

    foreach ($image_ids as $i => $id) {
        echo '<div class="prs-product-slide '.($i === 0 ? 'is-active' : '').'" data-index="'.$i.'">';
        echo wp_get_attachment_image($id, 'large');
        echo '</div>';
    }

    if (count($image_ids) > 1) {
        echo '<button class="prs-product-nav prs-prev" type="button">&lsaquo;</button>';
        echo '<button class="prs-product-nav prs-next" type="button">&rsaquo;</button>';
    }

    echo '</div>';

    if ( count( $image_ids ) > 1 ) {
        echo '<div class="prs-product-dots" aria-label="Galería de producto">';
        foreach ( $image_ids as $i => $id ) {
            $active = $i === 0 ? ' is-active' : '';
            echo '<button class="prs-product-dot' . $active . '" type="button" aria-label="Ver imagen ' . ( $i + 1 ) . '" data-index="' . $i . '"></button>';
        }
        echo '</div>';
    }
}


// Orden de categorias y renderizado de productos en bloques por categoria y precio
function prs_get_product_category_order() {
    return [ 'palancia-merch', 'chaquetas', 'chalecos', 'sudaderas', 'jerseis', 'tracksuits', 'pantalones', 'camisetas', 'bolsos', 'gafas', 'gorras'];
}

/**
 * Obtiene las categorías de producto filtradas y ordenadas según prioridad.
 * Centraliza la lógica usada en Home, Archivo y Single Product.
 */
function prs_get_sorted_product_categories() {
    // 1. Obtener todas las categorías no vacías
    $product_categories = get_terms( [
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
    ] );

    if ( is_wp_error( $product_categories ) || empty( $product_categories ) ) {
        return [];
    }

    // 2. Excluir categorías
    $excluded_slugs = [ 'tops', 'bottoms', 'accesorios' ];
    $product_categories = array_filter( $product_categories, function ( $cat ) use ( $excluded_slugs ) {
        return ! in_array( strtolower( $cat->slug ), $excluded_slugs, true );
    } );

    // 3. Ordenar según prioridad
    $priority_order = prs_get_product_category_order(); // Reutilizamos la función que ya tenías

    // Función auxiliar para ordenar objetos según un array de slugs
    $sorted = [];
    $lookup = [];

    // Indexar categorías por slug para búsqueda rápida
    foreach ( $product_categories as $cat ) {
        $lookup[ strtolower( $cat->slug ) ] = $cat;
    }

    // 1. PALANCIA MERCH primero
    if ( isset( $lookup['palancia-merch'] ) ) {
        $sorted[] = $lookup['palancia-merch'];
        unset( $lookup['palancia-merch'] );
    }

    // 2. Asegurar que 'todo' va después (usando la real si existe, o una fake si no)
    if ( isset( $lookup['todo'] ) ) {
        $sorted[] = $lookup['todo'];
        unset( $lookup['todo'] );
    } else {
        $sorted[] = (object) [
            'term_id' => 0,
            'name'    => 'Todo',
            'slug'    => 'todo',
        ];
    }

    // Añadir las prioritarias en orden
    foreach ( $priority_order as $slug ) {
        if ( isset( $lookup[ $slug ] ) ) {
            $sorted[] = $lookup[ $slug ];
            unset( $lookup[ $slug ] );
        }
    }

    // Añadir el resto (si hubiera alguna categoría nueva no listada en prioridad)
    foreach ( $lookup as $cat ) {
        $sorted[] = $cat;
    }

    return $sorted;
}

function prs_get_home_ordered_product_ids() {
    $ids  = [];
    $seen = [];

    $base_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => '_price',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'fields'         => 'ids',
    ];

    foreach ( prs_get_product_category_order() as $slug ) {
        $term = get_term_by( 'slug', $slug, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) {
            continue;
        }

        $args = $base_args;
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $slug,
            ],
        ];

        $query = new WP_Query( $args );
        if ( ! empty( $query->posts ) ) {
            foreach ( $query->posts as $post_id ) {
                if ( isset( $seen[ $post_id ] ) ) {
                    continue;
                }
                $seen[ $post_id ] = true;
                $ids[]            = $post_id;
            }
        }
        wp_reset_postdata();
    }

    // Rellenar con el resto de productos en orden de precio
    $query = new WP_Query( $base_args );
    if ( ! empty( $query->posts ) ) {
        foreach ( $query->posts as $post_id ) {
            if ( isset( $seen[ $post_id ] ) ) {
                continue;
            }
            $seen[ $post_id ] = true;
            $ids[]            = $post_id;
        }
    }
    wp_reset_postdata();

    return $ids;
}

function prs_render_product_card( $post_id ) {
    $thumb_id   = get_post_thumbnail_id( $post_id );
    $img        = wp_get_attachment_image_src( $thumb_id, 'large' );
    $img_url    = $img ? $img[0] : wc_placeholder_img_src();
    $url        = get_permalink( $post_id );
    $title_attr = the_title_attribute( [ 'echo' => false, 'post' => $post_id ] );
    $product    = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : false;
    $price_html = $product ? $product->get_price_html() : '';
    $stock_text = $product && ! $product->is_in_stock() ? __( 'Sold out', 'palancia-shop' ) : '';

    $html  = '<a href="' . esc_url( $url ) . '" class="product-item">';
    $html .= '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $title_attr ) . '">';
    $html .= '<div class="product-overlay">';
    $html .= '<span class="product-title">' . esc_html( get_the_title( $post_id ) ) . '</span>';
    if ( $stock_text ) {
        $html .= '<span class="product-stock">' . esc_html( $stock_text ) . '</span>';
    }
    if ( $price_html ) {
        $html .= '<span class="product-price">' . wp_kses_post( $price_html ) . '</span>';
    }
    $html .= '</div>';
    $html .= '</a>';

    return $html;
}

function prs_render_products_grid( $category_slug = '', $max_products = 0, $size_slug = '', $stock_filter = 'show' ) {
    $category_slug = strtolower( $category_slug );
    $size_slugs    = is_array( $size_slug )
        ? array_filter( array_map( 'sanitize_title', $size_slug ) )
        : array_filter( array_map( 'sanitize_title', explode( ',', (string) $size_slug ) ) );
    $stock_filter  = sanitize_title( (string) $stock_filter );
    $html          = '';
    $printed       = 0;
    $seen_ids      = [];
    $limit         = (int) $max_products;
    $is_unlimited  = $limit <= 0;

    $base_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $is_unlimited ? -1 : $limit,
        'meta_key'       => '_price',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ];

    // Helper para aplicar filtro de talla a los argumentos
    $apply_size_filter = function( $args ) use ( $size_slugs, $stock_filter ) {
        if ( ! empty( $size_slugs ) ) {
            if ( ! isset( $args['tax_query'] ) ) {
                $args['tax_query'] = [];
            }
            if ( count( $args['tax_query'] ) > 0 ) {
                $args['tax_query']['relation'] = 'AND';
            }
            $args['tax_query'][] = [
                'taxonomy' => 'pa_talla',
                'field'    => 'slug',
                'terms'    => $size_slugs,
                'operator' => 'IN',
            ];
        }
        if ( 'hide' === $stock_filter ) {
            if ( ! isset( $args['meta_query'] ) ) {
                $args['meta_query'] = [];
            }
            $args['meta_query'][] = [
                'key'   => '_stock_status',
                'value' => 'instock',
            ];
        }
        return $args;
    };

    // Cuando hay categoria concreta (diferente a TODO), solo ese bloque ordenado por precio
    if ( $category_slug && $category_slug !== 'todo' ) {
        $args              = $base_args;
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category_slug,
            ],
        ];
        $args = $apply_size_filter( $args );

        $loop = new WP_Query( $args );

        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() ) {
                if ( ! $is_unlimited && $printed >= $limit ) {
                    break;
                }
                $loop->the_post();
                $post_id = get_the_ID();

                if ( isset( $seen_ids[ $post_id ] ) ) {
                    continue;
                }

                $seen_ids[ $post_id ] = true;
                $html                .= prs_render_product_card( $post_id );
                $printed++;
            }
            wp_reset_postdata();
        }

        return $html;
    }

    // TODO / sin categoria: recorrer categorias en orden y dentro cada una por precio descendente
    foreach ( prs_get_product_category_order() as $slug ) {
        if ( ! $is_unlimited ) {
            $remaining = $limit - $printed;
            if ( $remaining <= 0 ) {
                break;
            }
        }

        $term = get_term_by( 'slug', $slug, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) {
            continue;
        }

        $args = $base_args;
        if ( ! $is_unlimited ) {
            $args['posts_per_page'] = $remaining;
        }
        $args['tax_query']      = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $slug,
            ],
        ];
        $args = $apply_size_filter( $args );

        $loop = new WP_Query( $args );

        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() ) {
                if ( ! $is_unlimited && $printed >= $limit ) {
                    break;
                }
                $loop->the_post();
                $post_id = get_the_ID();

                if ( isset( $seen_ids[ $post_id ] ) ) {
                    continue;
                }

                $seen_ids[ $post_id ] = true;
                $html                .= prs_render_product_card( $post_id );
                $printed++;
            }
            wp_reset_postdata();
        }
    }

    // Si faltan productos (categorias no listadas), rellenar por precio descendente sin perder el orden ya impreso
    if ( $is_unlimited || $printed < $limit ) {
        $args                   = $base_args;
        if ( ! $is_unlimited ) {
            $args['posts_per_page'] = $limit;
        }
        $args = $apply_size_filter( $args );

        $loop = new WP_Query( $args );

        if ( $loop->have_posts() ) {
            while ( $loop->have_posts() ) {
                if ( ! $is_unlimited && $printed >= $limit ) {
                    break;
                }
                $loop->the_post();
                $post_id = get_the_ID();

                if ( isset( $seen_ids[ $post_id ] ) ) {
                    continue;
                }

                $seen_ids[ $post_id ] = true;
                $html                .= prs_render_product_card( $post_id );
                $printed++;
            }
            wp_reset_postdata();
        }
    }

    return $html;
}

function prs_get_available_size_terms() {
    if ( ! taxonomy_exists( 'pa_talla' ) ) {
        return [];
    }

    $terms = get_terms( [
        'taxonomy'   => 'pa_talla',
        'hide_empty' => false,
        'orderby'    => 'menu_order',
        'order'      => 'ASC',
    ] );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return [];
    }

    $alpha_sizes = [
        'XXXXS'    => 0,
        '4XSMALL'  => 0,
        'XXXS'     => 1,
        '3XSMALL'  => 1,
        'XXS'      => 2,
        '2XSMALL'  => 2,
        'XS'       => 3,
        'XSMALL'   => 3,
        'S'        => 4,
        'SMALL'    => 4,
        'M'        => 5,
        'MEDIUM'   => 5,
        'L'        => 6,
        'LARGE'    => 6,
        'XL'       => 7,
        'XLARGE'   => 7,
        'XXL'      => 8,
        '2XL'      => 8,
        'XXLARGE'  => 8,
        '2XLARGE'  => 8,
        'XXXL'     => 9,
        '3XL'      => 9,
        'XXXLARGE' => 9,
        '3XLARGE'  => 9,
        'XXXXL'    => 10,
        '4XL'      => 10,
        '4XLARGE'  => 10,
    ];

    usort(
        $terms,
        static function( $first, $second ) use ( $alpha_sizes ) {
            $get_sort_key = static function( $term ) use ( $alpha_sizes ) {
                $label = strtoupper( remove_accents( (string) $term->name ) );
                $key   = preg_replace( '/[^A-Z0-9]/', '', $label );

                if ( isset( $alpha_sizes[ $key ] ) ) {
                    return [ 0, $alpha_sizes[ $key ], $key ];
                }

                if ( preg_match( '/^W(\d{2,3})(?:L(\d{2,3}))?$/', $key, $matches ) ) {
                    return [ 1, (int) $matches[1], isset( $matches[2] ) ? (int) $matches[2] : 0 ];
                }

                if ( preg_match( '/^(\d+(?:\.\d+)?)$/', $key, $matches ) ) {
                    return [ 2, (float) $matches[1], $key ];
                }

                return [ 3, 0, $key ];
            };

            $first_key  = $get_sort_key( $first );
            $second_key = $get_sort_key( $second );
            $comparison = $first_key <=> $second_key;

            return 0 !== $comparison ? $comparison : (int) $first->term_id <=> (int) $second->term_id;
        }
    );

    return $terms;
}

// Manejar solicitudes AJAX para filtrar productos por categoría
function prs_filter_products_by_category() {
    $category_slug = isset($_POST['category_slug'])
        ? sanitize_title( wp_unslash( $_POST['category_slug'] ) )
        : '';

    $size_slug = isset($_POST['size_slugs'])
        ? sanitize_text_field( wp_unslash( $_POST['size_slugs'] ) )
        : '';

    $stock_filter = isset($_POST['stock_filter'])
        ? sanitize_title( wp_unslash( $_POST['stock_filter'] ) )
        : 'show';

    // TODO => mostrar todo en orden de categorias y precio
    if ( $category_slug === 'todo' ) {
        $category_slug = '';
    }

    $html = prs_render_products_grid( $category_slug, 0, $size_slug, $stock_filter );

    if ( ! $html ) {
        $html = '<p class="prs-products-empty">' . esc_html__( 'No hay productos con estos filtros.', 'palancia-shop' ) . '</p>';
    }

    // Es una consulta publica de solo lectura. Una respuesta JSON evita que WordPress
    // inserte "-1" en el grid cuando una pagina cacheada contiene un nonce caducado.
    wp_send_json_success( [ 'html' => $html ] );
}

add_action( 'wp_ajax_prs_filter_products', 'prs_filter_products_by_category' );
add_action( 'wp_ajax_nopriv_prs_filter_products', 'prs_filter_products_by_category' );
// -------- Checkout ligero y con anti-bot -------- //
function prs_checkout_slim_fields( $fields ) {
    unset( $fields['billing']['billing_company'] );
    unset( $fields['billing']['billing_address_2'] );
    // Notas de pedido (si quieres mantenerlas, elimina esta línea)
    unset( $fields['order']['order_comments'] );
    return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'prs_checkout_slim_fields' );

// Honeypot simple para bots en checkout
function prs_checkout_honeypot_field( $checkout ) {
    echo '<div class="prs-checkout-hp" aria-hidden="true">';
    woocommerce_form_field(
        'prs_hp_field',
        [
            'type'  => 'text',
            'class' => [ 'prs-hp' ],
            'label' => __( 'No rellenar', 'palancia-shop' ),
        ],
        ''
    );
    echo '</div>';
}
add_action( 'woocommerce_after_checkout_billing_form', 'prs_checkout_honeypot_field' );

function prs_checkout_honeypot_validate() {
    if ( ! empty( $_POST['prs_hp_field'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- campo honeypot
        wc_add_notice( __( 'No se ha podido procesar el pedido. Inténtalo de nuevo.', 'palancia-shop' ), 'error' );
    }
}
add_action( 'woocommerce_checkout_process', 'prs_checkout_honeypot_validate' );

// ---------- Carrito custom por hooks (para evitar override) ----------
function prs_render_custom_cart() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return;
    }

    $cart_items = WC()->cart->get_cart();

    if ( WC()->cart->is_empty() ) {
        $shop_url = home_url( '/' );

        echo '<div class="pal-cart-empty">';
        echo '<div class="pal-cart-empty-index" aria-hidden="true">00</div>';
        echo '<div class="pal-cart-empty-title">' . esc_html__( 'Tu carrito está vacío', 'palancia-shop' ) . '</div>';
        echo '<div class="pal-cart-empty-message">' . esc_html__( 'Todavía no has añadido ningún producto.', 'palancia-shop' ) . '</div>';
        echo '<a class="pal-cart-empty-action" href="' . esc_url( $shop_url ) . '">' . esc_html__( 'Ver productos', 'palancia-shop' ) . '</a>';
        echo '</div>';
        return;
    }

	$terms_page_id  = wc_get_page_id( 'terms' );
	$terms_url      = $terms_page_id > 0 ? get_permalink( $terms_page_id ) : '';
	$requires_terms = ! empty( $terms_url );

	echo '<div class="pal-cart-wrapper">';
	foreach ( $cart_items as $cart_item_key => $cart_item ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
			continue;
		}

		$product_id        = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
		$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $product->is_visible() ? $product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
		$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
		$product_name      = apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key );
		$price_html        = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
		$item_data         = wc_get_formatted_cart_item_data( $cart_item );
		$remove_link       = apply_filters(
			'woocommerce_cart_item_remove_link',
			sprintf(
				'<a href="%s" class="pal-cart-remove button" aria-label="%s" data-product_id="%s" data-product_sku="%s">%s</a>',
				esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
				esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
				esc_attr( $product_id ),
				esc_attr( $product->get_sku() ),
				esc_html__( 'Remove', 'woocommerce' )
			),
			$cart_item_key
		);

		echo '<div class="pal-cart-item" data-cart-item-key="' . esc_attr( $cart_item_key ) . '">';

		echo '<div class="pal-cart-thumb">';
		if ( ! $product_permalink ) {
			echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';

		echo '<div class="pal-cart-main">';
		echo '<div class="pal-cart-title">';
		if ( ! $product_permalink ) {
			echo wp_kses_post( $product_name );
		} else {
			printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $product_name ) );
		}
		echo '</div>';

		if ( $item_data ) {
			echo '<div class="pal-cart-meta">' . $item_data . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '<div class="pal-cart-price pal-cart-price--mobile">' . $price_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>'; // pal-cart-main

		echo '<div class="pal-cart-right">';
		echo '<div class="pal-cart-remove-wrap">' . $remove_link . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="pal-cart-price">' . $price_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>'; // pal-cart-right

		echo '</div>'; // pal-cart-item
	}
	echo '</div>'; // pal-cart-wrapper

    echo '<div class="pal-cart-summary">';
    echo '<div class="pal-cart-subtotal"><span class="label">' . esc_html__( 'Subtotal', 'woocommerce' ) . '</span><span class="amount">';
    wc_cart_totals_subtotal_html();
    echo '</span></div>';

	if ( $requires_terms ) {
		echo '<label class="pal-cart-terms">';
		echo '<input type="checkbox" id="pal-cart-terms">';
		echo '<span>' . esc_html__( 'I agree to the', 'woocommerce' ) . ' <a href="' . esc_url( $terms_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'terms and conditions', 'woocommerce' ) . '</a></span>';
		echo '</label>';
	}

	$checkout_classes = 'button pal-cart-checkout' . ( $requires_terms ? ' is-disabled' : '' );
	$checkout_aria    = $requires_terms ? 'true' : 'false';
	echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="' . esc_attr( $checkout_classes ) . '" aria-disabled="' . esc_attr( $checkout_aria ) . '">' . esc_html__( 'Checkout', 'woocommerce' ) . '</a>';
	echo '<div class="pal-cart-note"><span>' . esc_html__( 'Limited to 1 per size / item', 'woocommerce' ) . '</span><span class="note-secondary">' . esc_html__( '* Some exceptions apply', 'woocommerce' ) . '</span></div>';
	echo '</div>'; // pal-cart-summary

	wc_enqueue_js(
		"
		const palTerms = document.getElementById('pal-cart-terms');
		const palCheckout = document.querySelector('.pal-cart-checkout');
		const toggleCheckout = () => {
			if (!palCheckout) return;
			if (!palTerms) {
				palCheckout.classList.remove('is-disabled');
				palCheckout.setAttribute('aria-disabled', 'false');
				return;
			}
			if (palTerms.checked) {
				palCheckout.classList.remove('is-disabled');
				palCheckout.setAttribute('aria-disabled', 'false');
			} else {
				palCheckout.classList.add('is-disabled');
				palCheckout.setAttribute('aria-disabled', 'true');
			}
		};
		if (palTerms && palCheckout) {
			palTerms.addEventListener('change', toggleCheckout);
			toggleCheckout();
			palCheckout.addEventListener('click', (event) => {
				if (palCheckout.classList.contains('is-disabled')) {
					event.preventDefault();
				}
            });
        }
        "
    );
}

// Suprimir aviso de producto eliminado del carrito
add_filter( 'woocommerce_cart_item_removed_message', '__return_empty_string' );

// Usar plantilla de carrito personalizada sin override de WooCommerce
function prs_cart_template_override( $template ) {
	if ( is_cart() ) {
		$custom = get_stylesheet_directory() . '/custom-cart.php';
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'prs_cart_template_override' );

// Usar plantilla custom para single product en vez del override de WooCommerce
function prs_single_product_template_override( $template ) {
	if ( is_singular( 'product' ) ) {
		$custom = get_stylesheet_directory() . '/custom-single-product.php';
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'prs_single_product_template_override', 11 );

// --------- Reglas: productos 1/1 y sin avisos estándar ---------
// Forzar que todos los productos sean vendidos de forma individual (sin cantidades)
add_filter( 'woocommerce_is_sold_individually', '__return_true', 10, 2 );

// Suprimir mensaje de "añadido al carrito"
add_filter( 'wc_add_to_cart_message_html', '__return_empty_string' );

//Activar Application Passwords
add_filter( 'wp_is_application_passwords_available', '__return_true' );

// ---------- Contact form handler ----------
function prs_handle_contact_form() {
    if ( ! isset( $_POST['prs_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['prs_contact_nonce'] ) ), 'prs_contact_form' ) ) {
        wp_die( 'Invalid request.' );
    }

    $name         = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $subject_key  = isset( $_POST['subject'] ) ? sanitize_key( wp_unslash( $_POST['subject'] ) ) : 'other';
    $order_number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
    $message      = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
    $website      = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : '';

    $subjects = [
        'product'  => 'Producto',
        'order'    => 'Pedido',
        'shipping' => 'Envío',
        'return'   => 'Devolución',
        'other'    => 'Otro',
    ];

    $referer = wp_get_referer();
    $redirect_base = remove_query_arg( 'contact', $referer ? $referer : home_url( '/contact' ) );

    if ( $website ) {
        wp_safe_redirect( add_query_arg( 'contact', 'success', $redirect_base ) );
        exit;
    }

    if ( empty( $name ) || empty( $message ) || ! is_email( $email ) || ! isset( $subjects[ $subject_key ] ) ) {
        wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect_base ) );
        exit;
    }

    $to            = get_option( 'admin_email' );
    $subject_label = $subjects[ $subject_key ];
    $subject       = '[PALANCIA] ' . $subject_label . ' / ' . $name;
    $body          = "Nombre: {$name}\nEmail: {$email}\nAsunto: {$subject_label}\nPedido: " . ( $order_number ?: '-' ) . "\n\n{$message}";
    $headers = [ 'Reply-To: ' . $name . ' <' . $email . '>' ];

    $sent = wp_mail( $to, $subject, $body, $headers );

    wp_safe_redirect( add_query_arg( 'contact', $sent ? 'success' : 'error', $redirect_base ) );
    exit;
}

add_action( 'admin_post_prs_contact', 'prs_handle_contact_form' );
add_action( 'admin_post_nopriv_prs_contact', 'prs_handle_contact_form' );
