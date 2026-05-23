<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MTTF_Route_Operators {
	const META_ROWS      = '_mttf_route_operator_rows';
	const META_OPERATOR_ID = '_mttf_route_operator_id';
	const ASSIGN_ROUTES_PAGE = 'mttf-assign-routes';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_assign_routes_page' ) );
		add_action( 'admin_post_mttf_save_operator_routes', array( __CLASS__, 'handle_assign_routes_submit' ) );
		add_filter( 'manage_tuyen_xe_posts_columns', array( __CLASS__, 'set_route_admin_columns' ), 20 );
		add_action( 'manage_tuyen_xe_posts_custom_column', array( __CLASS__, 'render_route_admin_column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'render_route_operator_filter' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_routes_by_operator' ) );
	}

	public static function add_assign_routes_page() {
		add_submenu_page(
			'edit.php?post_type=tuyen_xe',
			'Gán tuyến',
			'Gán tuyến',
			'edit_posts',
			self::ASSIGN_ROUTES_PAGE,
			array( __CLASS__, 'render_assign_routes_page' )
		);
	}

	public static function render_assign_routes_page() {
		$operators = MTTF_Operator::get_operator_choices( false );
		$operator_id = isset( $_GET['operator_id'] ) ? absint( $_GET['operator_id'] ) : 0;
		$selected_routes = $operator_id > 0 ? self::get_assigned_route_ids_for_operator( $operator_id ) : array();
		$routes = self::get_all_routes();
		?>
		<div class="wrap">
			<h1>Gán tuyến cho nhà xe</h1>

			<?php if ( isset( $_GET['updated'] ) && '1' === (string) $_GET['updated'] ) : ?>
				<div class="notice notice-success is-dismissible"><p>Đã lưu gán tuyến cho nhà xe.</p></div>
			<?php endif; ?>

			<?php if ( empty( $operators ) ) : ?>
				<p>Chưa có nhà xe nào. Hãy tạo nhà xe trước ở menu <strong>Nhà xe</strong>.</p>
			<?php else : ?>
				<form method="get" action="">
					<input type="hidden" name="post_type" value="tuyen_xe" />
					<input type="hidden" name="page" value="<?php echo esc_attr( self::ASSIGN_ROUTES_PAGE ); ?>" />
					<table class="form-table" role="presentation">
						<tbody>
						<tr>
							<th><label for="mttf-assign-operator">Nhà xe</label></th>
							<td>
								<select id="mttf-assign-operator" name="operator_id" class="regular-text">
									<option value="">Chọn nhà xe</option>
									<?php foreach ( $operators as $operator ) : ?>
										<option value="<?php echo esc_attr( (string) $operator['id'] ); ?>" <?php selected( $operator_id, (int) $operator['id'] ); ?>>
											<?php echo esc_html( $operator['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<?php submit_button( 'Xem tuyến', 'secondary', '', false ); ?>
							</td>
						</tr>
						</tbody>
					</table>
				</form>

				<?php if ( $operator_id > 0 ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="mttf_save_operator_routes" />
						<input type="hidden" name="operator_id" value="<?php echo esc_attr( (string) $operator_id ); ?>" />
						<?php wp_nonce_field( 'mttf_save_operator_routes_' . $operator_id, 'mttf_assign_routes_nonce' ); ?>

						<p>Chọn các tuyến mà nhà xe này đang khai thác.</p>

						<table class="widefat striped">
							<thead>
								<tr>
									<th style="width:56px;">Chọn</th>
									<th>Tuyến</th>
									<th>Khu vực</th>
									<th>Ưu tiên</th>
									<th>Kích hoạt</th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $routes ) ) : ?>
									<tr><td colspan="5">Chưa có tuyến nào.</td></tr>
								<?php else : ?>
									<?php foreach ( $routes as $route ) : ?>
										<?php
										$route_id = (int) $route->ID;
										$region   = (string) get_post_meta( $route_id, '_mttf_hub_region', true );
										$priority = (int) get_post_meta( $route_id, '_mttf_priority', true );
										$is_active = (int) get_post_meta( $route_id, '_mttf_is_active', true );
										?>
										<tr>
											<td><input type="checkbox" name="route_ids[]" value="<?php echo esc_attr( (string) $route_id ); ?>" <?php checked( in_array( $route_id, $selected_routes, true ) ); ?> /></td>
											<td><a href="<?php echo esc_url( get_edit_post_link( $route_id ) ); ?>"><?php echo esc_html( get_the_title( $route ) ); ?></a></td>
											<td><?php echo esc_html( MTTF_Operator::get_region_label( $region ) ); ?></td>
											<td><?php echo esc_html( (string) $priority ); ?></td>
											<td><?php echo 1 === $is_active ? 'Có' : 'Không'; ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>

						<?php submit_button( 'Lưu gán tuyến' ); ?>
					</form>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function handle_assign_routes_submit() {
		$operator_id = isset( $_POST['operator_id'] ) ? absint( $_POST['operator_id'] ) : 0;
		if ( $operator_id <= 0 || 'mttf_operator' !== get_post_type( $operator_id ) ) {
			wp_die( 'Nhà xe không hợp lệ.' );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Bạn không có quyền thực hiện thao tác này.' );
		}

		check_admin_referer( 'mttf_save_operator_routes_' . $operator_id, 'mttf_assign_routes_nonce' );

		$route_ids = isset( $_POST['route_ids'] ) && is_array( $_POST['route_ids'] )
			? array_map( 'absint', wp_unslash( $_POST['route_ids'] ) )
			: array();
		$route_ids = array_values( array_filter( array_unique( $route_ids ) ) );

		self::assign_routes_to_operator( $operator_id, $route_ids );

				wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'   => 'tuyen_xe',
					'page'        => self::ASSIGN_ROUTES_PAGE,
					'operator_id' => $operator_id,
					'updated'     => 1,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	public static function set_route_admin_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'mttf_hotline' === $key ) {
				$new_columns['mttf_operators'] = 'Nhà xe';
			}
		}

		if ( ! isset( $new_columns['mttf_operators'] ) ) {
			$new_columns['mttf_operators'] = 'Nhà xe';
		}

		return $new_columns;
	}

	public static function render_route_admin_column( $column, $post_id ) {
		if ( 'mttf_operators' !== $column ) {
			return;
		}

		$rows = self::get_route_operator_rows( $post_id, true );
		if ( empty( $rows ) ) {
			echo '-';
			return;
		}

		$names = array();
		foreach ( array_slice( $rows, 0, 3 ) as $row ) {
			$names[] = $row['operator_name'];
		}

		$out = implode( ', ', $names );
		if ( count( $rows ) > 3 ) {
			$out .= ' +' . ( count( $rows ) - 3 );
		}

		echo esc_html( $out );
	}

	public static function render_route_operator_filter() {
		global $typenow;

		if ( 'tuyen_xe' !== $typenow ) {
			return;
		}

		$operators = MTTF_Operator::get_operator_choices( false );
		if ( empty( $operators ) ) {
			return;
		}

		$selected = isset( $_GET['mttf_operator_filter'] ) ? absint( $_GET['mttf_operator_filter'] ) : 0;
		?>
		<select name="mttf_operator_filter">
			<option value="">Tất cả nhà xe</option>
			<?php foreach ( $operators as $operator ) : ?>
				<option value="<?php echo esc_attr( (string) $operator['id'] ); ?>" <?php selected( $selected, (int) $operator['id'] ); ?>>
					<?php echo esc_html( $operator['label'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public static function filter_routes_by_operator( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'tuyen_xe' !== $query->get( 'post_type' ) ) {
			return;
		}

		$operator_id = isset( $_GET['mttf_operator_filter'] ) ? absint( $_GET['mttf_operator_filter'] ) : 0;
		if ( $operator_id <= 0 ) {
			return;
		}

		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = array(
			'key'   => self::META_OPERATOR_ID,
			'value' => $operator_id,
		);
		$query->set( 'meta_query', $meta_query );
	}

	public static function get_route_operator_rows( $route_id, $active_only = true ) {
		$route_id = absint( $route_id );
		$stored   = get_post_meta( $route_id, self::META_ROWS, true );
		$stored   = is_array( $stored ) ? $stored : array();
		$rows     = array();

		foreach ( $stored as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$operator_id = isset( $row['operator_id'] ) ? absint( $row['operator_id'] ) : 0;
			if ( $operator_id <= 0 || 'mttf_operator' !== get_post_type( $operator_id ) ) {
				continue;
			}

			$normalized = self::normalize_row( $row, $operator_id );
			if ( $active_only && 1 !== (int) $normalized['operator_active'] ) {
				continue;
			}

			$rows[] = $normalized;
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				if ( (int) $a['priority'] === (int) $b['priority'] ) {
					return strcasecmp( $a['operator_name'], $b['operator_name'] );
				}

				return (int) $a['priority'] <=> (int) $b['priority'];
			}
		);

		return $rows;
	}

	public static function resolve_operator_row( $route_id, $operator_id ) {
		$operator_id = absint( $operator_id );
		foreach ( self::get_route_operator_rows( $route_id, false ) as $row ) {
			if ( (int) $row['operator_id'] === $operator_id ) {
				return $row;
			}
		}

		return null;
	}

	public static function get_operator_routes( $operator_id, $active_only = true ) {
		$operator_id = absint( $operator_id );
		if ( $operator_id <= 0 ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'      => 'tuyen_xe',
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => -1,
				'meta_key'       => self::META_OPERATOR_ID,
				'meta_value'     => $operator_id,
			)
		);

		if ( ! $active_only ) {
			return $posts;
		}

		$filtered = array();
		foreach ( $posts as $post ) {
			$row = self::resolve_operator_row( $post->ID, $operator_id );
			if ( $row && 1 === (int) $row['operator_active'] ) {
				$filtered[] = $post;
			}
		}

		$posts = $active_only ? $filtered : $posts;

		usort(
			$posts,
			static function ( $a, $b ) {
				$priority_a = (int) get_post_meta( $a->ID, '_mttf_priority', true );
				$priority_b = (int) get_post_meta( $b->ID, '_mttf_priority', true );

				if ( $priority_a === $priority_b ) {
					return strcasecmp( get_the_title( $a ), get_the_title( $b ) );
				}

				return $priority_a <=> $priority_b;
			}
		);

		return $posts;
	}

	private static function sanitize_rows( array $raw_rows ) {
		$rows = array();

		foreach ( $raw_rows as $raw_row ) {
			if ( ! is_array( $raw_row ) ) {
				continue;
			}

			$operator_id = absint( $raw_row['operator_id'] ?? 0 );
			if ( $operator_id <= 0 || 'mttf_operator' !== get_post_type( $operator_id ) ) {
				continue;
			}

			if ( isset( $rows[ $operator_id ] ) ) {
				continue;
			}

			$rows[ $operator_id ] = array(
				'operator_id'      => $operator_id,
			);
		}

		$rows = array_values( $rows );

		usort(
			$rows,
			static function ( $a, $b ) {
				return strcasecmp( get_the_title( (int) $a['operator_id'] ), get_the_title( (int) $b['operator_id'] ) );
			}
		);

		return $rows;
	}

	private static function sync_operator_index_meta( $route_id, array $rows ) {
		delete_post_meta( $route_id, self::META_OPERATOR_ID );

		foreach ( $rows as $row ) {
			add_post_meta( $route_id, self::META_OPERATOR_ID, (int) $row['operator_id'] );
		}
	}

	private static function normalize_row( array $row, $operator_id ) {
		$defaults = MTTF_Operator::get_operator_defaults( $operator_id );
		$name     = isset( $defaults['operator_name'] ) ? (string) $defaults['operator_name'] : get_the_title( $operator_id );

		return array(
			'operator_id'      => $operator_id,
			'operator_name'    => $name,
			'priority'         => isset( $defaults['priority'] ) ? absint( $defaults['priority'] ) : 0,
			'operator_active'  => isset( $defaults['is_active'] ) ? (int) $defaults['is_active'] : 0,
			'operator_defaults' => $defaults,
		);
	}

	private static function get_all_routes() {
		$posts = get_posts(
			array(
				'post_type'      => 'tuyen_xe',
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => -1,
			)
		);

		usort(
			$posts,
			static function ( $a, $b ) {
				$priority_a = (int) get_post_meta( $a->ID, '_mttf_priority', true );
				$priority_b = (int) get_post_meta( $b->ID, '_mttf_priority', true );

				if ( $priority_a === $priority_b ) {
					return strcasecmp( get_the_title( $a ), get_the_title( $b ) );
				}

				return $priority_a <=> $priority_b;
			}
		);

		return $posts;
	}

	private static function get_assigned_route_ids_for_operator( $operator_id ) {
		$routes = self::get_operator_routes( $operator_id, false );
		return array_map(
			static function ( $route ) {
				return (int) $route->ID;
			},
			$routes
		);
	}

	private static function assign_routes_to_operator( $operator_id, array $target_route_ids ) {
		$target_map = array_fill_keys( $target_route_ids, true );

		foreach ( self::get_all_routes() as $route ) {
			$route_id = (int) $route->ID;
			$rows     = self::get_route_operator_rows( $route_id, false );
			$next_rows = array();
			$found    = false;

			foreach ( $rows as $row ) {
				if ( (int) $row['operator_id'] === $operator_id ) {
					$found = true;
					if ( isset( $target_map[ $route_id ] ) ) {
						$next_rows[] = array( 'operator_id' => $operator_id );
					}
					continue;
				}

				$next_rows[] = array(
					'operator_id' => (int) $row['operator_id'],
				);
			}

			if ( isset( $target_map[ $route_id ] ) && ! $found ) {
				$next_rows[] = array(
					'operator_id' => $operator_id,
				);
			}

			$next_rows = self::sanitize_rows( $next_rows );
			update_post_meta( $route_id, self::META_ROWS, $next_rows );
			self::sync_operator_index_meta( $route_id, $next_rows );
		}
	}
}
