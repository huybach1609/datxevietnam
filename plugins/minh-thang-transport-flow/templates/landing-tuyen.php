<?php
/**
 * Landing template: /tuyen/{slug}/
 *
 * @package Minh_Thang_Transport_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$term = get_queried_object();
if ( ! $term instanceof WP_Term || MTTF_Landing_Taxonomies::TAX_TUYEN !== $term->taxonomy ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}

MTTF_Landing_Template::render_tuyen_landing( $term );
