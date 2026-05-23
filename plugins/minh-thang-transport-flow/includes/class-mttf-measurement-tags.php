<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Chèn snippet GTM và Google tag GA4 đúng theo hướng dẫn của Google (head + noscript trong body).
 *
 * @link https://developers.google.com/tag-platform/tag-manager/web
 */
class MTTF_Measurement_Tags {
	private static $gtm_noscript_printed = false;

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'print_head_scripts' ), 1 );
		add_action( 'wp_body_open', array( __CLASS__, 'print_gtm_body_noscript' ), 1 );
		add_action( 'wp_footer', array( __CLASS__, 'print_gtm_noscript_footer_fallback' ), 5 );
	}

	/**
	 * @param string $reason Context for skipping (debug).
	 * @return bool
	 */
	private static function should_skip_output() {
		if ( is_admin() ) {
			return true;
		}

		if ( wp_is_json_request() || wp_doing_ajax() ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( is_preview() ) {
			return true;
		}

		if ( is_feed() ) {
			return true;
		}

		return false;
	}

	public static function print_head_scripts() {
		if ( self::should_skip_output() ) {
			return;
		}

		if ( ! apply_filters( 'mttf_output_measurement_snippets', true ) ) {
			return;
		}

		$gtm_id = MTTF_Settings::get_saved_gtm_container_id();
		$ga_id  = MTTF_Settings::get_saved_ga4_measurement_id();

		if ( '' === $gtm_id && '' === $ga_id ) {
			return;
		}

		$dual_gtag = (int) MTTF_Settings::get( 'measurement_ga4_gtag_with_gtm', 0 ) === 1;

		if ( '' !== $gtm_id ) {
			self::print_gtm_js( $gtm_id );
		}

		$inject_gtag = '' !== $ga_id && ( '' === $gtm_id || $dual_gtag );

		if ( $inject_gtag ) {
			self::print_gtag_js( $ga_id );
		}
	}

	public static function print_gtm_body_noscript() {
		if ( self::should_skip_output() ) {
			return;
		}

		if ( ! apply_filters( 'mttf_output_measurement_snippets', true ) ) {
			return;
		}

		$gtm_id = MTTF_Settings::get_saved_gtm_container_id();
		if ( '' === $gtm_id ) {
			return;
		}

		$iframe_src = sprintf(
			'https://www.googletagmanager.com/ns.html?id=%s',
			rawurlencode( $gtm_id )
		);
		self::$gtm_noscript_printed = true;
		?>
		<!-- Google Tag Manager (noscript) — MTTF -->
		<noscript><iframe src="<?php echo esc_url( $iframe_src ); ?>" height="0" width="0" style="display:none;visibility:hidden" aria-hidden="true"></iframe></noscript>
		<!-- End Google Tag Manager (noscript) -->
		<?php
	}

	/**
	 * Một số theme cũ không gọi {@see 'wp_body_open'}; GTM vẫn nên có noscript.
	 */
	public static function print_gtm_noscript_footer_fallback() {
		if ( self::$gtm_noscript_printed ) {
			return;
		}

		if ( self::should_skip_output() ) {
			return;
		}

		if ( ! apply_filters( 'mttf_output_measurement_snippets', true ) ) {
			return;
		}

		$gtm_id = MTTF_Settings::get_saved_gtm_container_id();
		if ( '' === $gtm_id ) {
			return;
		}

		$iframe_src = sprintf(
			'https://www.googletagmanager.com/ns.html?id=%s',
			rawurlencode( $gtm_id )
		);
		self::$gtm_noscript_printed = true;
		?>
		<!-- Google Tag Manager (noscript) — MTTF footer fallback -->
		<noscript><iframe src="<?php echo esc_url( $iframe_src ); ?>" height="0" width="0" style="display:none;visibility:hidden" aria-hidden="true"></iframe></noscript>
		<?php
	}

	/**
	 * @param string $container_id Canonical GTM-… string.
	 */
	private static function print_gtm_js( $container_id ) {
		$id_json = wp_json_encode(
			$container_id,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);

		echo "\n";
		echo "<!-- Google Tag Manager — MTTF -->\n";
		echo '<script id="google-tag-manager-mttf">';
		echo '(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':' .
			'new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],' .
			'j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=' .
			'\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;' .
			'f.parentNode.insertBefore(j,f);})(window,document,\'script\',\'dataLayer\',' . $id_json . ');';
		echo '</script>';
		echo "\n<!-- End Google Tag Manager — MTTF -->\n";
	}

	/**
	 * @param string $measurement_id Canonical G-… string.
	 */
	private static function print_gtag_js( $measurement_id ) {
		$id_json       = wp_json_encode(
			$measurement_id,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
		$gtag_js_query = sprintf(
			'https://www.googletagmanager.com/gtag/js?id=%s',
			rawurlencode( $measurement_id )
		);

		echo "\n";
		echo '<!-- Google tag (gtag.js) — GA4 — MTTF -->' . "\n";
		printf(
			'<script async src="%s"></script>' . "\n",
			esc_url( $gtag_js_query, array( 'https', 'http' ) )
		);
		echo '<script id="google-gtag-mttf">' . "\n";
		echo 'window.dataLayer = window.dataLayer || [];';
		echo 'function gtag(){dataLayer.push(arguments);}' . "\n";
		echo 'gtag(\'js\', new Date());' . "\n";
		echo 'gtag(\'config\', ' . $id_json . ');';
		echo "\n</script>\n";
		echo '<!-- End Google tag — MTTF -->' . "\n";
	}
}
