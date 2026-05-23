<?php
/**
 * Custom site header template.
 *
 * @package GeneratePress_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$menu_id            = 'dxvn-primary-menu';
$has_primary_menu   = has_nav_menu( 'primary' );
$default_hotline    = '0900 000 000';
$hotline_display    = function_exists( 'dxvn_get_header_setting' ) ? dxvn_get_header_setting( 'hotline_number', $default_hotline ) : $default_hotline;
$hotline_href       = preg_replace( '/[^0-9+]/', '', $hotline_display );
$booking_button_text = function_exists( 'dxvn_get_header_setting' ) ? dxvn_get_header_setting( 'booking_button_text', 'Đặt vé ngay' ) : 'Đặt vé ngay';
$booking_button_url  = function_exists( 'dxvn_get_header_setting' ) ? dxvn_get_header_setting( 'booking_button_url', home_url( '/#mttf-hub' ) ) : home_url( '/#mttf-hub' );
?>
<header class="dxvn-header" aria-label="<?php esc_attr_e( 'Site header', 'generatepress' ); ?>">
	<div class="dxvn-header__inner">
		<div class="dxvn-header__brand">
			<?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) : ?>
				<div class="dxvn-header__logo-link">
					<?php the_custom_logo(); ?>
				</div>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="dxvn-header__site-title" rel="home"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="dxvn-header__mobile-lang" aria-label="Language switcher">
			<?php echo do_shortcode( '[gtranslate]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<button class="dxvn-header__toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $menu_id ); ?>">
			<span class="dxvn-header__toggle-bar"></span>
			<span class="dxvn-header__toggle-bar"></span>
			<span class="dxvn-header__toggle-bar"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Mở menu', 'generatepress' ); ?></span>
		</button>

		<div class="dxvn-header__menu-wrap" id="<?php echo esc_attr( $menu_id ); ?>">
			<nav class="dxvn-header__nav" aria-label="<?php esc_attr_e( 'Primary menu', 'generatepress' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'dxvn-header__menu',
						'fallback_cb'    => false,
					)
				);
				?>
			</nav>

			<div class="dxvn-header__actions">
				<div class="dxvn-header__desktop-lang" aria-label="Language switcher">
					<?php echo do_shortcode( '[gtranslate]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<a class="dxvn-header__hotline" href="tel:<?php echo esc_attr( $hotline_href ); ?>">
					<span class="dxvn-header__hotline-label">Hotline</span>
					<span class="dxvn-header__hotline-number"><?php echo esc_html( $hotline_display ); ?></span>
				</a>
				<a class="dxvn-header__cta" href="<?php echo esc_url( $booking_button_url ); ?>">
					<?php echo esc_html( $booking_button_text ); ?>
				</a>
			</div>
		</div>
	</div>
	<?php if ( ! $has_primary_menu ) : ?>
		<p class="dxvn-header__notice">Vui lòng gán menu cho vị trí Primary trong Giao diện > Menu.</p>
	<?php endif; ?>
</header>
