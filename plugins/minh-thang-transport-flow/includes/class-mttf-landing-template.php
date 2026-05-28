<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public landing templates: /tuyen/{slug}/ and /nha-xe/{slug}/.
 */
class MTTF_Landing_Template {
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		if ( is_tax( array( MTTF_Landing_Taxonomies::TAX_TUYEN, MTTF_Landing_Taxonomies::TAX_NHA_XE ) ) ) {
			$classes[] = 'mttf-landing-page';
		}

		return $classes;
	}

	/**
	 * @param string $template Path.
	 * @return string
	 */
	public static function template_include( $template ) {
		if ( is_tax( MTTF_Landing_Taxonomies::TAX_TUYEN ) ) {
			$located = self::locate_template( 'landing-tuyen.php' );
			return $located ? $located : $template;
		}

		if ( is_tax( MTTF_Landing_Taxonomies::TAX_NHA_XE ) ) {
			$located = self::locate_template( 'landing-nha-xe.php' );
			return $located ? $located : $template;
		}

		return $template;
	}

	public static function enqueue_assets() {
		if ( ! is_tax( array( MTTF_Landing_Taxonomies::TAX_TUYEN, MTTF_Landing_Taxonomies::TAX_NHA_XE ) ) ) {
			return;
		}

		if ( class_exists( 'MTTF_Performance', false ) ) {
			MTTF_Performance::enqueue_public_assets();
		} else {
			MTTF_Shortcode::enqueue_frontend_assets( 'landing' );
		}

		$path = MTTF_PATH . 'assets/css/landing.css';
		wp_enqueue_style(
			'mttf-landing',
			MTTF_URL . 'assets/css/landing.css',
			array( 'mttf-frontend' ),
			file_exists( $path ) ? (string) filemtime( $path ) : MTTF_VERSION
		);

		if ( is_tax( array( MTTF_Landing_Taxonomies::TAX_TUYEN, MTTF_Landing_Taxonomies::TAX_NHA_XE ) ) ) {
			$js_path = MTTF_PATH . 'assets/js/landing-lead.js';
			wp_enqueue_script(
				'mttf-landing-lead',
				MTTF_URL . 'assets/js/landing-lead.js',
				array(),
				file_exists( $js_path ) ? (string) filemtime( $js_path ) : MTTF_VERSION,
				true
			);
		}

		if ( is_tax( MTTF_Landing_Taxonomies::TAX_NHA_XE ) ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$groups  = MTTF_Landing_Query::group_routes_by_tuyen(
					MTTF_Landing_Query::get_routes_for_nha_xe( $term )
				);
				$filters = array();
				foreach ( $groups as $group ) {
					$tuyen_term = $group['term'] ?? null;
					if ( ! $tuyen_term instanceof WP_Term ) {
						continue;
					}
					$filters[] = array(
						'id'   => (int) $tuyen_term->term_id,
						'slug' => (string) $tuyen_term->slug,
						'name' => (string) $tuyen_term->name,
					);
				}

				$nx_js = MTTF_PATH . 'assets/js/landing-nha-xe.js';
				wp_enqueue_script(
					'mttf-landing-nha-xe',
					MTTF_URL . 'assets/js/landing-nha-xe.js',
					array( 'mttf-frontend' ),
					file_exists( $nx_js ) ? (string) filemtime( $nx_js ) : MTTF_VERSION,
					true
				);

				wp_localize_script(
					'mttf-landing-nha-xe',
					'mttfNhaXeLanding',
					array(
						'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
						'nonce'        => wp_create_nonce( 'mttf_nha_xe_filter' ),
						'action'       => 'mttf_nha_xe_filter_routes',
						'nhaXeId'      => (int) $term->term_id,
						'nhaXeSlug'    => (string) $term->slug,
						'tuyenFilters' => $filters,
					)
				);
			}
		}
	}

	/**
	 * @param string $name Template basename in templates/.
	 * @return string|false
	 */
	public static function locate_template( $name ) {
		$child = get_stylesheet_directory() . '/mttf/' . $name;
		if ( file_exists( $child ) ) {
			return $child;
		}

		$theme = get_template_directory() . '/mttf/' . $name;
		if ( file_exists( $theme ) ) {
			return $theme;
		}

		$plugin = MTTF_PATH . 'templates/' . $name;
		if ( file_exists( $plugin ) ) {
			return $plugin;
		}

		return false;
	}

	/**
	 * @param WP_Term $term Queried tuyen term.
	 */
	public static function render_tuyen_landing( $term ) {
		$routes = MTTF_Landing_Query::get_routes_for_tuyen( $term );

		get_header();
		MTTF_Landing_Tuyen::render_page( $term, $routes );
		get_footer();
	}

	/**
	 * @param WP_Term $term Queried nhà xe term.
	 */
	public static function render_nha_xe_landing( $term ) {
		$routes = MTTF_Landing_Query::get_routes_for_nha_xe( $term );

		get_header();
		MTTF_Landing_Nha_Xe::render_page( $term, $routes );
		get_footer();
	}
}
