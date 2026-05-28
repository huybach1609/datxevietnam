<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query active tuyen_xe cards for landing taxonomies.
 */
class MTTF_Landing_Query {

	/**
	 * @param WP_Term|int $term Term object or ID.
	 * @return WP_Post[]
	 */
	public static function get_routes_for_tuyen( $term ) {
		if ( class_exists( 'MTTF_Performance', false ) ) {
			return MTTF_Performance::get_routes_for_tuyen( $term );
		}

		$term = self::resolve_term( $term, MTTF_Landing_Taxonomies::TAX_TUYEN );
		if ( ! $term ) {
			return array();
		}

		return self::query_routes_for_tax(
			array(
				array(
					'taxonomy' => MTTF_Landing_Taxonomies::TAX_TUYEN,
					'field'    => 'term_id',
					'terms'    => array( (int) $term->term_id ),
				),
			)
		);
	}

	/**
	 * @param WP_Term|int $term Term object or ID.
	 * @return WP_Post[]
	 */
	public static function get_routes_for_nha_xe( $term ) {
		if ( class_exists( 'MTTF_Performance', false ) ) {
			return MTTF_Performance::get_routes_for_nha_xe( $term );
		}

		$term = self::resolve_term( $term, MTTF_Landing_Taxonomies::TAX_NHA_XE );
		if ( ! $term ) {
			return array();
		}

		return self::query_routes_for_tax(
			array(
				array(
					'taxonomy' => MTTF_Landing_Taxonomies::TAX_NHA_XE,
					'field'    => 'term_id',
					'terms'    => array( (int) $term->term_id ),
				),
			)
		);
	}

	/**
	 * Group routes by their mttf_tuyen term (for nhà xe landing).
	 *
	 * @param WP_Post[] $routes Route posts.
	 * @return array<int,array{term:WP_Term,routes:WP_Post[]}>
	 */
	public static function group_routes_by_tuyen( array $routes ) {
		if ( class_exists( 'MTTF_Performance', false ) ) {
			MTTF_Performance::prime_route_term_cache( $routes );
		}

		$groups = array();

		foreach ( $routes as $route ) {
			if ( ! $route instanceof WP_Post ) {
				continue;
			}

			$tuyen_terms = wp_get_post_terms( $route->ID, MTTF_Landing_Taxonomies::TAX_TUYEN );
			if ( is_wp_error( $tuyen_terms ) || empty( $tuyen_terms ) ) {
				continue;
			}

			$tuyen_term = $tuyen_terms[0];
			$term_id    = (int) $tuyen_term->term_id;

			if ( ! isset( $groups[ $term_id ] ) ) {
				$groups[ $term_id ] = array(
					'term'   => $tuyen_term,
					'routes' => array(),
				);
			}

			$groups[ $term_id ]['routes'][] = $route;
		}

		uasort(
			$groups,
			static function( $a, $b ) {
				$name_a = isset( $a['term']->name ) ? (string) $a['term']->name : '';
				$name_b = isset( $b['term']->name ) ? (string) $b['term']->name : '';
				return strcasecmp( $name_a, $name_b );
			}
		);

		return $groups;
	}

	/**
	 * @param WP_Post[] $routes       Routes for current nhà xe.
	 * @param int       $tuyen_term_id Tuyến term ID; 0 = all.
	 * @return WP_Post[]
	 */
	public static function filter_routes_by_tuyen( array $routes, $tuyen_term_id ) {
		$tuyen_term_id = absint( $tuyen_term_id );
		if ( $tuyen_term_id <= 0 ) {
			return $routes;
		}

		$filtered = array();
		foreach ( $routes as $route ) {
			if ( ! $route instanceof WP_Post ) {
				continue;
			}

			$tuyen_ids = wp_get_post_terms( $route->ID, MTTF_Landing_Taxonomies::TAX_TUYEN, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $tuyen_ids ) ) {
				continue;
			}

			if ( in_array( $tuyen_term_id, array_map( 'intval', $tuyen_ids ), true ) ) {
				$filtered[] = $route;
			}
		}

		return $filtered;
	}

	/**
	 * @param array<int,array> $tax_query Tax query clauses.
	 * @return WP_Post[]
	 */
	public static function query_routes_for_tax( array $tax_query ) {
		return self::query_routes( $tax_query );
	}

	/**
	 * @param array<int,array> $tax_query Tax query clauses.
	 * @return WP_Post[]
	 */
	private static function query_routes( array $tax_query ) {
		$args = array(
			'post_type'      => 'tuyen_xe',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'tax_query'      => array_merge(
				array( 'relation' => 'AND' ),
				$tax_query
			),
			'meta_query'     => array(
				array(
					'key'   => '_mttf_is_active',
					'value' => '1',
				),
			),
		);

		$routes = get_posts( $args );

		return MTTF_Shortcode::sort_route_posts( $routes, '' );
	}

	/**
	 * @param WP_Term|int $term     Term or ID.
	 * @param string      $taxonomy Taxonomy slug.
	 * @return WP_Term|null
	 */
	private static function resolve_term( $term, $taxonomy ) {
		if ( $term instanceof WP_Term ) {
			return $term->taxonomy === $taxonomy ? $term : null;
		}

		$term = get_term( absint( $term ), $taxonomy );

		if ( ! $term instanceof WP_Term || is_wp_error( $term ) ) {
			return null;
		}

		return $term;
	}
}
