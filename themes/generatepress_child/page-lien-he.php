<?php
/**
 * Template Name: DXVN Liên Hệ
 * Description: Trang liên hệ chuẩn chuyển đổi cho Datxevietnam.
 *
 * @package GeneratePress_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$settings = function_exists( 'dxvn_get_header_settings' ) ? dxvn_get_header_settings() : array();
$offices  = array();

for ( $i = 1; $i <= 6; $i++ ) {
	$name = trim( (string) ( $settings[ 'contact_office_' . $i . '_name' ] ?? '' ) );
	if ( '' === $name ) {
		continue;
	}

	$offices[] = array(
		'name'      => $name,
		'address'   => (string) ( $settings[ 'contact_office_' . $i . '_address' ] ?? '' ),
		'image_url' => (string) ( $settings[ 'contact_office_' . $i . '_image_url' ] ?? '' ),
		'phone'     => (string) ( $settings[ 'contact_office_' . $i . '_phone' ] ?? '' ),
		'zalo_url'  => (string) ( $settings[ 'contact_office_' . $i . '_zalo_url' ] ?? '' ),
		'map_url'   => (string) ( $settings[ 'contact_office_' . $i . '_map_url' ] ?? '' ),
		'embed_url' => (string) ( $settings[ 'contact_office_' . $i . '_map_embed_url' ] ?? '' ),
	);
}
?>

<div <?php generate_do_attr( 'content' ); ?>>
	<main <?php generate_do_attr( 'main' ); ?>>
		<?php do_action( 'generate_before_main_content' ); ?>

		<div class="dxvn-contact">
			<section class="dxvn-contact-hero" aria-labelledby="dxvn-contact-hero-title">
				<div class="dxvn-contact-hero__media">
					<img src="<?php echo esc_url( $settings['contact_hero_image_url'] ?? '' ); ?>" alt="<?php echo esc_attr( $settings['contact_hero_title'] ?? '' ); ?>" loading="lazy" />
				</div>
				<div class="dxvn-contact-hero__content">
					<span class="dxvn-contact__eyebrow"><?php echo esc_html( $settings['contact_hero_eyebrow'] ?? '' ); ?></span>
					<h1 id="dxvn-contact-hero-title"><?php echo esc_html( $settings['contact_hero_title'] ?? '' ); ?></h1>
					<p><?php echo esc_html( $settings['contact_hero_desc'] ?? '' ); ?></p>
					<div class="dxvn-contact-hero__proof"><?php echo esc_html( $settings['contact_hero_proof'] ?? '' ); ?></div>
				</div>
			</section>

			<section class="dxvn-contact-section" aria-labelledby="dxvn-contact-office-title">
				<div class="dxvn-contact-section__head">
					<span class="dxvn-contact__eyebrow"><?php echo esc_html( $settings['contact_office_eyebrow'] ?? '' ); ?></span>
					<h2 id="dxvn-contact-office-title"><?php echo esc_html( $settings['contact_office_title'] ?? '' ); ?></h2>
				</div>

				<div class="dxvn-contact-office-grid">
					<?php foreach ( $offices as $index => $office ) : ?>
						<article class="dxvn-contact-office-card" data-office-index="<?php echo esc_attr( (string) $index ); ?>" data-office-embed="<?php echo esc_attr( $office['embed_url'] ); ?>">
							<div class="dxvn-contact-office-card__media">
								<img src="<?php echo esc_url( $office['image_url'] ); ?>" alt="<?php echo esc_attr( $office['name'] ); ?>" loading="lazy" />
							</div>
							<div class="dxvn-contact-office-card__body">
								<h3><?php echo esc_html( $office['name'] ); ?></h3>
								<p><?php echo esc_html( $office['address'] ); ?></p>
								<div class="dxvn-contact-office-card__actions">
									<a class="dxvn-contact-btn dxvn-contact-btn--primary" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9\+]/', '', $office['phone'] ) ); ?>">Gọi điện</a>
									<a class="dxvn-contact-btn dxvn-contact-btn--ghost" href="<?php echo esc_url( $office['zalo_url'] ); ?>" target="_blank" rel="noopener">Chat Zalo</a>
									<a class="dxvn-contact-btn dxvn-contact-btn--map" href="<?php echo esc_url( $office['map_url'] ); ?>" target="_blank" rel="noopener">Chỉ đường</a>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="dxvn-contact-section dxvn-contact-section--alt" aria-labelledby="dxvn-contact-channel-title">
				<div class="dxvn-contact-section__head">
					<span class="dxvn-contact__eyebrow"><?php echo esc_html( $settings['contact_channel_eyebrow'] ?? '' ); ?></span>
					<h2 id="dxvn-contact-channel-title"><?php echo esc_html( $settings['contact_channel_title'] ?? '' ); ?></h2>
				</div>

				<div class="dxvn-contact-channel-grid">
					<div class="dxvn-contact-map-card">
						<h3><?php echo esc_html( $settings['contact_map_label'] ?? '' ); ?></h3>
						<iframe
							id="dxvn-contact-map-iframe"
							src="<?php echo esc_url( $settings['contact_map_embed_url'] ?? '' ); ?>"
							loading="lazy"
							referrerpolicy="no-referrer-when-downgrade"
							allowfullscreen
							title="Google Maps"
						></iframe>
					</div>

					<div class="dxvn-contact-form-card">
						<h3><?php echo esc_html( $settings['contact_form_title'] ?? '' ); ?></h3>
						<form class="dxvn-contact-form" action="#" method="post">
							<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'dxvn_contact_submit' ) ); ?>" />
							<label>
								<?php echo esc_html( $settings['contact_form_name_label'] ?? '' ); ?>
								<input type="text" name="name" required />
							</label>
							<label>
								<?php echo esc_html( $settings['contact_form_phone_label'] ?? '' ); ?>
								<input type="tel" name="phone" required />
							</label>
							<label>
								<?php echo esc_html( $settings['contact_form_need_label'] ?? '' ); ?>
								<select name="need">
									<option value="tu-van-dat-xe"><?php echo esc_html( $settings['contact_form_need_option_1'] ?? '' ); ?></option>
									<option value="partnership"><?php echo esc_html( $settings['contact_form_need_option_2'] ?? '' ); ?></option>
									<option value="quality"><?php echo esc_html( $settings['contact_form_need_option_3'] ?? '' ); ?></option>
								</select>
							</label>
							<label>
								<?php echo esc_html( $settings['contact_form_note_label'] ?? '' ); ?>
								<textarea name="note" rows="4"></textarea>
							</label>
							<button type="submit" class="dxvn-contact-btn dxvn-contact-btn--primary"><?php echo esc_html( $settings['contact_form_submit_text'] ?? '' ); ?></button>
							<p class="dxvn-contact-form__status" aria-live="polite"></p>
						</form>

						<div class="dxvn-contact-alt-channels">
							<h4><?php echo esc_html( $settings['contact_alt_channels_title'] ?? '' ); ?></h4>
							<div class="dxvn-contact-alt-channels__list">
								<a href="<?php echo esc_url( $settings['contact_zalo_url'] ?? '#' ); ?>" target="_blank" rel="noopener">Zalo</a>
								<a href="<?php echo esc_url( $settings['contact_whatsapp_url'] ?? '#' ); ?>" target="_blank" rel="noopener">WhatsApp</a>
								<a href="<?php echo esc_url( $settings['contact_messenger_url'] ?? '#' ); ?>" target="_blank" rel="noopener">Messenger</a>
								<a href="<?php echo esc_url( $settings['contact_viber_url'] ?? '#' ); ?>" target="_blank" rel="noopener">Viber</a>
							</div>
						</div>
					</div>
				</div>
			</section>

			<section class="dxvn-contact-section dxvn-contact-special" aria-labelledby="dxvn-contact-special-title">
				<div class="dxvn-contact-section__head">
					<span class="dxvn-contact__eyebrow"><?php echo esc_html( $settings['contact_special_title'] ?? '' ); ?></span>
					<h2 id="dxvn-contact-special-title"><?php echo esc_html( $settings['contact_special_title'] ?? '' ); ?></h2>
				</div>

				<div class="dxvn-contact-special-grid">
					<article class="dxvn-contact-special-card">
						<h3><?php echo esc_html( $settings['contact_special_international_title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $settings['contact_special_international_desc'] ?? '' ); ?></p>
					</article>
					<article class="dxvn-contact-special-card">
						<h3><?php echo esc_html( $settings['contact_special_partner_title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $settings['contact_special_partner_desc'] ?? '' ); ?></p>
						<a class="dxvn-contact-btn dxvn-contact-btn--secondary" href="<?php echo esc_url( $settings['contact_special_partner_cta_url'] ?? '#' ); ?>"><?php echo esc_html( $settings['contact_special_partner_cta_text'] ?? '' ); ?></a>
					</article>
				</div>
			</section>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function () {
			var cards = document.querySelectorAll('.dxvn-contact-office-card');
			var mapFrame = document.getElementById('dxvn-contact-map-iframe');
			if (cards.length && mapFrame) {
				cards.forEach(function (card) {
					card.addEventListener('click', function () {
						var embed = card.getAttribute('data-office-embed') || '';
						if (!embed) return;
						mapFrame.setAttribute('src', embed);
						cards.forEach(function (item) { item.classList.remove('is-active'); });
						card.classList.add('is-active');
					});
				});
			}

			var form = document.querySelector('.dxvn-contact-form');
			if (!form) return;
			var statusEl = form.querySelector('.dxvn-contact-form__status');
			var submitBtn = form.querySelector('button[type="submit"]');

			form.addEventListener('submit', function (event) {
				event.preventDefault();

				if (submitBtn) submitBtn.disabled = true;
				if (statusEl) {
					statusEl.textContent = 'Đang gửi yêu cầu...';
					statusEl.classList.remove('is-error', 'is-success');
				}

				var payload = new FormData(form);
				payload.append('action', 'dxvn_contact_submit');

				fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					credentials: 'same-origin',
					body: payload
				})
				.then(function (response) { return response.json(); })
				.then(function (data) {
					if (!data || !data.success) {
						var errorMsg = (data && data.data && data.data.message) ? data.data.message : 'Gửi thất bại, vui lòng thử lại.';
						throw new Error(errorMsg);
					}
					form.reset();
					if (statusEl) {
						statusEl.textContent = data.data && data.data.message ? data.data.message : 'Đã gửi thành công.';
						statusEl.classList.add('is-success');
					}
				})
				.catch(function (error) {
					if (statusEl) {
						statusEl.textContent = error.message || 'Có lỗi xảy ra.';
						statusEl.classList.add('is-error');
					}
				})
				.finally(function () {
					if (submitBtn) submitBtn.disabled = false;
				});
			});
		});
		</script>

		<?php do_action( 'generate_after_main_content' ); ?>
	</main>
</div>

<?php
do_action( 'generate_after_primary_content_area' );
generate_construct_sidebars();
get_footer();
