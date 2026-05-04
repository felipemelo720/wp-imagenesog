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
		$clean['bg_color']     = sanitize_hex_color( $input['bg_color'] ?? '' ) ?: '#0E252C';
		$clean['text_color']   = sanitize_hex_color( $input['text_color'] ?? '' ) ?: '#FFFFFF';
		$clean['accent_color'] = sanitize_hex_color( $input['accent_color'] ?? '' ) ?: '#98CC3F';
		$clean['cta']          = sanitize_text_field( $input['cta'] ?? '' );
		$clean['badge']        = sanitize_text_field( $input['badge'] ?? '' );
		$clean['locale']       = sanitize_text_field( $input['locale'] ?? 'es_CL' );

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
			'badge'        => '',
			'locale'       => 'es_CL',
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
						<?php settings_fields( 'wp_og_plugin_group' ); ?>

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
										value="<?php echo esc_attr( $opts['bg_color'] ); ?>"
									>
									<input
										type="text"
										id="wp_og_bg_color_text"
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
										value="<?php echo esc_attr( $opts['text_color'] ); ?>"
									>
									<input
										type="text"
										id="wp_og_text_color_text"
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
										value="<?php echo esc_attr( $opts['accent_color'] ); ?>"
									>
									<input
										type="text"
										id="wp_og_accent_color_text"
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
							<tr>
								<th scope="row">
									<label for="wp_og_badge"><?php esc_html_e( 'Badge', 'wp-og-plugin' ); ?></label>
								</th>
								<td>
									<input
										type="text"
										id="wp_og_badge"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[badge]"
										value="<?php echo esc_attr( $opts['badge'] ); ?>"
										class="regular-text"
										maxlength="30"
									>
									<p class="description"><?php esc_html_e( 'Texto de badge sobre la imagen, ej: "OFERTA", "NUEVO". Dejar vacío para no mostrar.', 'wp-og-plugin' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wp_og_locale"><?php esc_html_e( 'og:locale', 'wp-og-plugin' ); ?></label>
								</th>
								<td>
									<input
										type="text"
										id="wp_og_locale"
										name="<?php echo esc_attr( self::OPTION_KEY ); ?>[locale]"
										value="<?php echo esc_attr( $opts['locale'] ); ?>"
										class="small-text"
										maxlength="10"
										placeholder="es_CL"
									>
									<p class="description"><?php esc_html_e( 'Locale para og:locale, ej: es_CL, en_US, es_ES.', 'wp-og-plugin' ); ?></p>
								</td>
							</tr>
						</table>

						<?php submit_button( __( 'Guardar configuración', 'wp-og-plugin' ) ); ?>
					</form>
				</div>

				<div class="wp-og-preview-col">
					<h2><?php esc_html_e( 'Vista previa', 'wp-og-plugin' ); ?></h2>
					<div class="wp-og-preview-frame">
						<div id="wp-og-preview-status" class="wp-og-preview-loading">
							<?php esc_html_e( 'Cargando…', 'wp-og-plugin' ); ?>
						</div>
						<img
							id="wp-og-preview-img"
							src="<?php echo esc_url( $preview_url ); ?>"
							alt="<?php esc_attr_e( 'OG image preview', 'wp-og-plugin' ); ?>"
							width="600"
							height="315"
							style="display:none;"
						>
					</div>
					<p>
						<button type="button" id="wp-og-preview-btn" class="button">
							<?php esc_html_e( 'Actualizar vista previa', 'wp-og-plugin' ); ?>
						</button>
						<a id="wp-og-preview-link" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener" style="margin-left:12px;">
							<?php esc_html_e( 'Abrir imagen completa', 'wp-og-plugin' ); ?>
						</a>
					</p>
					<p id="wp-og-preview-error" class="wp-og-preview-error" style="display:none;">
						<?php esc_html_e( 'No se pudo cargar la imagen. Verifica la URL del API.', 'wp-og-plugin' ); ?>
					</p>
				</div>
			</div>
		</div>
		<script>
		(function() {
			// ── Color picker sync ──────────────────────────────────────────
			var pairs = [
				['wp_og_bg_color',     'wp_og_bg_color_text'],
				['wp_og_text_color',   'wp_og_text_color_text'],
				['wp_og_accent_color', 'wp_og_accent_color_text'],
			];
			pairs.forEach(function(pair) {
				var picker = document.getElementById(pair[0]);
				var text   = document.getElementById(pair[1]);
				if (!picker || !text) return;
				picker.addEventListener('input', function() { text.value = picker.value; });
				text.addEventListener('input', function() {
					if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) {
						picker.value = text.value;
					}
				});
			});

			// ── Live preview ───────────────────────────────────────────────
			var img    = document.getElementById('wp-og-preview-img');
			var status = document.getElementById('wp-og-preview-status');
			var error  = document.getElementById('wp-og-preview-error');
			var link   = document.getElementById('wp-og-preview-link');
			var btn    = document.getElementById('wp-og-preview-btn');

			function val(id) {
				var el = document.getElementById(id);
				return el ? el.value : '';
			}

			function buildUrl() {
				var base = val('wp_og_api_url');
				if (!base) return '';
				var params = {
					title:       'Producto de ejemplo',
					price:       '$19.990',
					salePrice:   '$14.990',
					logo:        val('wp_og_logo'),
					bgColor:     val('wp_og_bg_color_text'),
					textColor:   val('wp_og_text_color_text'),
					accentColor: val('wp_og_accent_color_text'),
					template:    val('wp_og_template'),
					font:        val('wp_og_font'),
					cta:         val('wp_og_cta'),
					badge:       val('wp_og_badge'),
				};
				var qs = Object.keys(params)
					.filter(function(k) { return params[k] !== ''; })
					.map(function(k) { return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]); })
					.join('&');
				return base + (base.indexOf('?') === -1 ? '?' : '&') + qs;
			}

			function loadPreview() {
				var url = buildUrl();
				if (!url) {
					status.textContent   = '<?php echo esc_js( __( 'Ingresa una URL de API válida.', 'wp-og-plugin' ) ); ?>';
					status.style.display = 'block';
					img.style.display    = 'none';
					error.style.display  = 'none';
					return;
				}

				img.style.display    = 'none';
				error.style.display  = 'none';
				status.style.display = 'block';
				status.textContent   = '<?php echo esc_js( __( 'Cargando…', 'wp-og-plugin' ) ); ?>';

				var fresh = new Image();
				fresh.onload = function() {
					img.src              = url;
					img.style.display    = 'block';
					status.style.display = 'none';
					error.style.display  = 'none';
					if (link) link.href  = url;
				};
				fresh.onerror = function() {
					img.style.display    = 'none';
					status.style.display = 'none';
					error.style.display  = 'block';
				};
				fresh.src = url;
			}

			if (btn) btn.addEventListener('click', loadPreview);

			// Cargar automáticamente al abrir la página
			loadPreview();
		})();
		</script>
		<?php
	}
}
