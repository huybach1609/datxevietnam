<?php
/**
 * Template Name: DXVN Hợp Tác
 * Description: Trang dành cho nhà xe limousine muốn hợp tác được giới thiệu trên Datxevietnam (có xét duyệt).
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
		<?php do_action( 'generate_before_main_content' ); ?>

		<div class="dxvn-hoptac">
			<section class="dxvn-hoptac-hero" aria-labelledby="dxvn-hoptac-hero-title">
				<div class="dxvn-hoptac-hero__content">
					<span class="dxvn-hoptac__eyebrow">Đối tác nhà xe</span>
					<h1 id="dxvn-hoptac-hero-title">Hợp tác với Datxevietnam</h1>
					<p>
						Nếu bạn là chủ/quản lý nhà xe limousine đang vận hành tuyến ổn định, hãy gửi yêu cầu hợp tác để được xem xét giới thiệu trên website. Sau khi duyệt, đội ngũ sẽ đồng hành thu thông tin và dựng nội dung — không phải tự đăng công khai ngay.
					</p>
					<p class="dxvn-hoptac-hero__note" role="note">
						Đăng hiển thị chỉ áp dụng cho đối tác đạt tiêu chí và được phê duyệt.
					</p>
					<div class="dxvn-hoptac-hero__actions">
						<a class="dxvn-hoptac-btn dxvn-hoptac-btn--primary" href="#hop-tac-form">Gửi yêu cầu hợp tác</a>
						<a class="dxvn-hoptac-btn dxvn-hoptac-btn--ghost" href="#hop-tac-faq">Câu hỏi thường gặp</a>
					</div>
				</div>
				<div class="dxvn-hoptac-hero__aside" aria-hidden="true">
					<svg class="dxvn-hoptac-hero__aside-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 96" fill="none" aria-hidden="true">
						<rect x="4" y="28" width="112" height="44" rx="8" stroke="rgba(255,255,255,0.5)" stroke-width="3"/>
						<circle cx="28" cy="72" r="10" stroke="rgba(217,188,74,0.95)" stroke-width="3"/>
						<circle cx="92" cy="72" r="10" stroke="rgba(217,188,74,0.95)" stroke-width="3"/>
						<path d="M20 52h80M36 38h48" stroke="rgba(255,255,255,0.55)" stroke-width="2.5" stroke-linecap="round"/>
					</svg>
					<p>Kết nối tuyến đúng hành khách — có kiểm duyệt nội dung &amp; pháp lý cơ bản.</p>
				</div>
			</section>

			<section class="dxvn-hoptac-section" aria-labelledby="dxvn-hoptac-benefits-title">
				<div class="dxvn-hoptac-section__head">
					<span class="dxvn-hoptac__eyebrow">Lợi ích</span>
					<h2 id="dxvn-hoptac-benefits-title">Vì sao đồng hành cùng chúng tôi?</h2>
					<p>Kênh Datxevietnam tập trung người đang có nhu cầu đặt limousine — không phân tán như các hình thức quảng cáo chung chung.</p>
				</div>
				<div class="dxvn-hoptac-benefits">
					<article>
						<div class="dxvn-hoptac-icon">01</div>
						<h3>Tiếp cận đúng tệp khách</h3>
						<p>Hiển thị trong luồng tìm và so sánh tuyến, giúp khách chủ động liên hệ.</p>
					</article>
					<article>
						<div class="dxvn-hoptac-icon">02</div>
						<h3>Nội dung chỉn chu</h3>
						<p>Đội nội dung hỗ trợ tóm tắt lịch trình, điểm đón và thông tin cần thiết để khách tin tưởng.</p>
					</article>
					<article>
						<div class="dxvn-hoptac-icon">03</div>
						<h3>Minh bạch tiêu chí</h3>
						<p>Đối tác được nhận các bước rõ ràng: gửi hồ sơ → duyệt → bổ sung tài liệu → đăng tin.</p>
					</article>
					<article>
						<div class="dxvn-hoptac-icon">04</div>
						<h3>Bảo vệ thương hiệu</h3>
						<p>Không tự đăng hàng loạt không kiểm; chỉ các nhà xe đạt điều kiện mới xuất hiện sau duyệt.</p>
					</article>
				</div>
			</section>

			<section class="dxvn-hoptac-section" aria-labelledby="dxvn-hoptac-criteria-title">
				<div class="dxvn-hoptac-section__head">
					<span class="dxvn-hoptac__eyebrow">Tiêu chí</span>
					<h2 id="dxvn-hoptac-criteria-title">Chúng tôi ưu tiên nhà xe có thể chứng minh</h2>
					<p>Danh sách không mang tính hứa hẹn đăng tự động — đây là các tiêu chí thực tế đội ngũ dùng khi đánh giá.</p>
				</div>
				<div class="dxvn-hoptac-criteria">
					<ul>
						<li>Đang vận hành limousine (hoặc dòng xe tương đương cam kết) trên một hoặc nhiều tuyến cố định.</li>
						<li>Giấy tờ/pháp lý vận tải còn hiệu lực và thông tin liên lạc vận hành rõ ràng.</li>
						<li>Cam kết công bố giờ chạy, điểm đón và chính sách hủy/đổi trả trung thực với hành khách.</li>
					</ul>
					<ul>
						<li>Sẵn sàng phối hợp cập nhật khi thay đổi giá hoặc lịch trong giai đoạn hợp tác.</li>
						<li>Không quảng cáo sai lệch, không chèn nội dung gây hiểu nhầm hoặc cạnh tranh không lành mạnh.</li>
						<li>Đồng ý quy trình xét duyệt nội dung và điều chỉnh wording theo guideline của Datxevietnam.</li>
					</ul>
				</div>
			</section>

			<section class="dxvn-hoptac-section" aria-labelledby="dxvn-hoptac-steps-title">
				<div class="dxvn-hoptac-section__head">
					<span class="dxvn-hoptac__eyebrow">Quy trình</span>
					<h2 id="dxvn-hoptac-steps-title">Các bước làm việc</h2>
					<p>Thông thường từ lúc bạn gửi form đến khi nội dung lên trang có thể mất vài ngày, tùy đội ngũ và độ đầy đủ thông tin.</p>
				</div>
				<div class="dxvn-hoptac-steps-wrapper">
					<ol class="dxvn-hoptac-steps">
						<li>
							<h3>Gửi yêu cầu</h3>
							<p>Bạn điền biểu mẫu với thông tin nhà xe, tuyến và người liên hệ.</p>
						</li>
						<li>
							<h3>Sơ duyệt</h3>
							<p>Chúng tôi xem khớp tuyến, khu vực và các tiêu chí cơ bản.</p>
						</li>
						<li>
							<h3>Thu tài liệu</h3>
							<p>Logo, ảnh xe, khung giờ, điểm đón và bảng giá/biểu phí (theo checklist).</p>
						</li>
						<li>
							<h3>Dựng bài đăng</h3>
							<p>Draft hiển thị trên môi trường nội bộ để chỉnh sửa cùng bạn.</p>
						</li>
						<li>
							<h3>Xuất bản</h3>
							<p>Sau duyệt cuối, nội dung được đưa ra kênh và bạn được thông báo.</p>
						</li>
					</ol>
				</div>
			</section>

			<section class="dxvn-hoptac-form-section" id="hop-tac-form" aria-labelledby="dxvn-hoptac-form-title">
				<div class="dxvn-hoptac-section__head">
					<span class="dxvn-hoptac__eyebrow">Đăng ký</span>
					<h2 id="dxvn-hoptac-form-title">Gửi thông tin hợp tác</h2>
					<p>Chúng tôi chỉ nhận hồ sơ qua biểu mẫu dưới đây. Vui lòng điền chính xác để bộ phận liên lạc nhanh hơn.</p>
				</div>

				<form class="dxvn-hoptac-form" action="#" method="post" id="dxvn-hop-tac-form">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'dxvn_partnership_submit' ) ); ?>" />

					<label>
						Tên nhà xe / thương hiệu
						<input type="text" name="fleet_name" required autocomplete="organization" maxlength="200" placeholder="VD: nhà xe X — limousine tuyến ..." />
					</label>
					<label>
						Người liên hệ
						<input type="text" name="contact_name" required autocomplete="name" maxlength="120" placeholder="Họ và tên" />
					</label>
					<label>
						Số điện thoại
						<input type="tel" name="phone" required autocomplete="tel" maxlength="20" placeholder="Số có Zalo/Zalo OA nếu có" />
					</label>
					<label>
						Email (không bắt buộc nhưng nên có)
						<input type="email" name="email" autocomplete="email" maxlength="120" placeholder="email@nhaxe.vn" />
					</label>
					<label>
						Tuyến / lộ trình chính
						<input type="text" name="route_main" required maxlength="500" placeholder="VD: TP.HCM ⇄ Đà Lạt, ghép các điểm trung chuyển..." />
					</label>
					<label>
						Khu vực hoạt động
						<select name="region" required>
							<option value="">— Chọn —</option>
							<option value="north">Miền Bắc</option>
							<option value="central">Miền Trung</option>
							<option value="south">Miền Nam</option>
							<option value="interprovincial">Liên miền / nối miền</option>
							<option value="other">Khác (ghi rõ trong ghi chú)</option>
						</select>
					</label>
					<label class="dxvn-hoptac-form__full">
						Ghi chú thêm cho bộ phận đối tác
						<textarea name="note" maxlength="2500" placeholder="Số lượng xe, giờ chạy nổi bật, liên kết fanpage/Google Maps…"></textarea>
					</label>
					<label class="dxvn-hoptac-form__checkbox dxvn-hoptac-form__full">
						<input type="checkbox" name="confirm_accurate" value="1" required />
						<span>Tôi xác nhận thông tin gửi đi là đúng sự thật và hiểu nội dung chỉ được đăng sau khi Datxevietnam xét duyệt.</span>
					</label>

					<button type="submit" class="dxvn-hoptac-btn dxvn-hoptac-btn--primary">Gửi yêu cầu hợp tác</button>
					<p class="dxvn-hoptac-form__status" aria-live="polite"></p>
				</form>

				<p class="dxvn-hoptac-footer-note" style="margin-top: 26px;">
					Dữ liệu gửi từ form được dùng để liên hệ và đánh giá hợp tác — không chia sẻ cho bên thứ ba ngoài mục đích vận hành. Nếu cần hỗ trợ khách đặt xe thông thường, xin dùng <a href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">trang Liên hệ</a>.
				</p>
			</section>

			<section class="dxvn-hoptac-section dxvn-hoptac-section--faq" id="hop-tac-faq" aria-labelledby="dxvn-hoptac-faq-title">
				<div class="dxvn-hoptac-section__head">
					<span class="dxvn-hoptac__eyebrow">FAQ</span>
					<h2 id="dxvn-hoptac-faq-title">Câu hỏi thường gặp</h2>
					<p>Sáu câu hỏi thường gặp trước và sau khi gửi hồ sơ hợp tác.</p>
				</div>
				<div class="dxvn-hoptac-faq">
					<details class="dxvn-hoptac-faq__item">
						<summary>
							<span class="dxvn-hoptac-faq__q">Gửi form có nghĩa là được đăng ngay không?</span>
							<span class="dxvn-hoptac-faq__icon" aria-hidden="true"></span>
						</summary>
						<div class="dxvn-hoptac-faq__body">
							<p>Không. Gửi form chỉ là bước đầu để được xem xét. Chúng tôi liên lạc, thu đủ tài liệu và chỉ đăng sau khi thống nhất nội dung.</p>
						</div>
					</details>
					<details class="dxvn-hoptac-faq__item">
						<summary>
							<span class="dxvn-hoptac-faq__q">Tôi chỉ có 1–2 xe, có được không?</span>
							<span class="dxvn-hoptac-faq__icon" aria-hidden="true"></span>
						</summary>
						<div class="dxvn-hoptac-faq__body">
							<p>Có thể. Quan trọng là tính pháp lý và chất lượng vận hành được thể hiện rõ chứ không là con số tuyệt đối.</p>
						</div>
					</details>
					<details class="dxvn-hoptac-faq__item">
						<summary>
							<span class="dxvn-hoptac-faq__q">Sau duyệt, tôi có phải trả phí không?</span>
							<span class="dxvn-hoptac-faq__icon" aria-hidden="true"></span>
						</summary>
						<div class="dxvn-hoptac-faq__body">
							<p>Quyền lợi và điều khoản tài chính (nếu có trong từng giai đoạn) sẽ được trao đổi cụ thể khi có buổi làm việc chính thức — không ép buộc chỉ sau một email.</p>
						</div>
					</details>
					<details class="dxvn-hoptac-faq__item">
						<summary>
							<span class="dxvn-hoptac-faq__q">Bao lâu thì tôi nhận được phản hồi sau khi gửi form?</span>
							<span class="dxvn-hoptac-faq__icon" aria-hidden="true"></span>
						</summary>
						<div class="dxvn-hoptac-faq__body">
							<p>Tùy lượng hồ sơ và khung làm việc, nhưng trong điều kiện bình thường nhóm đối tác sẽ liên lạc lại trong vài ngày làm việc. Cuối tuần hoặc dịp lễ có thể chậm hơn — nếu cần gấp nên nhắn kèm số có Zalo khi được gọi điện thoại.</p>
						</div>
					</details>
					<details class="dxvn-hoptac-faq__item">
						<summary>
							<span class="dxvn-hoptac-faq__q">Khi được mời làm việc, tôi cần chuẩn bị những gì?</span>
							<span class="dxvn-hoptac-faq__icon" aria-hidden="true"></span>
						</summary>
						<div class="dxvn-hoptac-faq__body">
							<p>Thường gồm logo nhà xe, ảnh xe/dàn xe phục vụ tuyến, khung giờ và điểm đón trả chủ đạo, chỉnh sửa giá hoặc bảng quy định (nếu muốn công khai), cùng ảnh giấy tờ vận tải có liên quan để đội kiểm duyệt đối chiếu — danh mục chính xác sẽ được gửi khi có xác nhận hướng hợp tác.</p>
						</div>
					</details>
					<details class="dxvn-hoptac-faq__item">
						<summary>
							<span class="dxvn-hoptac-faq__q">Sau khi đã lên web, tôi có cập nhật giờ chạy hoặc giá được không?</span>
							<span class="dxvn-hoptac-faq__icon" aria-hidden="true"></span>
						</summary>
						<div class="dxvn-hoptac-faq__body">
							<p>Được. Hợp tác không có nghĩa là nội dung cố định một lần. Khi có thay đổi lịch, giá hoặc điểm đón, bạn thông báo kênh liên lạc đã thống nhất để nội dung trên site được chỉnh sửa và thống nhất lại wording trước khi đăng cập nhật.</p>
						</div>
					</details>
				</div>
			</section>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function () {
			var form = document.getElementById('dxvn-hop-tac-form');
			if (!form) return;
			var statusEl = form.querySelector('.dxvn-hoptac-form__status');
			var submitBtn = form.querySelector('button[type="submit"]');

			form.addEventListener('submit', function (event) {
				event.preventDefault();

				if (submitBtn) submitBtn.disabled = true;
				if (statusEl) {
					statusEl.textContent = 'Đang gửi...';
					statusEl.classList.remove('is-error', 'is-success');
				}

				var payload = new FormData(form);
				payload.append('action', 'dxvn_partnership_submit');

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
