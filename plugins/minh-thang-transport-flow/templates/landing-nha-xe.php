<?php
/**
 * Landing template: /nha-xe/{slug}/
 *
 * @package Minh_Thang_Transport_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$term = get_queried_object();
if ( ! $term instanceof WP_Term || MTTF_Landing_Taxonomies::TAX_NHA_XE !== $term->taxonomy ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}

MTTF_Landing_Template::render_nha_xe_landing( $term );
