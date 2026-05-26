<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Lead log admin UI (filter by route / phone).
 */
class MTTF_Lead_Admin {
	public static function sanitize_per_page() {
		$p = isset( $_REQUEST['per_page'] ) ? absint( wp_unslash( $_REQUEST['per_page'] ) ) : 25;
		if ( $p < 10 ) {
			$p = 10;
		}
		if ( $p > 500 ) {
			$p = 500;
		}
		return $p;
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_export_csv' ) );
	}

	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=' . MTTF_CPT::get_article_post_type(),
			'Lead & thống kê',
			'Lead',
			'manage_options',
			'mttf-leads',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function maybe_export_csv() {
		if ( ! is_admin() || ! isset( $_GET['page'], $_GET['mttf_export_csv'], $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( 'mttf-leads' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '' );
		}

		check_admin_referer( 'mttf_export_csv' );

		$f_route = isset( $_GET['route_id'] ) ? absint( $_GET['route_id'] ) : 0;
		$f_phone = isset( $_GET['phone'] ) ? sanitize_text_field( wp_unslash( $_GET['phone'] ) ) : '';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=mttf-leads-' . gmdate( 'Y-m-d-His' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		if ( false === $out ) {
			exit;
		}

		// BOM UTF-8 để Excel mở tiếng Việt đúng.
		fwrite( $out, chr( 239 ) . chr( 187 ) . chr( 191 ) );

		fputcsv(
			$out,
			array(
				'ID',
				'Ngày giờ',
				'SĐT',
				'ID tuyến',
				'Slug tuyến',
				'Tên tuyến',
				'Miền',
				'App quốc tế',
				'URL nguồn',
				'UTM/Click JSON',
			)
		);

		$r = MTTF_Lead_DB::query_leads(
			array(
				'route_id'   => $f_route,
				'phone'      => $f_phone,
				'paged'      => 1,
				'per_page'   => 25,
				'export_cap' => 12000,
			)
		);

		foreach ( $r['items'] as $row ) {
			fputcsv(
				$out,
				array(
					(int) $row->id,
					isset( $row->created_at ) ? (string) $row->created_at : '',
					isset( $row->phone ) ? (string) $row->phone : '',
					(int) $row->route_id,
					isset( $row->route_slug ) ? (string) $row->route_slug : '',
					isset( $row->route_title ) ? (string) $row->route_title : '',
					isset( $row->hub_region ) ? (string) $row->hub_region : '',
					isset( $row->contact_apps ) ? (string) $row->contact_apps : '',
					isset( $row->source_url ) ? (string) $row->source_url : '',
					isset( $row->utm_json ) ? (string) $row->utm_json : '',
				)
			);
		}

		fclose( $out );
		exit;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		$list = new MTTF_Leads_List_Table();

		$routes = get_posts(
			array(
				'post_type'      => MTTF_CPT::get_article_post_type(),
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 600,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);
		$route_sel = isset( $_GET['route_id'] ) ? absint( $_GET['route_id'] ) : 0;
		$phone_q   = isset( $_GET['phone'] ) ? sanitize_text_field( wp_unslash( $_GET['phone'] ) ) : '';

		$list->prepare_items();

		$export_args = array(
			'post_type'         => MTTF_CPT::get_article_post_type(),
			'page'              => 'mttf-leads',
			'mttf_export_csv'   => '1',
		);
		if ( $route_sel > 0 ) {
			$export_args['route_id'] = (string) $route_sel;
		}
		if ( '' !== $phone_q ) {
			$export_args['phone'] = $phone_q;
		}
		$url_export = wp_nonce_url( add_query_arg( $export_args, admin_url( 'edit.php' ) ), 'mttf_export_csv' );

		$total_global = MTTF_Lead_DB::count_all();

		echo '<div class="wrap">';
		echo '<h1>Lead đặt tuyến (MTTF)</h1>';

		echo '<form method="get" id="mttf-leads-form" action="' . esc_url( admin_url( 'edit.php' ) ) . '">';
		echo '<input type="hidden" name="post_type" value="' . esc_attr( MTTF_CPT::get_article_post_type() ) . '">';
		echo '<input type="hidden" name="page" value="mttf-leads">';
		echo '<div style="display:flex;align-items:flex-end;gap:10px;margin:16px 0 14px;flex-wrap:wrap;">';
		echo '<label><span>Tuyến:</span>&nbsp;<select name="route_id">';
		echo '<option value="0">— Tất cả —</option>';
		foreach ( $routes as $rid ) {
			$t = get_the_title( $rid );
			printf(
				'<option value="%d" %s>%s</option>',
				(int) $rid,
				selected( $route_sel, $rid, false ),
				esc_html( $t )
			);
		}
		echo '</select></label>';
		echo '<label><span>SĐT khách:</span>&nbsp;<input type="search" name="phone" class="regular-text" value="' . esc_attr( $phone_q ) . '" placeholder="VD: 097… hoặc +84" autocomplete="off"></label>';
		submit_button( 'Lọc', 'secondary', '', false );
		echo '</div>';

		if ( MTTF_Lead_DB::table_exists() ) {
			printf(
				'<p>Tổng lead khớp bộ lọc hiện tại: <strong>%d</strong>. Đã lưu trong hệ thống (toàn cục): <strong>%d</strong>.</p>',
				(int) $list->_total_filtered,
				(int) $total_global
			);
		} else {
			echo '<p>Bảng lead chưa sẵn sàng — thử load lại trang sau khi plugin cập nhật.</p>';
		}

		echo '<p><a class="button button-primary" href="' . esc_url( $url_export ) . '">Xuất CSV (tối đa 12000 dòng, theo lọc)</a>';
		echo ' <span class="description">Nội bộ — file chứa SĐT đầy đủ.</span></p>';

		$list->display();
		echo '</form>';

		echo '</div>';
	}
}

class MTTF_Leads_List_Table extends WP_List_Table {
	public $_total_filtered = 0;

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'mttf_lead',
				'plural'   => 'mttf_leads',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'created_at'   => 'Thời gian',
			'phone'        => 'SĐT',
			'route_title'  => 'Tuyến',
			'hub_region'   => 'Miền',
			'contact_apps' => 'App',
			'source_short' => 'Nguồn',
		);
	}

	protected function column_default( $item, $column_name ) {
		return '';
	}

	protected function column_created_at( $item ) {
		$t = isset( $item->created_at ) ? (string) $item->created_at : '';
		if ( '' === $t ) {
			return '—';
		}

		try {
			$tz = wp_timezone();
			$dt = new DateTimeImmutable( $t, $tz );
			return '<code>' . esc_html( wp_date( 'd/m/Y H:i', $dt->getTimestamp() ) ) . '</code>';
		} catch ( Exception $e ) {
			return esc_html( $t );
		}
	}

	protected function column_phone( $item ) {
		$p = isset( $item->phone ) ? (string) $item->phone : '';
		return '' !== $p ? '<strong>' . esc_html( $p ) . '</strong>' : '—';
	}

	protected function column_route_title( $item ) {
		$title = isset( $item->route_title ) ? (string) $item->route_title : '';
		if ( isset( $item->route_id ) && (int) $item->route_id > 0 ) {
			$link = get_edit_post_link( (int) $item->route_id );
			if ( $link ) {
				return '<a href="' . esc_url( $link ) . '">' . esc_html( '' !== $title ? $title : '#' . (int) $item->route_id ) . '</a>';
			}
		}

		return esc_html( '' !== $title ? $title : '—' );
	}

	protected function column_hub_region( $item ) {
		$r   = isset( $item->hub_region ) ? (string) $item->hub_region : '';
		$map = array(
			'bac'   => 'Bắc',
			'trung' => 'Trung',
			'nam'   => 'Nam',
			'khac'  => 'Khác',
		);

		return esc_html( $map[ $r ] ?? $r );
	}

	protected function column_contact_apps( $item ) {
		$c = isset( $item->contact_apps ) ? trim( (string) $item->contact_apps ) : '';
		return '' !== $c ? esc_html( $c ) : '—';
	}

	protected function column_source_short( $item ) {
		$u = isset( $item->source_url ) ? (string) $item->source_url : '';
		if ( '' === $u ) {
			return '—';
		}

		return '<span title="' . esc_attr( $u ) . '">' . esc_html( wp_trim_words( $u, 5, '…' ) ) . '</span>';
	}

	public function no_items() {
		echo '<p>Không có lead nào khớp bộ lọc (hoặc chưa có ai gửi form).</p>';
	}

	public function prepare_items() {
		$f_route = isset( $_REQUEST['route_id'] ) ? absint( wp_unslash( $_REQUEST['route_id'] ) ) : 0;
		$f_phone = isset( $_REQUEST['phone'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['phone'] ) ) : '';
		$pp      = MTTF_Lead_Admin::sanitize_per_page();

		$paged = $this->get_pagenum();
		if ( $paged < 1 ) {
			$paged = 1;
		}

		$res = MTTF_Lead_DB::query_leads(
			array(
				'route_id' => $f_route,
				'phone'    => $f_phone,
				'paged'    => $paged,
				'per_page' => $pp,
			)
		);

		$this->items           = $res['items'];
		$this->_total_filtered = $res['total'];

		$this->set_pagination_args(
			array(
				'total_items' => $res['total'],
				'per_page'    => $pp,
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), array() );
	}

	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$cur     = (int) ( $this->_pagination_args['per_page'] ?? MTTF_Lead_Admin::sanitize_per_page() );
		$presets = array( 10, 25, 50, 100, 150, 200, 300, 500 );
		if ( ! in_array( $cur, $presets, true ) ) {
			$presets[] = $cur;
			sort( $presets, SORT_NUMERIC );
		}

		echo '<div class="alignleft actions mttf-leads-per-page-wrap">';
		echo '<label for="mttf-leads-per-page"><span class="title-count">Số hàng hiển thị: </span>';
		echo '<select id="mttf-leads-per-page" name="per_page" onchange="this.form.submit()">';
		foreach ( $presets as $n ) {
			printf(
				'<option value="%d" %s>%d</option>',
				(int) $n,
				selected( $cur, $n, false ),
				(int) $n
			);
		}
		echo '</select></label>';
		echo '</div>';
	}

	protected function pagination( $which ) {
		if ( ! isset( $this->_pagination_args['total_items'], $this->_pagination_args['per_page'] ) ) {
			return;
		}

		$total_items = (int) $this->_pagination_args['total_items'];
		$per_page    = max( 1, (int) $this->_pagination_args['per_page'] );

		if ( $total_items <= 0 ) {
			echo '<div class="tablenav-pages no-pages"><span class="displaying-num">';
			echo esc_html( '0 trong tổng số 0' );
			echo '</span></div>';
			return;
		}

		$total_pages = (int) ( $this->_pagination_args['total_pages'] ?? 0 );
		if ( $total_pages < 1 ) {
			$total_pages = (int) max( 1, ceil( $total_items / $per_page ) );
		}

		$infinite_scroll = false;
		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}

		if ( 'top' === $which && $total_pages > 1 && $this->screen instanceof WP_Screen ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$current = $this->get_pagenum();
		$from    = ( $current - 1 ) * $per_page + 1;
		$to      = min( $current * $per_page, $total_items );

		$output = '<span class="displaying-num">' . esc_html(
			sprintf(
				'%1$s - %2$s trong tổng số %3$s',
				number_format_i18n( $from ),
				number_format_i18n( $to ),
				number_format_i18n( $total_items )
			)
		) . '</span>';

		$removable_query_args = wp_removable_query_args();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( $removable_query_args, $current_url );

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = false;
		$disable_last  = false;
		$disable_prev  = false;
		$disable_next  = false;

		if ( 1 === $current ) {
			$disable_first = true;
			$disable_prev  = true;
		}
		if ( $total_pages === $current ) {
			$disable_last = true;
			$disable_next = true;
		}

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				__( 'First page', 'default' ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
				__( 'Previous page', 'default' ),
				'&lsaquo;'
			);
		}

		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = sprintf(
				'<span class="screen-reader-text">%s</span>' .
				'<span id="table-paging" class="paging-input">' .
				'<span class="tablenav-paging-text">',
				__( 'Current Page', 'default' )
			);
		} else {
			$html_current_page = sprintf(
				'<label for="current-page-selector" class="screen-reader-text">%s</label>' .
				"<input class='current-page' id='current-page-selector' type='text'"
					. " name='paged' value='%s' size='%d' aria-describedby='table-paging' />" .
				"<span class='tablenav-paging-text'>",
				__( 'Current Page', 'default' ),
				$current,
				strlen( (string) $total_pages )
			);
		}

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );

		$page_links[] = $total_pages_before . sprintf(
			/* translators: 1: Current page, 2: Total pages. */
			_x( '%1$s of %2$s', 'paging', 'default' ),
			$html_current_page,
			$html_total_pages
		) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
				__( 'Next page', 'default' ),
				'&rsaquo;'
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				__( 'Last page', 'default' ),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class .= ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- mirrors core WP_List_Table::pagination markup.
		echo $this->_pagination;
	}

	protected function get_views() {
		return array();
	}
}
