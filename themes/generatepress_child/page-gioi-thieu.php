<?php
/**
 * Template Name: DXVN Giới Thiệu
 * Description: Trang giới thiệu thương hiệu Datxevietnam.
 *
 * @package GeneratePress_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$about = function_exists( 'dxvn_get_header_setting' ) ? dxvn_get_header_settings() : array();
$about_hero_image = $about['about_hero_image_url'] ?? ( get_stylesheet_directory_uri() . '/assets/images/about-hero-limousine.jpg' );
?>

<div <?php generate_do_attr( 'content' ); ?>>
	<main <?php generate_do_attr( 'main' ); ?>>
		<?php do_action( 'generate_before_main_content' ); ?>

		<div class="dxvn-about">
			<section class="dxvn-about-hero" aria-labelledby="dxvn-about-hero-title">
				<div class="dxvn-about-hero__media">
					<img src="<?php echo esc_url( $about_hero_image ); ?>" alt="<?php echo esc_attr( $about['about_hero_title'] ?? 'Dàn xe Limousine cao cấp Datxevietnam' ); ?>" loading="lazy" />
				</div>
				<div class="dxvn-about-hero__content">
					<span class="dxvn-about__eyebrow"><?php echo esc_html( $about['about_hero_eyebrow'] ?? 'Datxevietnam' ); ?></span>
					<h1 id="dxvn-about-hero-title"><?php echo esc_html( $about['about_hero_title'] ?? '' ); ?></h1>
					<p>
						<?php echo esc_html( $about['about_hero_desc'] ?? '' ); ?>
					</p>
				</div>
			</section>

			<section class="dxvn-about-section" aria-labelledby="dxvn-about-vision-title">
				<div class="dxvn-about-section__head">
					<span class="dxvn-about__eyebrow"><?php echo esc_html( $about['about_vision_eyebrow'] ?? '' ); ?></span>
					<h2 id="dxvn-about-vision-title"><?php echo esc_html( $about['about_vision_title'] ?? '' ); ?></h2>
				</div>
				<div class="dxvn-about-grid dxvn-about-grid--2">
					<article class="dxvn-about-card dxvn-about-card--icon">
						<div class="dxvn-about-card__icon" aria-hidden="true">01</div>
						<h3><?php echo esc_html( $about['about_vision_col_title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $about['about_vision_col_text'] ?? '' ); ?></p>
					</article>
					<article class="dxvn-about-card dxvn-about-card--icon">
						<div class="dxvn-about-card__icon" aria-hidden="true">02</div>
						<h3><?php echo esc_html( $about['about_mission_col_title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $about['about_mission_col_text_1'] ?? '' ); ?></p>
						<p><?php echo esc_html( $about['about_mission_col_text_2'] ?? '' ); ?></p>
					</article>
				</div>
			</section>

			<section class="dxvn-about-section" aria-labelledby="dxvn-about-scale-title">
				<div class="dxvn-about-section__head">
					<span class="dxvn-about__eyebrow"><?php echo esc_html( $about['about_scale_eyebrow'] ?? '' ); ?></span>
					<h2 id="dxvn-about-scale-title"><?php echo esc_html( $about['about_scale_title'] ?? '' ); ?></h2>
				</div>
				<div class="dxvn-about-grid dxvn-about-grid--4 dxvn-about-grid--stats">
					<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
					<article class="dxvn-about-stat">
						<div class="dxvn-about-stat__value"><span class="dxvn-counter" data-target="<?php echo esc_attr( (string) (int) ( $about[ 'about_scale_' . $i . '_number' ] ?? 0 ) ); ?>">0</span><?php echo esc_html( $about[ 'about_scale_' . $i . '_suffix' ] ?? '' ); ?></div>
						<p><?php echo esc_html( $about[ 'about_scale_' . $i . '_desc' ] ?? '' ); ?></p>
					</article>
					<?php endfor; ?>
				</div>
				<p class="dxvn-about-note"><?php echo esc_html( $about['about_scale_note'] ?? '' ); ?></p>
			</section>

			<section class="dxvn-about-section dxvn-about-section--alt" aria-labelledby="dxvn-about-core-title">
				<div class="dxvn-about-section__head">
					<span class="dxvn-about__eyebrow"><?php echo esc_html( $about['about_core_eyebrow'] ?? '' ); ?></span>
					<h2 id="dxvn-about-core-title"><?php echo esc_html( $about['about_core_title'] ?? '' ); ?></h2>
				</div>
				<div class="dxvn-about-grid dxvn-about-grid--4 dxvn-about-grid--core">
					<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
						<article class="dxvn-about-card">
							<h3>
								<span class="dxvn-about-core-icon" aria-hidden="true">
									<?php if ( 1 === $i ) : ?>
										<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 3l7 3v6c0 5-3.5 8.5-7 9-3.5-.5-7-4-7-9V6l7-3zm-3.2 9.1l2.1 2.1 4.3-4.3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
									<?php elseif ( 2 === $i ) : ?>
										<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M8 7H5a2 2 0 00-2 2v8a2 2 0 002 2h3m8-12h3a2 2 0 012 2v8a2 2 0 01-2 2h-3M8 12h8m-8-3h8m-8 6h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
									<?php elseif ( 3 === $i ) : ?>
										<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M4 7h16M4 12h16M4 17h10M16 16l2 2 3-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
									<?php else : ?>
										<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 3l7 3v6c0 5-3.5 8.5-7 9-3.5-.5-7-4-7-9V6l7-3zM9.5 12.5l1.7 1.7 3.3-3.3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
									<?php endif; ?>
								</span>
								<?php echo esc_html( $about[ 'about_core_' . $i . '_title' ] ?? '' ); ?>
							</h3>
							<p><?php echo esc_html( $about[ 'about_core_' . $i . '_text' ] ?? '' ); ?></p>
						</article>
					<?php endfor; ?>
				</div>
			</section>

			<section class="dxvn-about-section dxvn-about-section--alt-soft" aria-labelledby="dxvn-about-verify-title">
				<div class="dxvn-about-section__head">
					<span class="dxvn-about__eyebrow"><?php echo esc_html( $about['about_verify_eyebrow'] ?? '' ); ?></span>
					<h2 id="dxvn-about-verify-title"><?php echo esc_html( $about['about_verify_title'] ?? '' ); ?></h2>
				</div>
				<ol class="dxvn-about-timeline">
					<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
						<li>
							<h3><?php echo esc_html( $about[ 'about_verify_' . $i . '_title' ] ?? '' ); ?></h3>
							<p><?php echo esc_html( $about[ 'about_verify_' . $i . '_text' ] ?? '' ); ?></p>
						</li>
					<?php endfor; ?>
				</ol>
			</section>

			<section class="dxvn-about-section" aria-labelledby="dxvn-about-gallery-title">
				<div class="dxvn-about-section__head">
					<span class="dxvn-about__eyebrow"><?php echo esc_html( $about['about_gallery_eyebrow'] ?? '' ); ?></span>
					<h2 id="dxvn-about-gallery-title"><?php echo esc_html( $about['about_gallery_title'] ?? '' ); ?></h2>
					<p><?php echo esc_html( $about['about_gallery_desc'] ?? '' ); ?></p>
				</div>
				<div class="dxvn-about-gallery">
					<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
						<figure>
							<img src="<?php echo esc_url( $about[ 'about_gallery_' . $i . '_image_url' ] ?? '' ); ?>" alt="<?php echo esc_attr( $about[ 'about_gallery_' . $i . '_alt' ] ?? '' ); ?>" loading="lazy" />
							<figcaption><?php echo esc_html( $about[ 'about_gallery_' . $i . '_caption' ] ?? '' ); ?></figcaption>
						</figure>
					<?php endfor; ?>
				</div>
			</section>

			<section class="dxvn-about-section dxvn-about-cta" aria-labelledby="dxvn-about-cta-title">
				<div class="dxvn-about-section__head">
					<span class="dxvn-about__eyebrow"><?php echo esc_html( $about['about_cta_eyebrow'] ?? '' ); ?></span>
					<h2 id="dxvn-about-cta-title"><?php echo esc_html( $about['about_cta_title'] ?? '' ); ?></h2>
					<p><?php echo esc_html( $about['about_cta_desc'] ?? '' ); ?></p>
				</div>
				<div class="dxvn-about-cta__actions">
					<a class="dxvn-final-cta__btn dxvn-final-cta__btn--primary" href="<?php echo esc_url( $about['about_cta_primary_url'] ?? home_url( '/?focus_search=1#mttf-search-input' ) ); ?>"><?php echo esc_html( $about['about_cta_primary_text'] ?? '' ); ?></a>
					<a class="dxvn-final-cta__btn dxvn-final-cta__btn--secondary" href="<?php echo esc_url( $about['about_cta_secondary_url'] ?? home_url( '/lien-he' ) ); ?>"><?php echo esc_html( $about['about_cta_secondary_text'] ?? '' ); ?></a>
				</div>
			</section>
		</div>

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
		?>

		<script>
		document.addEventListener('DOMContentLoaded', function () {
			var counters = document.querySelectorAll('.dxvn-counter');
			var hasAnimated = false;

			function animateCounters() {
				if (hasAnimated) {
					return;
				}
				hasAnimated = true;

				counters.forEach(function (counter) {
					var target = parseInt(counter.getAttribute('data-target') || '0', 10);
					var value = 0;
					var step = Math.max(1, Math.ceil(target / 40));
					var timer = window.setInterval(function () {
						value += step;
						if (value >= target) {
							counter.textContent = String(target);
							window.clearInterval(timer);
							return;
						}
						counter.textContent = String(value);
					}, 24);
				});
			}

			if ('IntersectionObserver' in window) {
				var observer = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (entry.isIntersecting) {
							animateCounters();
							observer.disconnect();
						}
					});
				}, { threshold: 0.25 });

				var statsSection = document.querySelector('#dxvn-about-scale-title');
				if (statsSection) {
					observer.observe(statsSection);
				}
			} else {
				animateCounters();
			}
		});
		</script>

		<?php do_action( 'generate_after_main_content' ); ?>
	</main>
</div>

<?php
do_action( 'generate_after_primary_content_area' );
generate_construct_sidebars();
get_footer();
