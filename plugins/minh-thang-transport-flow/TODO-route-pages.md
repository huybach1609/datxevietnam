# TODO: Fix Route Pages

Ngày tạo: 2026-05-26

## Hiện trạng

- `/route/?operator=...` đang chạy ổn.
- `/route/?route=...` đã vào đúng trang tuyến nhưng layout đang vỡ, xử lý sau.
- `/route/?route=...&operator=...` đang dễ rơi vào not found do link từ trang tuyến chưa tạo đúng URL chi tiết và resolver còn phụ thuộc vào data mới chưa đầy đủ.

## Mục tiêu URL

- `/route/`: danh sách `tuyen_xe`.
- `/route/?route={route_slug}`: trang tuyến, chỉ hiển thị các `bai_xe` thuộc tuyến đó.
- `/route/?route={route_slug}&operator={operator_slug}`: trang chi tiết một `bai_xe`.

## Ưu tiên 1: Fix Điều Hướng Từ Trang Tuyến

- [ ] Sửa `templates/route/route-archive.php` không gọi `$render_directory_route_sections()` cho danh sách `bai_xe`.
- [ ] Tạo renderer riêng cho danh sách `bai_xe` trong route archive, ví dụ `render_article_route_sections()` hoặc `render_article_discovery_sections()`.
- [ ] Renderer mới phải dùng `render_route_discovery_card()` cho từng `bai_xe`, không dùng `render_route_summary_card()`.
- [ ] Card `bai_xe` trên trang `?route=` phải build URL bằng `build_article_directory_url()`.
- [ ] URL card phải có đủ dạng `?route={route_slug}&operator={operator_slug}`.
- [ ] CTA trên card bài xe nên là `Xem bài xe này` hoặc `Xem chi tiết`, không dùng lại nhãn tổng hợp `Xem tuyến này`.

## Ưu tiên 2: Fix Resolver `route + operator`

- [ ] Mở rộng `find_article_by_route_and_operator()` để fallback theo mapping cũ `MTTF_Route_Operators::get_route_operator_rows()` khi `_mttf_selected_operator_id` chưa được set.
- [ ] Resolver nên match theo thứ tự:
  - `_mttf_route_slug = route_slug`
  - `_mttf_selected_operator_id = operator_id`
  - fallback: bài có `_mttf_route_slug = route_slug` và operator nằm trong `_mttf_route_operator_rows`
  - `_mttf_is_active = 1`
- [ ] Nếu có nhiều bài match fallback, ưu tiên bài có `_mttf_priority` cao hơn, sau đó date mới hơn.
- [ ] Khi fallback match thành công, cân nhắc tự ghi `_mttf_selected_operator_id` để data sạch dần.

## Ưu tiên 3: Chuẩn Hóa Slug Data

- [ ] Kiểm tra lại các `Route Slug` trong `bai_xe`; không nên chứa tên nhà xe.
- [ ] Ví dụ nên dùng `xe-cabin-vip-ha-noi-sapa` hoặc tốt hơn `ha-noi-sapa`, không dùng `xe-cabin-vip-ha-noi-sapa-hkbusline`.
- [ ] `operator` slug phải nằm riêng ở `mttf_operator`, ví dụ `hkbusline` hoặc `s-trip-viet-nam`.
- [ ] Trang chi tiết cuối nên là `?route=xe-cabin-vip-ha-noi-sapa&operator=hkbusline`, không gộp operator vào `route`.
- [ ] Viết script/admin action nhỏ để audit các `bai_xe` có `_mttf_route_slug` chứa operator slug.

## Ưu tiên 4: Dọn Layout Trang `?route=`

- [ ] Tách CSS riêng cho `.mttf-route-page--route-archive` nếu layout đang kế thừa sai từ directory.
- [ ] Fix block `Nhà xe đang khai thác` đang hiển thị logo/title quá lớn và thiếu grid/card styling.
- [ ] Đảm bảo section bài xe dùng grid giống directory card, không thành layout một cột rời rạc.
- [ ] Kiểm tra hero trên desktop/mobile, đặc biệt chiều cao ảnh và form/summary không chồng lên nội dung dưới.
- [ ] Kiểm tra footer không bị kéo sát vào card khi danh sách ít bài.

## Ưu tiên 5: Kiểm Thử

- [ ] Test `/route/` vẫn hiển thị danh sách tuyến.
- [ ] Test `/route/?route=xe-cabin-vip-ha-noi-sapa` hiển thị trang tuyến và danh sách `bai_xe`.
- [ ] Test click từng card trong trang tuyến có URL đủ `route + operator`.
- [ ] Test `/route/?route=xe-cabin-vip-ha-noi-sapa&operator=hkbusline` mở đúng bài xe.
- [ ] Test `/route/?route=xe-cabin-vip-ha-noi-sapa&operator=s-trip-viet-nam` mở đúng bài xe khác cùng tuyến.
- [ ] Test `/route/?operator=s-trip-viet-nam` vẫn không regression.
- [ ] Chạy `php -l` cho các file PHP sửa.
- [ ] Chạy `gitnexus_detect_changes()` trước khi commit.

## File Dự Kiến Sửa

- `includes/class-mttf-shortcode.php`
- `templates/route/route-archive.php`
- `templates/route/partials/route-discovery-card.php`
- `assets/css/route-directory.css`
- `assets/css/route-route-detail.css`
- `assets/css/route-base.css`
