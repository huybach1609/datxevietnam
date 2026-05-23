# Route Pages UX Plan

## Goal

Thiết kế lại cụm trang `/route` thành một flow Google Ads rõ ràng, trong đó `/route/` là trang trung gian khám phá, còn `?route=` và `?operator=` là các landing page có ý định tìm kiếm cụ thể hơn.

Các URL cần hỗ trợ:

- `/route/`: trang tổng quan tất cả tuyến và nhà xe.
- `/route/?route=nameroute`: trang tuyến, hiển thị các nhà xe đang khai thác tuyến đó.
- `/route/?operator=nameoperator`: trang nhà xe, hiển thị các tuyến mà nhà xe đang khai thác.

Reference chính: `docs/screencapture-bluemoreyachting-gulet-charter-fethiye-2026-05-23-14_02_47.png`.

Điểm học từ reference:

- Hero full-width dùng ảnh thật làm tín hiệu đầu tiên.
- Header/filter nổi ở mép dưới hero để tạo cảm giác search experience cao cấp.
- Nội dung sau hero đi theo nhịp: giới thiệu ngắn, danh sách chính, các section hỗ trợ.
- Card có ảnh lớn, thông tin cô đọng, CTA rõ.
- Trang có cảm giác destination page, không chỉ là danh sách dữ liệu.

## UX Principles

- Người dùng Ads thường đến với nhu cầu nhanh: tìm tuyến, so sánh nhà xe, gọi hoặc Zalo.
- Không bắt người dùng đọc dài trước khi thấy lựa chọn chính.
- Mỗi trang có một primary action khác nhau:
- `/route/`: chọn một nhà xe hoặc một tuyến.
- `/route/?route=`: chọn nhà xe đang chạy tuyến đó.
- `/route/?operator=`: chọn tuyến của nhà xe đó.
- Hero dùng ảnh thật của xe, ghế, đường đi hoặc điểm đến. Không dùng hero dạng card xanh nhạt như hiện tại.
- Component phải chịu được data ít: 0 nhà xe, 1 nhà xe, 2 tuyến, hoặc hàng chục tuyến.

## Page Shell

Theme vẫn giữ header site hiện tại, nhưng trong plugin cần có một dynamic route header nằm ngay đầu route page.

Dynamic header variants:

- `/route/`: CTA chung, ví dụ `Đặt xe limousine toàn quốc, chọn tuyến hoặc hãng xe phù hợp`.
- `/route/?route=ha-noi-sapa`: CTA kèm tuyến, ví dụ `Đặt xe tuyến Hà Nội ⇄ Sapa, xem nhà xe đang khai thác`.
- `/route/?operator=s-trip-viet-nam`: CTA kèm operator, ví dụ `Đặt vé S-Trip Việt Nam, xem các tuyến đang mở bán`.

Header component nên gồm:

- Brand/context label ngắn.
- Dynamic CTA sentence.
- Quick action: `Gọi 1900 8164`.
- Secondary action: `Tư vấn Zalo`.
- Trên mobile, CTA sentence giữ tối đa 2 dòng, action gom thành 2 nút ngang hoặc sticky bottom mini bar.

## Shared Hero

Hero áp dụng cho cả 3 trang, full-width giống Bluemore:

- Background image chiếm toàn bộ chiều ngang content/page.
- Overlay tối nhẹ hoặc blue-tint để chữ đọc được.
- Breadcrumb nhỏ ở phía trên nội dung hero.
- H1 lớn, ngắn, đi thẳng vào intent.
- Subcopy 1-2 câu, không quá 120 ký tự nếu có thể.
- Hero facts strip ở đáy hero, giống reference:
- `/route/`: số tuyến, số nhà xe, miền đang phục vụ, hotline.
- `/route/?route=`: giá từ, số nhà xe, khu vực, tần suất hoặc hotline.
- `/route/?operator=`: số tuyến, khu vực, hotline, loại xe chính nếu có.
- Hero search/filter bar nổi ở mép dưới chỉ dùng ở `/route/`; với 2 trang detail, dùng compact action bar hoặc tabs.

Image rules:

- `/route/`: dùng ảnh fleet/limousine rộng, ưu tiên ảnh có xe thật.
- `/route/?route=`: dùng ảnh route thumbnail hoặc ảnh điểm đến/tuyến.
- `/route/?operator=`: dùng logo/ảnh nhà xe phối với ảnh fleet, không để logo nhỏ đơn độc.

## Page 1: `/route/`

Role: trang trung gian. Không cố convert ngay bằng form dài. Mục tiêu là đưa người dùng sang route detail hoặc operator detail đúng intent.

Flow:

1. Hero full-width.
2. Filter/search bar nổi.
3. Danh sách nhà xe.
4. Danh sách tuyến xe.
5. Optional trust strip hoặc FAQ ngắn nếu cần cho Ads.

Components:

- `RouteDynamicHeader`
- `RouteHero`
- `RouteSearchDock`
- `OperatorRail`
- `RouteDirectoryGrid`
- `RouteEmptyState`

`RouteSearchDock`:

- Input: tìm tuyến, điểm đến, nhà xe.
- Chips: Miền Bắc, Miền Nam, Miền Trung, Cabin VIP, Limousine.
- Mobile: input full-width, chips horizontal scroll.

`OperatorRail`:

- Card nhà xe có logo, tên, số tuyến, miền chính.
- Click card chuyển sang `/route/?operator=operator-slug`.
- Với ít operator, dùng row lớn 2-4 item.
- Với nhiều operator, dùng grid responsive hoặc carousel nhẹ.
- Không hiển thị mô tả dài ở trang tổng quan.

`RouteDirectoryGrid`:

- Card tuyến có ảnh, tên tuyến, giá từ, số nhà xe, loại xe, rating nếu có.
- Primary link: `Xem nhà xe tuyến này`.
- Click card hoặc CTA chuyển sang `/route/?route=route-slug`.
- Không mở modal lead khi click card trong `/route/`, chỉ mở modal khi bấm `Tư vấn ngay`.

Empty states:

- Không có operator: ẩn `OperatorRail`, vẫn hiện tuyến.
- Không có tuyến: hiện block hỗ trợ gọi hotline và Zalo.
- Search không có kết quả: giữ search term, đề xuất `Xóa bộ lọc` và hotline.

## Page 2: `/route/?operator=nameoperator`

Role: landing page theo nhà xe. Người dùng đã quan tâm một hãng cụ thể, cần xem hãng này chạy tuyến nào và gọi nhanh.

Flow:

1. Hero full-width theo operator.
2. Operator summary strip.
3. Danh sách tuyến xe của operator.
4. CTA cuối trang theo operator.

Components:

- `OperatorDynamicHeader`
- `OperatorHero`
- `OperatorInfoStrip`
- `OperatorRouteCardGrid`
- `OperatorRouteCard`
- `OperatorCTA`

`OperatorHero`:

- H1: tên nhà xe.
- Subcopy: mô tả ngắn từ operator summary.
- Facts: số tuyến, khu vực, hotline, Zalo.
- Primary CTA: `Gọi nhà xe`.
- Secondary CTA: `Xem tất cả tuyến`.

`OperatorRouteCard`:

- Component riêng, không dùng y nguyên card route tổng quan nếu card hiện tại quá nặng.
- Nội dung nên có:
- ảnh tuyến/xe.
- tên tuyến.
- giá từ.
- tần suất.
- loại xe.
- trạng thái `Đang khai thác`.
- CTA: `Xem tuyến này`.
- Secondary CTA: `Tư vấn`.

Behavior:

- Click `Xem tuyến này` sang `/route/?route=route-slug`.
- Click `Tư vấn` mở lead modal, route context là tuyến đó và operator context là nhà xe hiện tại.
- Search trong trang operator chỉ lọc tuyến của operator.

Empty states:

- Operator không có route: hero vẫn hiện, dưới là CTA gọi hotline và link về `/route/`.
- Operator thiếu logo/ảnh: dùng initials + fleet background fallback.
- Operator thiếu hotline: fallback hotline site.

## Page 3: `/route/?route=nameroute`

Role: landing page theo tuyến. Đây là trang quan trọng nhất cho Google Ads vì query thường là tuyến cụ thể.

Flow:

1. Hero full-width theo tuyến.
2. Route summary strip.
3. Danh sách card nhà xe chạy tuyến đó.
4. Optional related routes cùng miền hoặc cùng điểm đến.
5. CTA cuối trang.

Components:

- `RouteDynamicHeader`
- `RouteHero`
- `RouteInfoStrip`
- `RouteOperatorCardGrid`
- `RouteOperatorCard`
- `RelatedRoutes`
- `RouteCTA`

`RouteHero`:

- H1: `Xe Cabin VIP Hà Nội ⇄ Sapa`.
- Subcopy: một câu cam kết hoặc mô tả route.
- Facts: giá từ, số nhà xe, khu vực, tần suất.
- Primary CTA: `Gọi tư vấn tuyến này`.
- Secondary CTA: `Xem tất cả tuyến`.

`RouteOperatorCard`:

- Đây là card riêng cho cặp route + operator.
- Nội dung nên có:
- logo operator hoặc ảnh xe.
- tên operator.
- route name nhỏ để giữ context.
- giá từ, nếu sau này có relation metadata thì dùng giá riêng của operator-route.
- hotline/Zalo.
- số tuyến operator đang chạy.
- mô tả 1-2 dòng.
- CTA chính: `Gọi ngay`.
- CTA phụ: `Xem các tuyến của hãng`.

Behavior:

- Click operator name/card secondary sang `/route/?operator=operator-slug`.
- Click `Gọi ngay` dùng hotline operator, fallback route, fallback site.
- Click `Zalo` dùng Zalo operator, fallback route, fallback site.

Empty states:

- Route không có operator: hiển thị CTA `Gọi tổng đài để được xếp xe`.
- Route không tồn tại: trang not-found trong plugin, không nên rơi về homepage.
- Route thiếu ảnh: dùng ảnh mặc định theo region hoặc car type.

## Component Inventory

Shared components:

- `RoutePageShell`
- `DynamicRouteHeader`
- `FullBleedHero`
- `HeroFactsStrip`
- `SearchDock`
- `FilterChips`
- `LeadModal`
- `EmptyState`
- `FinalCTA`

Directory-only:

- `OperatorRail`
- `OperatorMiniCard`
- `RouteDirectoryGrid`
- `RouteDiscoveryCard`

Operator page:

- `OperatorInfoStrip`
- `OperatorRouteCardGrid`
- `OperatorRouteCard`

Route page:

- `RouteInfoStrip`
- `RouteOperatorCardGrid`
- `RouteOperatorCard`
- `RelatedRoutes`

## CSS File Ownership

Keep CSS inside plugin because these pages are plugin-owned.

Current split should evolve as:

- `frontend.css`: shared plugin tokens and legacy hub/card/modal styles.
- `route-base.css`: shared route page shell, dynamic header, hero, facts strip, empty state.
- `route-directory.css`: `/route/` only.
- `route-operator-detail.css`: `/route/?operator=` only.
- `route-route-detail.css`: `/route/?route=` only.

Theme `generatepress_child` should only handle:

- no-sidebar layout.
- page container width if GeneratePress constrains it.
- site header/footer integration.

## Implementation Phases

Phase 1: khung HTML trước.

- Add wrapper classes already planned:
- `.mttf-route-page--directory`
- `.mttf-route-page--operator-detail`
- `.mttf-route-page--route-detail`
- Add `DynamicRouteHeader` partial.
- Replace current card-like hero with `FullBleedHero` partial.
- Add facts strip data per view.
- Keep existing route/operator data helpers.
- Keep current modal behavior.

Phase 2: component split.

- Build `OperatorMiniCard`.
- Build `RouteDiscoveryCard`.
- Build `OperatorRouteCard`.
- Build `RouteOperatorCard`.
- Stop reusing the same heavy route card for all contexts.

Phase 3: styling.

- Implement full-width hero inspired by Bluemore.
- Add floating search dock on `/route/`.
- Add compact action/facts strip for detail pages.
- Tune responsive layout.
- Ensure mobile hero does not consume the whole first viewport; show start of next section.

Phase 4: conversion behavior.

- Add operator context to modal payload.
- Add CTA tracking labels per page type.
- Add empty/error states.
- Add related routes for `/route/?route=`.

Phase 5: QA.

- Test `/route/`.
- Test `/route/?route=valid`.
- Test `/route/?operator=valid`.
- Test invalid route/operator.
- Test 0 operator, 1 operator, many operators.
- Test mobile width 375px and desktop width 1440px.
- Check click targets and no modal hijack on navigation links.

## Visual Direction

Recommended direction: travel-commerce, image-led, premium but direct.

Scene sentence:

Người dùng đang trên điện thoại hoặc laptop, vừa search tuyến xe trên Google Ads, muốn nhìn thấy ngay tuyến/nhà xe đáng tin và có số gọi trong vài giây.

Color strategy:

- Restrained base for content readability.
- Committed blue/yellow brand accents for CTA and facts.
- Hero image carries most of the mood instead of flat blue panels.

Typography:

- Keep current brand/site type unless changing site-wide later.
- H1 in hero should be large but not oversized on detail pages.
- Card text needs tighter hierarchy: title, one fact row, CTA.

Motion:

- Page load: hero content fade/slide once.
- Card hover: subtle lift.
- Search dock: no heavy animation.
- Mobile: avoid motion that delays CTA.

## Open Decisions Before Coding

- Default hero image source for `/route/`: site setting, plugin setting, or hard-coded fallback.
- Whether route/operator detail cards should open lead modal directly or always navigate first.
- Whether `/route/?operator=` should show operator intro text long-form or keep it compact.
- Whether relation metadata should be expanded later for operator-specific price, schedule and vehicle type.

## Acceptance Criteria For The First Frame

- `/route/` clearly looks like a route discovery page, not a generic shortcode list.
- `/route/?route=` clearly answers: which nhà xe run this route?
- `/route/?operator=` clearly answers: which routes does this nhà xe run?
- Each page has a unique wrapper class and CSS file.
- Hero is image-led and full-width.
- Primary click path is visible without scrolling too far.
- Mobile view shows hero plus at least the start of the next component in the first viewport.
