<?php
/**
 * Front page template for the child theme.
 *
 * @package GeneratePress_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div <?php generate_do_attr( 'content' ); ?>>
	<main <?php generate_do_attr( 'main' ); ?>>
		<?php
		/**
		 * generate_before_main_content hook.
		 *
		 * @since 0.1
		 */
		do_action( 'generate_before_main_content' );

		$home_hub_aria_label = dxvn_get_header_setting( 'home_hub_aria_label', 'Danh sách tuyến nổi bật' );
		$benefits            = array();
		$process_steps       = array();
		$commitments         = array();
		$testimonials        = array();
		$faqs                = array();

		for ( $i = 1; $i <= 6; $i++ ) {
			$benefits[] = array(
				'title' => dxvn_get_header_setting( 'home_benefit_' . $i . '_title' ),
				'text'  => dxvn_get_header_setting( 'home_benefit_' . $i . '_text' ),
			);
		}

		for ( $i = 1; $i <= 3; $i++ ) {
			$process_steps[] = array(
				'title' => dxvn_get_header_setting( 'home_process_' . $i . '_title' ),
				'text'  => dxvn_get_header_setting( 'home_process_' . $i . '_text' ),
			);

			$commitments[] = array(
				'title' => dxvn_get_header_setting( 'home_commit_' . $i . '_title' ),
				'text'  => dxvn_get_header_setting( 'home_commit_' . $i . '_text' ),
			);

			$testimonials[] = array(
				'text'   => dxvn_get_header_setting( 'home_testimonial_' . $i . '_text' ),
				'author' => dxvn_get_header_setting( 'home_testimonial_' . $i . '_author' ),
				'status' => dxvn_get_header_setting( 'home_testimonial_' . $i . '_status' ),
			);
		}

		for ( $i = 1; $i <= 5; $i++ ) {
			$faqs[] = array(
				'q' => dxvn_get_header_setting( 'home_faq_' . $i . '_q' ),
				'a' => dxvn_get_header_setting( 'home_faq_' . $i . '_a' ),
			);
		}
		?>

		<section class="dxvn-home-hub" aria-label="<?php echo esc_attr( $home_hub_aria_label ); ?>">
			<?php echo do_shortcode( '[mttf_hub]' ); ?>
		</section>

		<section class="dxvn-home-section dxvn-benefits" aria-labelledby="dxvn-benefits-title">
			<div class="dxvn-home-section__head">
				<span class="dxvn-home-section__eyebrow"><?php echo esc_html( dxvn_get_header_setting( 'home_benefits_eyebrow' ) ); ?></span>
				<h2 id="dxvn-benefits-title" class="dxvn-home-section__title"><?php echo esc_html( dxvn_get_header_setting( 'home_benefits_title' ) ); ?></h2>
				<p class="dxvn-home-section__desc"><?php echo esc_html( dxvn_get_header_setting( 'home_benefits_desc' ) ); ?></p>
			</div>
			<div class="dxvn-home-grid dxvn-home-grid--3">
				<?php foreach ( $benefits as $index => $item ) : ?>
					<article class="dxvn-info-card">
						<div class="dxvn-info-card__icon"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></div>
						<h3 class="dxvn-info-card__title"><?php echo esc_html( $item['title'] ); ?></h3>
						<p class="dxvn-info-card__text"><?php echo esc_html( $item['text'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="dxvn-home-section dxvn-process" aria-labelledby="dxvn-process-title">
			<div class="dxvn-home-section__head">
				<span class="dxvn-home-section__eyebrow"><?php echo esc_html( dxvn_get_header_setting( 'home_process_eyebrow' ) ); ?></span>
				<h2 id="dxvn-process-title" class="dxvn-home-section__title"><?php echo esc_html( dxvn_get_header_setting( 'home_process_title' ) ); ?></h2>
			</div>
			<div class="dxvn-home-grid dxvn-home-grid--3">
				<?php foreach ( $process_steps as $index => $item ) : ?>
					<article class="dxvn-step-card">
						<div class="dxvn-step-card__num"><?php echo esc_html( (string) ( $index + 1 ) ); ?></div>
						<h3 class="dxvn-step-card__title"><?php echo esc_html( $item['title'] ); ?></h3>
						<p class="dxvn-step-card__text"><?php echo esc_html( $item['text'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="dxvn-home-section dxvn-whyus" aria-labelledby="dxvn-whyus-title">
			<div class="dxvn-home-section__head">
				<span class="dxvn-home-section__eyebrow"><?php echo esc_html( dxvn_get_header_setting( 'home_whyus_eyebrow' ) ); ?></span>
				<h2 id="dxvn-whyus-title" class="dxvn-home-section__title"><?php echo esc_html( dxvn_get_header_setting( 'home_whyus_title' ) ); ?></h2>
				<p class="dxvn-home-section__desc"><?php echo esc_html( dxvn_get_header_setting( 'home_whyus_desc' ) ); ?></p>
			</div>
			<div class="dxvn-home-grid dxvn-home-grid--3 dxvn-commit-grid">
				<?php foreach ( $commitments as $index => $item ) : ?>
					<article class="dxvn-commit-card">
						<div class="dxvn-commit-card__icon"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></div>
						<h3 class="dxvn-commit-card__title"><?php echo esc_html( $item['title'] ); ?></h3>
						<p class="dxvn-commit-card__text"><?php echo esc_html( $item['text'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="dxvn-home-section dxvn-testimonials" aria-labelledby="dxvn-testimonials-title">
			<div class="dxvn-home-section__head">
				<span class="dxvn-home-section__eyebrow"><?php echo esc_html( dxvn_get_header_setting( 'home_testimonials_eyebrow' ) ); ?></span>
				<h2 id="dxvn-testimonials-title" class="dxvn-home-section__title"><?php echo esc_html( dxvn_get_header_setting( 'home_testimonials_title' ) ); ?></h2>
			</div>
			<div class="dxvn-home-grid dxvn-home-grid--3">
				<?php foreach ( $testimonials as $item ) : ?>
					<article class="dxvn-quote-card">
						<p class="dxvn-quote-card__text"><?php echo esc_html( $item['text'] ); ?></p>
						<div class="dxvn-quote-card__author"><?php echo esc_html( $item['author'] ); ?></div>
						<div class="dxvn-quote-card__status"><?php echo esc_html( $item['status'] ); ?></div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="dxvn-home-section dxvn-faq" aria-labelledby="dxvn-faq-title">
			<div class="dxvn-home-section__head">
				<span class="dxvn-home-section__eyebrow"><?php echo esc_html( dxvn_get_header_setting( 'home_faq_eyebrow' ) ); ?></span>
				<h2 id="dxvn-faq-title" class="dxvn-home-section__title"><?php echo esc_html( dxvn_get_header_setting( 'home_faq_title' ) ); ?></h2>
			</div>
			<div class="dxvn-faq__list">
				<?php foreach ( $faqs as $item ) : ?>
					<details class="dxvn-faq__item">
						<summary><?php echo esc_html( $item['q'] ); ?></summary>
						<p><?php echo esc_html( $item['a'] ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="dxvn-home-section dxvn-final-cta" aria-labelledby="dxvn-final-cta-title">
			<div class="dxvn-final-cta__box">
				<span class="dxvn-home-section__eyebrow"><?php echo esc_html( dxvn_get_header_setting( 'home_final_cta_eyebrow' ) ); ?></span>
				<h2 id="dxvn-final-cta-title" class="dxvn-home-section__title"><?php echo esc_html( dxvn_get_header_setting( 'home_final_cta_title' ) ); ?></h2>
				<p class="dxvn-home-section__desc"><?php echo esc_html( dxvn_get_header_setting( 'home_final_cta_desc' ) ); ?></p>
				<div class="dxvn-final-cta__actions">
					<a class="dxvn-final-cta__btn dxvn-final-cta__btn--primary" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9\+]/', '', (string) dxvn_get_header_setting( 'home_final_cta_primary_phone', '19008164' ) ) ); ?>"><?php echo esc_html( dxvn_get_header_setting( 'home_final_cta_primary_text' ) ); ?></a>
					<a class="dxvn-final-cta__btn dxvn-final-cta__btn--secondary" href="<?php echo esc_url( dxvn_get_header_setting( 'home_final_cta_secondary_url', home_url( '/#mttf-search-input' ) ) ); ?>"><?php echo esc_html( dxvn_get_header_setting( 'home_final_cta_secondary_text' ) ); ?></a>
				</div>
			</div>
		</section>

		<?php
		if ( generate_has_default_loop() ) {
			while ( have_posts() ) :
				the_post();

				$content = trim( (string) get_the_content() );
				if ( '' !== $content ) :
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<div class="inside-article">
							<div class="entry-content" itemprop="text">
								<?php the_content(); ?>
							</div>
						</div>
					</article>
					<?php
				endif;
			endwhile;
		}

		/**
		 * generate_after_main_content hook.
		 *
		 * @since 0.1
		 */
		do_action( 'generate_after_main_content' );
		?>
	</main>
</div>

<?php
/**
 * generate_after_primary_content_area hook.
 *
 * @since 2.0
 */
do_action( 'generate_after_primary_content_area' );

generate_construct_sidebars();

get_footer();
