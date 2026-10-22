<?php
defined( 'ABSPATH' ) || exit;

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



add_action( 'customize_register', 'prs_customize_register' );
add_action( 'wp_head', 'prs_customizer_css', 20 );
