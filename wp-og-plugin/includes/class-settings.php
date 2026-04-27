<?php
defined( 'ABSPATH' ) || exit;

class WP_OG_Settings {

	const OPTION_KEY = 'wp_og_plugin_settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'OG Image Settings', 'wp-og-plugin' ),
			__( 'OG Images', 'wp-og-plugin' ),
			'manage_options',
			'wp-og-plugin',
			array( $this, 'render_page' )
		);
	}

	public function register_settings(): void {
		register_setting(
			'wp_og_plugin_group',
			self::OPTION_KEY,
			array( $this, 'sanitize_options' )
		);
	}

	public function sanitize_options( array $input ): array {
		$clean = array();

		$clean['api_url']      = esc_url_raw( $input['api_url'] ?? 'https://satori-og.vercel.app/api/og' );
		$clean['logo']         = esc_url_raw( $input['logo'] ?? '' );
		$clean['bg_color']     = sanitize_hex_color( $input['bg_color'] ?? '#0E252C' );
		$clean['text_color']   = sanitize_hex_color( $input['text_color'] ?? '#FFFFFF' );
		$clean['accent_color'] = sanitize_hex_color( $input['accent_color'] ?? '#98CC3F' );
		$clean['cta']          = sanitize_text_field( $input['cta'] ?? '' );

		$allowed_templates = array( 'default', 'minimal', 'gradient' );
		$clean['template'] = in_array( $input['template'] ?? '', $allowed_templates, true )
			? $input['template']
			: 'default';

		$allowed_fonts = array( 'inter', 'montserrat', 'opensans', 'playfair' );
		$clean['font'] = in_array( $input['font'] ?? '', $allowed_fonts, true )
			? $input['font']
			: 'inter';

		return $clean;
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'woocommerce_page_wp-og-plugin' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'wp-og-plugin-admin',
			WP_OG_PLUGIN_URL . 'assets/admin.css',
			array(),
			WP_OG_PLUGIN_VERSION
		);
	}

	public static function get_options(): array {
		$defaults = array(
			'api_url'      => 'https://satori-og.vercel.app/api/og',
			'logo'         => '',
			'bg_color'     => '#0E252C',
			'text_color'   => '#FFFFFF',
			'accent_color' => '#98CC3F',
			'template'     => 'default',
			'font'         => 'inter',
			'cta'          => 'Ver producto',
		);

		$saved = get_option( self::OPTION_KEY, array() );

		return wp_parse_args( $saved, $defaults );
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$opts = self::get_options();

		$preview_url = add_query_arg(
			array(
				'title'       => urlencode( 'Producto de ejemplo' ),
				'price'       => urlencode( '$19.990' ),
				'salePrice'   => urlencode( '$14.990' ),
				'logo'        => urlencode( $opts['logo'] ),
				'bgColor'     => urlencode( $opts['bg_color'] ),
				'textColor'   => urlencode( $opts['text_color'] ),
				'accentColor' => urlencode( $opts['accent_color'] ),
				'template'    => urlencode( $opts['template'] ),
				'font'        => urlencode( $opts['font'] ),
				'cta'         => urlencode( $opts['cta'] ),
			),
			esc_url( $opts['api_url'] )
		);
		?>
		<div class="wrap wp-og-wrap">
			<h1><?php esc_html_e( 'OG Image Settings', 'wp-og-plugin' ); ?></h1>

			<div class="wp-og-layout">
				<div class="wp-og-form-col">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'wp_og_plugin_group' );
						wp_nonce_field( 'wp_og_plugin_save', 'wp_og_nonce' );
						?>

						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="wp_og_api_url"><?php esc_html_e( 'API URL', 'wp-og-plugin' ); ?></label>
								</th>
								<td>
									<input
										type="url"
										id="wp_og_api_url"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[api_url]"
										value="<?php echo esc_attr( $opts['api_url'] ); ?>"
										class="regular-text"
									>
									<p class="description"><?php esc_html_e( 'Satori OG API endpoint.', 'wp-og-plugin' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wp_og_logo"><?php esc_html_e( 'Logo URL', 'wp-og-plugin' ); ?></label>
								</th>
								<td>
									<input
										type="url"
										id="wp_og_logo"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[logo]"
										value="<?php echo esc_attr( $opts['logo'] ); ?>"
										class="regular-text"
									>
									<p class="description"><?php esc_html_e( 'URL completa al logo de la tienda.', 'wp-og-plugin' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wp_og_bg_color"><?php esc_html_e( 'Color de fondo', 'wp-og-plugin' ); ?></label>
								</th>
								<td>
									<input
										type="color"
										id="wp_og_bg_color"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[bg_color]"
										value="<?php echo esc_attr( $opts['bg_color'] ); ?>"
									>
									<input
										type="text"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[bg_color]"
										value="<?php echo esc_attr( $opts['bg_color'] ); ?>"
										class="wp-og-hex-input"
										maxlength="7"
										pattern="#[0-9A-Fa-f]{6}"
									>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wp_og_text_color"><?php esc_html_e( 'Color de texto', 'wp-og-plugin' ); ?></label>
								</th>
								<td>
									<input
										type="color"
										id="wp_og_text_color"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[text_color]"
										value="<?php echo esc_attr( $opts['text_color'] ); ?>"
									>
									<input
										type="text"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[text_color]"
										value="<?php echo esc_attr( $opts['text_color'] ); ?>"
										class="wp-og-hex-input"
										maxlength="7"
										pattern="#[0-9A-Fa-f]{6}"
									>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wp_og_accent_color"><?php esc_html_e( 'Color de acento', 'wp-og-plugin' ); ?></label>
								</th>
								<td>
									<input
										type="color"
										id="wp_og_accent_color"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[accent_color]"
										value="<?php echo esc_attr( $opts['accent_color'] ); ?>"
									>
									<input
										type="text"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[accent_color]"
										value="<?php echo esc_attr( $opts['accent_color'] ); ?>"
										class="wp-og-hex-input"
										maxlength="7"
										pattern="#[0-9A-Fa-f]{6}"
									>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wp_og_template"><?php esc_html_e( 'Template', 'wp-og-plugin' ); ?></label>
								</th>
								<td>
									<select id="wp_og_template" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[template]">
										<?php foreach ( array( 'default', 'minimal', 'gradient' ) as $t ) : ?>
											<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $opts['template'], $t ); ?>>
												<?php echo esc_html( ucfirst( $t ) ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wp_og_font"><?php esc_html_e( 'Fuente', 'wp-og-plugin' ); ?></label>
								</th>
								<td>
									<select id="wp_og_font" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[font]">
										<?php foreach ( array( 'inter', 'montserrat', 'opensans', 'playfair' ) as $f ) : ?>
											<option value="<?php echo esc_attr( $f ); ?>" <?php selected( $opts['font'], $f ); ?>>
												<?php echo esc_html( ucfirst( $f ) ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wp_og_cta"><?php esc_html_e( 'Texto CTA', 'wp-og-plugin' ); ?></label>
								</th>
								<td>
									<input
										type="text"
										id="wp_og_cta"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[cta]"
										value="<?php echo esc_attr( $opts['cta'] ); ?>"
										class="regular-text"
										maxlength="50"
									>
									<p class="description"><?php esc_html_e( 'Texto pie de imagen, ej: "Ver producto"', 'wp-og-plugin' ); ?></p>
								</td>
							</tr>
						</table>

						<?php submit_button( __( 'Guardar configuración', 'wp-og-plugin' ) ); ?>
					</form>
				</div>

				<div class="wp-og-preview-col">
					<h2><?php esc_html_e( 'Vista previa', 'wp-og-plugin' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Guarda los cambios para actualizar la vista previa.', 'wp-og-plugin' ); ?></p>
					<div class="wp-og-preview-frame">
						<img
							src="<?php echo esc_url( $preview_url ); ?>"
							alt="<?php esc_attr_e( 'OG image preview', 'wp-og-plugin' ); ?>"
							width="600"
							height="315"
							loading="lazy"
						>
					</div>
					<p class="description">
						<a href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Abrir imagen completa (1200×630)', 'wp-og-plugin' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}
}
