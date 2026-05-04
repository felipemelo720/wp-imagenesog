<?php
defined( 'ABSPATH' ) || exit;

class WP_OG_Injector {

	public function __construct() {
		// template_redirect fires before wp_head: garantiza que los filtros
		// de Yoast/RankMath/AIOSEO estén registrados antes de que impriman.
		add_action( 'template_redirect', array( $this, 'remove_other_og_images' ) );

		add_action( 'wp_head', array( $this, 'inject_og_tags' ), 10 );
	}

	public function remove_other_og_images(): void {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		// Yoast SEO
		add_filter( 'wpseo_og_image_url', '__return_false' );
		add_filter( 'wpseo_frontend_presenters', array( $this, 'remove_yoast_og_presenters' ) );

		// RankMath
		add_filter( 'rank_math/opengraph/facebook/og_image', '__return_false' );
		add_filter( 'rank_math/opengraph/facebook/og_image_width', '__return_false' );
		add_filter( 'rank_math/opengraph/facebook/og_image_height', '__return_false' );

		// All in One SEO
		add_filter( 'aioseo_og_image', '__return_false' );
	}

	/**
	 * Removes Yoast's OG image presenter to prevent duplicate tags.
	 *
	 * @param array $presenters
	 * @return array
	 */
	public function remove_yoast_og_presenters( array $presenters ): array {
		foreach ( $presenters as $key => $presenter ) {
			if ( $presenter instanceof \Yoast\WP\SEO\Presenters\Open_Graph\Image_Presenter ) {
				unset( $presenters[ $key ] );
			}
		}
		return $presenters;
	}

	public function inject_og_tags(): void {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$product = wc_get_product( get_the_ID() );
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$opts    = WP_OG_Settings::get_options();
		$api_url = $opts['api_url'];
		$title   = $product->get_name();

		$dec_sep  = wc_get_price_decimal_separator();
		$thou_sep = wc_get_price_thousand_separator();
		$decimals = wc_get_price_decimals();

		if ( $product->is_type( 'variable' ) ) {
			$min_raw = $product->get_variation_price( 'min', true );
			$max_raw = $product->get_variation_price( 'max', true );
			$price   = '$' . number_format( (float) $min_raw, $decimals, $dec_sep, $thou_sep );
			if ( (float) $min_raw !== (float) $max_raw ) {
				$price .= ' - $' . number_format( (float) $max_raw, $decimals, $dec_sep, $thou_sep );
			}
			$sale_price = '';
		} else {
			$regular_raw = $product->get_regular_price();
			$sale_raw    = $product->get_sale_price();
			$price       = $regular_raw !== '' ? '$' . number_format( (float) $regular_raw, $decimals, $dec_sep, $thou_sep ) : '';
			$sale_price  = $sale_raw !== '' ? '$' . number_format( (float) $sale_raw, $decimals, $dec_sep, $thou_sep ) : '';
		}

		$image_id  = $product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';

		$params = array(
			'title'       => $title,
			'price'       => $price,
			'logo'        => $opts['logo'],
			'bgColor'     => $opts['bg_color'],
			'textColor'   => $opts['text_color'],
			'accentColor' => $opts['accent_color'],
			'template'    => $opts['template'],
			'font'        => $opts['font'],
			'cta'         => $opts['cta'],
		);

		if ( $sale_price !== '' ) {
			$params['salePrice'] = $sale_price;
		}

		if ( $opts['badge'] !== '' ) {
			$params['badge'] = $opts['badge'];
		}

		if ( $image_url ) {
			$params['image'] = $image_url;
		}

		// Remove empty values so API uses its own defaults
		$params = array_filter( $params, static function ( $v ) {
			return $v !== '';
		} );

		$og_image_url = add_query_arg( $params, esc_url( $api_url ) );

		$description = wp_strip_all_tags( $product->get_short_description() );
		if ( $description === '' ) {
			$description = wp_trim_words( wp_strip_all_tags( $product->get_description() ), 20, '…' );
		}

		echo "\n";
		if ( ! empty( $opts['locale'] ) ) {
			printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( $opts['locale'] ) );
		}
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( get_permalink() ) );
		if ( $description !== '' ) {
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
		}
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $og_image_url ) );
		echo '<meta property="og:image:width" content="1200">' . "\n";
		echo '<meta property="og:image:height" content="630">' . "\n";
	}
}
