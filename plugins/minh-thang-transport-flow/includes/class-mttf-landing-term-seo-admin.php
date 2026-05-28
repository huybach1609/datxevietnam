<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO fields on mttf_tuyen and mttf_nha_xe term screens.
 */
class MTTF_Landing_Term_SEO_Admin {

	/** @var array<string,string> */
	private static $fields = array(
		'title'            => 'SEO Title',
		'description'      => 'Meta Description',
		'h1'               => 'H1 tùy chỉnh',
		'hero_description' => 'Mô tả hero',
		'og_title'         => 'OG Title',
		'og_description'   => 'OG Description',
		'og_image'         => 'OG Image URL',
	);

	public static function init() {
		foreach ( array( MTTF_Landing_Taxonomies::TAX_TUYEN, MTTF_Landing_Taxonomies::TAX_NHA_XE ) as $taxonomy ) {
			add_action( "{$taxonomy}_add_form_fields", array( __CLASS__, 'render_add_fields' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( __CLASS__, 'render_edit_fields' ) );
			add_action( "created_{$taxonomy}", array( __CLASS__, 'save_term' ) );
			add_action( "edited_{$taxonomy}", array( __CLASS__, 'save_term' ) );
		}
	}

	public static function render_add_fields() {
		wp_nonce_field( 'mttf_save_term_seo', 'mttf_term_seo_nonce' );
		?>
		<div class="form-field mttf-term-seo-wrap">
			<h2><?php esc_html_e( 'SEO Landing', 'minh-thang-transport-flow' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Để trống để dùng mẫu SEO tự động theo tên tuyến/nhà xe.', 'minh-thang-transport-flow' ); ?>
			</p>
			<?php self::render_fields_html( 0 ); ?>
		</div>
		<?php
	}

	/**
	 * @param WP_Term $term Term.
	 */
	public static function render_edit_fields( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		wp_nonce_field( 'mttf_save_term_seo', 'mttf_term_seo_nonce' );
		?>
		<tr class="form-field mttf-term-seo-wrap">
			<th scope="row" colspan="2">
				<h2><?php esc_html_e( 'SEO Landing', 'minh-thang-transport-flow' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Ưu tiên các trường bên dưới. Nếu để trống, trang landing sẽ tự sinh title/meta/H1 theo template.', 'minh-thang-transport-flow' ); ?>
				</p>
			</th>
		</tr>
		<?php self::render_fields_html( (int) $term->term_id, true ); ?>
		<?php
	}

	/**
	 * @param int  $term_id Term ID.
	 * @param bool $table   Table row layout.
	 */
	private static function render_fields_html( $term_id, $table = false ) {
		foreach ( self::$fields as $key => $label ) {
			$meta_key = MTTF_Landing_SEO::META_PREFIX . $key;
			$value    = $term_id > 0 ? (string) get_term_meta( $term_id, $meta_key, true ) : '';
			$field_id = 'mttf_seo_' . $key;
			$is_area  = in_array( $key, array( 'description', 'hero_description', 'og_description' ), true );

			if ( $table ) {
				?>
				<tr class="form-field">
					<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
					<td>
						<?php self::render_input( $field_id, $meta_key, $value, $is_area ); ?>
					</td>
				</tr>
				<?php
				continue;
			}

			?>
			<div class="form-field">
				<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
				<?php self::render_input( $field_id, $meta_key, $value, $is_area ); ?>
			</div>
			<?php
		}
	}

	/**
	 * @param string $field_id Field id.
	 * @param string $meta_key Meta key.
	 * @param string $value    Value.
	 * @param bool   $is_area  Textarea.
	 */
	private static function render_input( $field_id, $meta_key, $value, $is_area ) {
		if ( $is_area ) {
			printf(
				'<textarea name="%1$s" id="%2$s" class="large-text" rows="3">%3$s</textarea>',
				esc_attr( $meta_key ),
				esc_attr( $field_id ),
				esc_textarea( $value )
			);
			return;
		}

		printf(
			'<input type="text" name="%1$s" id="%2$s" value="%3$s" class="regular-text" />',
			esc_attr( $meta_key ),
			esc_attr( $field_id ),
			esc_attr( $value )
		);
	}

	/**
	 * @param int $term_id Term ID.
	 */
	public static function save_term( $term_id ) {
		if ( ! isset( $_POST['mttf_term_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mttf_term_seo_nonce'] ) ), 'mttf_save_term_seo' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		foreach ( self::$fields as $key => $label ) {
			unset( $label );
			$meta_key = MTTF_Landing_SEO::META_PREFIX . $key;
			$raw      = isset( $_POST[ $meta_key ] ) ? wp_unslash( $_POST[ $meta_key ] ) : '';

			if ( 'og_image' === $key ) {
				$value = esc_url_raw( (string) $raw );
			} elseif ( in_array( $key, array( 'description', 'hero_description', 'og_description' ), true ) ) {
				$value = sanitize_textarea_field( (string) $raw );
			} else {
				$value = sanitize_text_field( (string) $raw );
			}

			if ( '' === $value ) {
				delete_term_meta( (int) $term_id, $meta_key );
			} else {
				update_term_meta( (int) $term_id, $meta_key, $value );
			}
		}
	}
}
