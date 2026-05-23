<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the floating bar should load on the current request.
 *
 * Hidden on the cabin booking page (/dat-ve/dat-ve-cabin/) and on any request whose
 * slug matches the excluded slugs saved in the plugin settings.
 *
 * @return bool
 */
function wfcb_should_display_bar() {
	$show = true;

	if ( function_exists( 'is_page' ) && is_page( 'dat-ve-cabin' ) ) {
		$show = false;
	} elseif ( wfcb_request_matches_excluded_slug() ) {
		$show = false;
	}

	/**
	 * @param bool $show Whether to show the bar.
	 */
	return (bool) apply_filters( 'wfcb_should_display_bar', $show );
}

/**
 * Get the configured excluded slugs.
 *
 * @return array
 */
function wfcb_get_excluded_slugs() {
	$options = get_option( 'wfcb_settings', array() );
	$slugs   = isset( $options['excluded_slugs'] ) && is_array( $options['excluded_slugs'] ) ? $options['excluded_slugs'] : array();

	$normalized = array();

	foreach ( $slugs as $slug ) {
		$slug = sanitize_title( wp_unslash( $slug ) );

		if ( '' !== $slug ) {
			$normalized[] = $slug;
		}
	}

	return array_values( array_unique( $normalized ) );
}

/**
 * Get slug candidates from the current request.
 *
 * @return array
 */
function wfcb_get_current_request_slugs() {
	$slugs = array();

	if ( function_exists( 'is_singular' ) && is_singular() ) {
		$queried_object = get_queried_object();

		if ( $queried_object instanceof WP_Post && ! empty( $queried_object->post_name ) ) {
			$slugs[] = sanitize_title( $queried_object->post_name );
		}
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$request_path = wp_parse_url( $request_uri, PHP_URL_PATH );

	if ( ! empty( $request_path ) ) {
		$path_parts = array_filter( explode( '/', trim( $request_path, '/' ) ) );

		foreach ( $path_parts as $path_part ) {
			$slug = sanitize_title( $path_part );

			if ( '' !== $slug ) {
				$slugs[] = $slug;
			}
		}
	}

	return array_values( array_unique( $slugs ) );
}

/**
 * Determine whether the current request matches any excluded slug.
 *
 * @return bool
 */
function wfcb_request_matches_excluded_slug() {
	$excluded_slugs = wfcb_get_excluded_slugs();

	if ( empty( $excluded_slugs ) ) {
		return false;
	}

	$current_slugs = wfcb_get_current_request_slugs();

	return ! empty( array_intersect( $excluded_slugs, $current_slugs ) );
}

/**
 * Enqueue frontend assets.
 */
function wfcb_enqueue_frontend_assets() {
	$options = get_option( 'wfcb_settings', array() );

	if ( empty( $options['enabled'] ) || ! wfcb_should_display_bar() ) {
		return;
	}

	wp_enqueue_style(
		'wfcb-frontend',
		WFCB_PLUGIN_URL . 'assets/frontend.css',
		array(),
		WFCB_VERSION
	);

	wp_enqueue_script(
		'wfcb-frontend',
		WFCB_PLUGIN_URL . 'assets/frontend.js',
		array(),
		WFCB_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'wfcb_enqueue_frontend_assets' );

/**
 * Render floating contact bar.
 */
function wfcb_render_bar() {
	$options = get_option( 'wfcb_settings', array() );

	if ( empty( $options['enabled'] ) || ! wfcb_should_display_bar() ) {
		return;
	}

	$items = isset( $options['items'] ) && is_array( $options['items'] ) ? $options['items'] : array();

	if ( empty( $items ) ) {
		return;
	}

	$position = isset( $options['position'] ) ? $options['position'] : 'right';
	$position = in_array( $position, array( 'left', 'right', 'bottom' ), true ) ? $position : 'right';

	$wrapper_classes = array(
		'wfcb-bar',
		'wfcb-position-' . $position,
	);

	$wrapper_class_attr = implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) );
	?>
	<div class="<?php echo esc_attr( $wrapper_class_attr ); ?>">
		<ul class="wfcb-items">
			<?php foreach ( $items as $item ) : ?>
				<?php
				$label            = isset( $item['label'] ) ? $item['label'] : '';
				$icon_url         = isset( $item['icon_url'] ) ? $item['icon_url'] : '';
				$link_url         = isset( $item['link_url'] ) ? $item['link_url'] : '';
				$app_key          = isset( $item['app_key'] ) ? sanitize_key( $item['app_key'] ) : '';
				$desktop_behavior = isset( $item['desktop_behavior'] ) ? sanitize_key( $item['desktop_behavior'] ) : 'link';
				$qr_page_url      = '';

				if ( 'qr_page' === $desktop_behavior && '' !== $app_key ) {
					$qr_page_url = home_url( '/quet-ma-qr/#' . rawurlencode( $app_key ) );
				}

				if ( '' === $label && '' === $icon_url && '' === $link_url ) {
					continue;
				}

				$initial_href   = $link_url ? $link_url : ( $qr_page_url ? $qr_page_url : '' );
				$is_external    = $initial_href && 0 !== strpos( $initial_href, home_url() );
				$initial_target = $is_external ? '_blank' : '_self';
				?>
				<li class="wfcb-item">
					<a
						class="wfcb-link"
						href="<?php echo esc_url( $initial_href ); ?>"
						target="<?php echo esc_attr( $initial_target ); ?>"
						rel="noopener noreferrer"
						data-wfcb-link-url="<?php echo esc_url( $link_url ); ?>"
						data-wfcb-link-target="_blank"
						data-wfcb-qr-page-url="<?php echo esc_url( $qr_page_url ); ?>"
						data-wfcb-qr-page-target="_self"
					>
						<?php if ( $icon_url ) : ?>
							<span class="wfcb-icon">
								<img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $label ); ?>" loading="lazy" />
							</span>
						<?php endif; ?>
						<?php if ( $label ) : ?>
							<span class="wfcb-label"><?php echo esc_html( $label ); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}
add_action( 'wp_footer', 'wfcb_render_bar' );

/**
 * Shortcode: render QR cards horizontal list.
 *
 * Usage: [wfcb_qr_cards]
 *
 * @return string
 */
function wfcb_shortcode_qr_cards() {
	$options = get_option( 'wfcb_settings', array() );
	$items   = isset( $options['items'] ) && is_array( $options['items'] ) ? $options['items'] : array();

	if ( empty( $items ) ) {
		return '';
	}

	wp_enqueue_style(
		'wfcb-qr-cards',
		WFCB_PLUGIN_URL . 'assets/qr-cards.css',
		array(),
		WFCB_VERSION
	);

	wp_enqueue_script(
		'wfcb-qr-cards',
		WFCB_PLUGIN_URL . 'assets/qr-cards.js',
		array(),
		WFCB_VERSION,
		true
	);

	$wrapper_id = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'wfcb-qr-cards-' ) : ( 'wfcb-qr-cards-' . wp_rand( 1000, 9999 ) );

	ob_start();
	?>
	<div class="wfcb-qr-cards" id="<?php echo esc_attr( $wrapper_id ); ?>">
		<div class="scroll-track" data-wfcb-qr-track>
			<?php foreach ( $items as $item ) : ?>
				<?php
				$app_key      = isset( $item['app_key'] ) ? sanitize_key( $item['app_key'] ) : '';
				$label        = isset( $item['label'] ) ? $item['label'] : '';
				$handle       = isset( $item['handle'] ) ? $item['handle'] : '';
				$icon_url     = isset( $item['icon_url'] ) ? $item['icon_url'] : '';
				$qr_image_url = isset( $item['qr_image_url'] ) ? $item['qr_image_url'] : '';
				$scan_hint    = isset( $item['scan_hint'] ) ? $item['scan_hint'] : '';
				$accent_color = isset( $item['accent_color'] ) ? sanitize_hex_color( $item['accent_color'] ) : '';

				if ( '' === $app_key ) {
					continue;
				}

				$card_title = $label ? $label : strtoupper( $app_key );
				$scan_hint  = $scan_hint ? $scan_hint : __( 'Quét mã để kết bạn', 'wfcb' );
				?>
				<div
					class="card"
					data-app="<?php echo esc_attr( $app_key ); ?>"
					id="<?php echo esc_attr( $app_key ); ?>"
					<?php if ( $accent_color ) : ?>
						style="background: <?php echo esc_attr( $accent_color ); ?>;"
					<?php endif; ?>
				>
					<div class="card-deco"></div><div class="card-deco2"></div>
					<div class="card-inner">
						<div class="app-label">
							<div class="app-icon">
								<?php if ( $icon_url ) : ?>
									<img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $card_title ); ?>" loading="lazy" />
								<?php else : ?>
									<span class="wfcb-qr-fallback-icon" aria-hidden="true"></span>
								<?php endif; ?>
							</div>
							<span class="app-name"><?php echo esc_html( $card_title ); ?></span>
							<?php if ( $handle ) : ?>
								<span class="app-handle"><?php echo esc_html( $handle ); ?></span>
							<?php endif; ?>
						</div>
						<div class="qr-frame">
							<?php if ( $qr_image_url ) : ?>
								<img src="<?php echo esc_url( $qr_image_url ); ?>" alt="<?php echo esc_attr( $card_title . ' QR' ); ?>" loading="lazy" />
							<?php else : ?>
								<div class="qr-placeholder">
									<div>
										<?php echo esc_html__( 'Thêm QR của bạn vào đây', 'wfcb' ); ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
						<div
							class="scan-hint"
							role="button"
							tabindex="0"
							data-wfcb-copy-text="<?php echo esc_attr( $handle ? $handle : $card_title ); ?>"
							data-wfcb-copied-label="<?php echo esc_attr__( 'Copied', 'wfcb' ); ?>"
							aria-label="<?php echo esc_attr__( 'Copy', 'wfcb' ); ?>"
						>
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy-icon lucide-copy"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
							<?php echo esc_html( $scan_hint ); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="dots" data-wfcb-qr-dots></div>
	</div>
	<?php

	return (string) ob_get_clean();
}
add_shortcode( 'wfcb_qr_cards', 'wfcb_shortcode_qr_cards' );
