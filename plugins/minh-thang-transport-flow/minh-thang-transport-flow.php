<?php

/**

 * Plugin Name: Minh Thang Transport Flow

 * Description: Hub card booking flow for limousine routes with quick lead capture.

 * Version: 1.3.0

 * Author: Minh Thang

 * Text Domain: minh-thang-transport-flow

 */



if ( ! defined( 'ABSPATH' ) ) {

	exit;

}



define( 'MTTF_VERSION', '1.3.0' );

define( 'MTTF_PATH', plugin_dir_path( __FILE__ ) );

define( 'MTTF_URL', plugin_dir_url( __FILE__ ) );



require_once MTTF_PATH . 'includes/class-mttf-cpt.php';

require_once MTTF_PATH . 'includes/class-mttf-metabox.php';

require_once MTTF_PATH . 'includes/class-mttf-shortcode.php';

require_once MTTF_PATH . 'includes/class-mttf-settings.php';

require_once MTTF_PATH . 'includes/class-mttf-measurement-tags.php';

require_once MTTF_PATH . 'includes/class-mttf-lead-db.php';

require_once MTTF_PATH . 'includes/class-mttf-lead-admin.php';

require_once MTTF_PATH . 'includes/class-mttf-ajax.php';

require_once MTTF_PATH . 'includes/class-mttf-lead-notify.php';

require_once MTTF_PATH . 'includes/class-mttf-activity-pings.php';

require_once MTTF_PATH . 'includes/class-mttf-contact-counter.php';

require_once MTTF_PATH . 'includes/class-mttf-landing-taxonomies.php';

require_once MTTF_PATH . 'includes/class-mttf-landing-validation.php';

require_once MTTF_PATH . 'includes/class-mttf-landing-query.php';

require_once MTTF_PATH . 'includes/class-mttf-performance.php';

require_once MTTF_PATH . 'includes/class-mttf-landing-template.php';

require_once MTTF_PATH . 'includes/class-mttf-landing-tuyen.php';

require_once MTTF_PATH . 'includes/class-mttf-landing-nha-xe.php';

require_once MTTF_PATH . 'includes/class-mttf-landing-seo.php';

require_once MTTF_PATH . 'includes/class-mttf-landing-term-seo-admin.php';



register_activation_hook(

	__FILE__,

	static function () {

		MTTF_Contact_Counter::activate();

		MTTF_Lead_DB::activate();

		MTTF_Landing_Taxonomies::activate();

	}

);

register_deactivation_hook( __FILE__, array( 'MTTF_Contact_Counter', 'deactivate' ) );



add_action(

	'plugins_loaded',

	static function () {

		MTTF_CPT::init();

		MTTF_Metabox::init();

		MTTF_Shortcode::init();

		MTTF_Settings::init();

		MTTF_Measurement_Tags::init();

		MTTF_Lead_DB::init();

		MTTF_Lead_Admin::init();

		MTTF_Ajax::init();

		MTTF_Activity_Pings::init();

		MTTF_Contact_Counter::init();

		MTTF_Landing_Taxonomies::init();

		MTTF_Landing_Validation::init();

		MTTF_Landing_Template::init();

		MTTF_Performance::init();

		MTTF_Landing_Nha_Xe::init();

		MTTF_Landing_SEO::init();

		MTTF_Landing_Term_SEO_Admin::init();

	}

);

