<?php
require __DIR__ . '/lib/helpers.php';
$config = require __DIR__ . '/config.php';
$assets = $config['assets'] ?? [];
$footerLinks = $config['footerLinks'] ?? [];

$logo = asset_url($assets['logoUrl'] ?? '');

$vehicles = [
    ['key' => 'vehicle16ImageUrl', 'title' => 'Xe 16 chỗ', 'items' => ['Phù hợp: gia đình, nhóm nhỏ 10–14 người.', 'Ưu điểm: linh hoạt, dễ di chuyển, chi phí thấp.', 'Tiện nghi: cơ bản, máy lạnh, ghế tiêu chuẩn.', 'Cảm giác: gần gũi, không quá rộn.']],
    ['key' => 'vehicle29ImageUrl', 'title' => 'Xe 29 chỗ thường', 'items' => ['Phù hợp: nhóm trung bình 15–25 người.', 'Ưu điểm: cân bằng giữa chi phí và sức chứa.', 'Tiện nghi: cơ bản, đủ dùng cho chuyến đi vừa phải.', 'Cảm giác: rộng, thoải mái.']],
    ['key' => 'vehicleLimoImageUrl', 'title' => 'Xe 29 chỗ Limousine', 'items' => ['Phù hợp: nhóm trung bình 15–25 người.', 'Ưu điểm: ghế VIP, không gian rộng, riêng tư.', 'Tiện nghi: ghế massage, wifi, cổng sạc.', 'Cảm giác: sang trọng như hạng thương gia.']],
    ['key' => 'vehicle45ImageUrl', 'title' => 'Xe 45 chỗ', 'items' => ['Phù hợp: đoàn lớn, công ty, tour đông người.', 'Ưu điểm: tiết kiệm chi phí theo đầu người.', 'Tiện nghi: tiêu chuẩn, rộng rãi cho đi tập thể.', 'Cảm giác: thoáng, phù hợp lịch trình dài.']],
];

$reviews = [
    ['key' => 'testimonialImage1', 'name' => 'Khách gia đình', 'quote' => 'Xe sạch sẽ, lịch trình linh hoạt và tài xế hỗ trợ rất nhiệt tình cho chuyến đi gia đình.'],
    ['key' => 'testimonialImage2', 'name' => 'Nhóm bạn đi chơi', 'quote' => 'Không gian thoải mái, đặc biệt dòng Limousine cho trải nghiệm rất ưng ý khi đi xa.'],
    ['key' => 'testimonialImage3', 'name' => 'Đoàn công ty', 'quote' => 'Xe đúng giờ, tài xế thân thiện, phù hợp cho team building và các lịch trình đoàn.'],
    ['key' => 'testimonialImage4', 'name' => 'Khách tour', 'quote' => 'Dịch vụ hỗ trợ nhanh, xe rộng rãi và sạch sẽ trong suốt chuyến đi dài.'],
];
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <meta name="theme-color" content="<?= e($config['brandColor'] ?? '#8BC34A') ?>">
  <title><?= e($config['brandName'] ?? 'NAM THẮNG TRAVEL BUS') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css?v=<?= rawurlencode(APP_VERSION) ?>">
  <script>
    window.APP_CONFIG = <?= json_encode([
      'androidLink' => $config['androidLink'] ?? '',
      'iosLink' => $config['iosLink'] ?? '',
      'version' => APP_VERSION,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  </script>
</head>
<body>
<header class="header">
  <div class="container header-inner">
    <a class="brand brand-logo-only" href="#top" aria-label="<?= e($config['brandName'] ?? 'NAM THẮNG TRAVEL BUS') ?>">
      <div class="brand-logo">
        <?php if ($logo): ?>
          <img src="<?= e($logo) ?>" alt="<?= e($config['brandName'] ?? 'NAM THẮNG TRAVEL BUS') ?>" onerror="this.replaceWith(document.createTextNode('NT'))">
        <?php else: ?>NT<?php endif; ?>
      </div>
    </a>

    <div class="header-actions">
      <a class="btn btn-secondary" href="<?= e($config['hotlineHref'] ?? 'tel:0923098098') ?>">TƯ VẤN THUÊ XE</a>
      <button class="btn btn-primary magic-ticket-btn" type="button" data-scroll-form>THAM GIA NHẬN MAGIC TICKET</button>
    </div>
  </div>
</header>

<main id="top">
  <section class="hero container">
    <div class="hero-banner">
      <?php $hero = asset_url($assets['heroBannerImageUrl'] ?? ''); ?>
      <?php if ($hero): ?>
        <img src="<?= e($hero) ?>" alt="<?= e($config['campaignTitle'] ?? 'Nam Thắng Travel Bus') ?>">
      <?php else: ?>
        <div class="image-empty">Vui lòng thêm ảnh chiến dịch trong config.php</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="section" id="dong-xe">
    <div class="container">
      <h2 class="section-title">CÁC DÒNG XE CỦA NAM THẮNG TRAVEL BUS</h2>
      <p class="section-desc">Tối ưu theo đúng nhu cầu di chuyển: linh hoạt, vừa túi tiền hoặc trải nghiệm cao cấp cho đoàn của bạn.</p>
      <div class="vehicle-grid">
        <?php foreach ($vehicles as $vehicle): ?>
          <?php $img = asset_url($assets[$vehicle['key']] ?? $assets['vehicleDefaultImageUrl'] ?? ''); ?>
          <article class="vehicle-card">
            <div class="vehicle-visual">
              <?php if ($img): ?>
                <img src="<?= e($img) ?>" alt="<?= e($vehicle['title']) ?>" loading="lazy" onerror="this.parentElement.innerHTML='<span class=&quot;vehicle-icon&quot;>🚌</span>'">
              <?php else: ?>
                <span class="vehicle-icon">🚌</span>
              <?php endif; ?>
            </div>
            <h3><?= e($vehicle['title']) ?></h3>
            <ul>
              <?php foreach ($vehicle['items'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
            </ul>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section" id="tai-sao-chon">
    <div class="container">
      <div class="why-custom-card" aria-labelledby="whyCustomTitle">
        <h2 id="whyCustomTitle">HƠN <span>1000+ KHÁCH HÀNG</span> TIN CHỌN NAM THẮNG TRAVEL BUS</h2>
        <div class="why-feature-list">
          <article class="why-feature">
            <div class="why-feature-icon" aria-hidden="true">
              <svg class="why-logo-icon" viewBox="0 0 64 64">
                <rect x="14" y="10" width="36" height="42" rx="6" fill="#70be3a"/>
                <path d="M19 17h26v18H19z" fill="#263f39"/>
                <path d="M22 20h20v12H22z" fill="#315f7f"/>
                <path d="M18 39h28" stroke="#0f7f41" stroke-width="4" stroke-linecap="round"/>
                <circle cx="22" cy="46" r="4" fill="#263f39"/>
                <circle cx="42" cy="46" r="4" fill="#263f39"/>
                <path d="M19 55h8M37 55h8" stroke="#263f39" stroke-width="4" stroke-linecap="round"/>
              </svg>
            </div>
            <div class="why-feature-copy">
              <h3>XE ĐỜI MỚI 2026</h3>
              <p>Ghế ngồi êm ái, rộng rãi, nhiều tiện ích mới trên xe</p>
            </div>
          </article>

          <article class="why-feature why-feature-reverse">
            <div class="why-feature-icon" aria-hidden="true">
              <svg class="why-logo-icon" viewBox="0 0 64 64">
                <circle cx="32" cy="32" r="27" fill="#8bc63f"/>
                <circle cx="32" cy="27" r="9" fill="#ffd4a3"/>
                <path d="M21 25h22l-3-8H24l-3 8Z" fill="#0f7f41"/>
                <path d="M24 24h16" stroke="#0b6634" stroke-width="3" stroke-linecap="round"/>
                <path d="M19 53c2.6-9 7.1-13 13-13s10.4 4 13 13H19Z" fill="#0f7f41"/>
                <path d="M28 41l4 6 4-6" fill="#ffffff"/>
                <circle cx="28" cy="29" r="1.5" fill="#263f39"/>
                <circle cx="36" cy="29" r="1.5" fill="#263f39"/>
                <path d="M28 34c2.5 1.8 5.5 1.8 8 0" stroke="#9b5e31" stroke-width="2.4" stroke-linecap="round"/>
              </svg>
            </div>
            <div class="why-feature-copy">
              <h3>TÀI XẾ KINH NGHIỆM ĐƯỜNG DÀI</h3>
              <p>Lái an toàn, đúng lịch trình, hạn chế say xe cho người lớn tuổi và trẻ nhỏ</p>
            </div>
          </article>

          <article class="why-feature">
            <div class="why-feature-icon" aria-hidden="true">
              <svg class="why-logo-icon" viewBox="0 0 64 64">
                <rect x="13" y="13" width="38" height="38" rx="8" fill="#8bc63f"/>
                <rect x="21" y="21" width="22" height="22" rx="4" fill="none" stroke="#0f7f41" stroke-width="5"/>
                <path d="M25 32l5 5 10-12" fill="none" stroke="#0f7f41" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="why-feature-copy">
              <h3>ĐA DẠNG LỰA CHỌN</h3>
              <p>Với hệ thống xe từ 4-7-16-29-45 chỗ, đáp ứng mọi nhu cầu di chuyển</p>
            </div>
          </article>

          <article class="why-feature why-feature-reverse">
            <div class="why-feature-icon" aria-hidden="true">
              <svg class="why-logo-icon" viewBox="0 0 64 64">
                <circle cx="32" cy="32" r="27" fill="#8bc63f"/>
                <circle cx="32" cy="32" r="18" fill="#0f7f41"/>
                <circle cx="32" cy="32" r="13" fill="#8bc63f"/>
                <path d="M32 22v11l8 5" fill="none" stroke="#0f7f41" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="why-feature-copy">
              <h3>CHỦ ĐỘNG THỜI GIAN</h3>
              <p>Toàn bộ lịch trình được linh hoạt theo kế hoạch của đoàn di chuyển</p>
            </div>
          </article>
        </div>
      </div>
    </div>
  </section>

  <section class="section" id="magic-ticket">
    <div class="container checkin-wrap">
      <div class="howto-card">
        <h2 class="checkin-title">CHECK-IN NGAY – NHẬN MAGIC TICKET LIỀN TAY</h2>
        <?php $guide = asset_url($assets['checkinGuideImageUrl'] ?? ''); ?>
        <?php if ($guide): ?><img class="guide-img" src="<?= e($guide) ?>" alt="Thể lệ tham gia" loading="lazy"><?php endif; ?>
        <div class="step"><b>1</b><span>Chụp ảnh hoặc quay video sáng tạo cùng xe Nam Thắng Travel Bus.</span></div>
        <div class="step"><b>2</b><span>Đăng công khai Facebook hoặc TikTok kèm hashtag:<br><strong>#NamThangTravelBus #TaxiNamThang #ChuyenXeThanhThoi #XeXinMienTay #SongAoTrenXe</strong></span></div>
        <div class="step"><b>3</b><span>Điền thông tin bên cạnh và nhấn <strong>NHẬN MAGIC TICKET</strong>.</span></div>
        <div class="note-box">
          <strong>Lưu ý:</strong>
          <ul>
            <li>Voucher sẽ tự động gửi vào phần Ưu Đãi trên App Taxi Nam Thắng sau 24h.</li>
            <li>Ảnh check-in: nhận Voucher Magic Ticket 300K</li>
            <li>Video review: nhận Voucher Magic Ticket 600K</li>
            <li>Voucher áp dụng toàn bộ hệ thống Taxi điện Nam Thắng tại các tỉnh Miền Tây.</li>
            <li>Nếu chưa có ứng dụng, bạn vui lòng Tải App Taxi Nam Thắng <a href="<?= e($config['iosLink'] ?? $config['androidLink'] ?? '#') ?>" target="_blank" rel="noopener noreferrer">tại đây</a>.</li>
          </ul>
        </div>
      </div>

      <div class="form-card" id="magicTicketFormCard">
        <h2>Điền thông tin nhận Magic Ticket</h2>
        <p>Gửi đúng link check-in công khai và ảnh màn hình để đội ngũ xác minh nhanh hơn.</p>
        <form id="magicTicketForm" novalidate>
          <label>Số điện thoại <span>*</span><input id="soDienThoai" name="soDienThoai" type="tel" maxlength="20" required placeholder="Nhập số điện thoại"></label>
          <label>Họ tên <span>*</span><input id="hoTen" name="hoTen" type="text" maxlength="100" required placeholder="Nhập họ tên"></label>
          <label>Dán link check-in <span>*</span><textarea id="linkCheckIn" name="linkCheckIn" required placeholder="Dán link bài đăng Facebook hoặc TikTok công khai"></textarea></label>
          <div class="upload-box">
            <div><strong>Ảnh màn hình check-in</strong><small>Có thể chọn nhiều ảnh, tối đa 10 ảnh. Ảnh được nén trước khi gửi.</small></div>
            <button id="pickImagesBtn" type="button" class="pick-btn">Chọn ảnh</button>
            <input id="checkerImages" type="file" accept="image/*" multiple hidden>
            <div id="previewGrid" class="preview-grid"></div>
            <small id="previewCount">Chưa chọn ảnh nào.</small>
          </div>
          <button id="submitBtn" class="btn btn-primary submit-btn" type="submit">NHẬN MAGIC TICKET</button>
          <p class="privacy-note">Thông tin chỉ dùng để xác minh và gửi Magic Ticket.</p>
        </form>
      </div>
    </div>
  </section>

  <section class="section" id="reviews">
    <div class="container">
      <h2 class="section-title">Khách hàng nói gì về NAM THẮNG TRAVEL BUS</h2>
      <div class="reviews-track">
        <?php foreach ($reviews as $review): ?>
          <?php $img = asset_url($assets[$review['key']] ?? ''); ?>
          <article class="review-card">
            <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e($review['name']) ?>" loading="lazy"><?php endif; ?>
            <h3><?= e($review['name']) ?></h3><p><?= e($review['quote']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section" id="faq">
    <div class="container faq-list">
      <h2 class="section-title">Câu hỏi thường gặp khi đặt xe</h2>
      <details open><summary>Đặt cọc, thuê xe như thế nào và nếu lịch trình thay đổi đột ngột ra sao?</summary><p>Khách hàng liên hệ hotline hoặc Zalo để được tư vấn xe, lịch trình và chi phí. Đội ngũ Nam Thắng sẽ hỗ trợ điều chỉnh khi lịch trình phát sinh thay đổi.</p></details>
      <details><summary>Giá thuê xe đã bao gồm tất cả các chi phí như xăng dầu, phí cầu đường, cao tốc và tiền tip cho tài xế chưa?</summary><p>Chi phí sẽ được tư vấn rõ theo từng lịch trình. Các khoản bao gồm hoặc phát sinh như xăng dầu, phí cầu đường, cao tốc và tip tài xế sẽ được xác nhận trước khi đặt xe.</p></details>
      <details><summary>Xe Nam Thắng là dòng xe đời nào, có đầy đủ tiện nghi không?</summary><p>Nam Thắng sử dụng các dòng xe đời mới, sạch sẽ, ghế ngồi thoải mái, máy lạnh và các tiện ích phù hợp cho hành trình gia đình, nhóm bạn hoặc đoàn du lịch.</p></details>
      <details><summary>Tài xế có rành đường không và phục vụ đoàn như thế nào?</summary><p>Tài xế có kinh nghiệm đường dài, hỗ trợ lịch trình linh hoạt, lái xe an toàn và đồng hành cùng đoàn trong suốt chuyến đi.</p></details>
    </div>
  </section>
</main>

<footer class="footer">
  <div class="container footer-inner">
    <div class="footer-main">
      <div class="footer-logo-wrap">
        <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="Logo Nam Thắng"><?php else: ?><span>NT</span><?php endif; ?>
      </div>
      <h3 class="footer-brand-name"><?= e($config['footerName'] ?? 'NAM THẮNG TRAVEL BUS') ?></h3>
      <p class="footer-tagline">Chuyên nghiệp trên từng hành trình</p>
    </div>

    <div class="footer-company">
      <h4>THÔNG TIN CÔNG TY</h4>
      <p><strong>Trụ sở:</strong> Đường số 3 (KVBX tỉnh Kiên Giang) ấp Sua Đũa, xã Bình An, tỉnh An Giang</p>
    </div>

    <div class="footer-contact">
      <h4>LIÊN HỆ</h4>
      <p>Hotline: 0923 098 098</p>
      <p>Hotline: 0918 722 944 ( Mr. Sĩ)</p>
    </div>

    <div class="footer-social">
      <h4>KÊNH LIÊN KẾT</h4>
      <div class="footer-social-list">
        <a href="https://www.facebook.com/namthang.travelbus" target="_blank" rel="noopener noreferrer" class="footer-social-item" aria-label="Facebook">
          <span class="footer-social-icon facebook-icon"><svg viewBox="0 0 24 24"><path d="M22 12.06C22 6.48 17.52 2 11.94 2S2 6.48 2 12.06c0 5.02 3.66 9.19 8.44 9.94v-7.03H7.9v-2.91h2.54V9.84c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.23.19 2.23.19v2.45h-1.26c-1.24 0-1.63.77-1.63 1.56v1.9h2.77l-.44 2.91h-2.33V22c4.79-.75 8.45-4.92 8.45-9.94z"/></svg></span>
        </a>
        <a href="https://www.tiktok.com/@namthangtravelbus" target="_blank" rel="noopener noreferrer" class="footer-social-item" aria-label="TikTok">
          <span class="footer-social-icon tiktok-icon"><svg viewBox="0 0 24 24"><path d="M16.75 2c.35 2.72 1.87 4.34 4.55 4.51v3.06c-1.55.15-2.91-.36-4.46-1.31v5.72c0 7.27-7.92 9.54-11.1 4.33-2.04-3.35-.79-9.22 5.76-9.45v3.23c-.51.08-1.06.21-1.56.39-1.49.5-2.34 1.45-2.1 3.12.46 3.2 6.33 4.15 5.84-2.1V2h3.07z"/></svg></span>
        </a>
        <a href="https://namthanggroup.vn/" target="_blank" rel="noopener noreferrer" class="footer-social-item" aria-label="Website">
          <span class="footer-social-icon website-icon"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm6.93 6h-3.02a15.7 15.7 0 0 0-1.33-3.03A8.04 8.04 0 0 1 18.93 8zM12 4.04c.83 1.2 1.48 2.52 1.86 3.96h-3.72A13.2 13.2 0 0 1 12 4.04zM4.26 14a8.4 8.4 0 0 1 0-4h3.41a16.7 16.7 0 0 0 0 4H4.26zm.81 2h3.02c.34 1.08.79 2.1 1.33 3.03A8.04 8.04 0 0 1 5.07 16zm3.02-8H5.07a8.04 8.04 0 0 1 4.35-3.03A15.7 15.7 0 0 0 8.09 8zM12 19.96A13.2 13.2 0 0 1 10.14 16h3.72A13.2 13.2 0 0 1 12 19.96zM14.29 14H9.71a14.7 14.7 0 0 1 0-4h4.58a14.7 14.7 0 0 1 0 4zm.29 5.03c.54-.93.99-1.95 1.33-3.03h3.02a8.04 8.04 0 0 1-4.35 3.03zM16.33 14a16.7 16.7 0 0 0 0-4h3.41a8.4 8.4 0 0 1 0 4h-3.41z"/></svg></span>
        </a>
      </div>
    </div>
  </div>
</footer>

<div class="floating-contact" aria-label="Liên hệ nhanh">
  <a
    class="float-btn zalo"
    href="<?= e($config['zaloLink'] ?? '#') ?>"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Zalo"
  >
    <img
      class="float-icon-img"
      src="https://upload.wikimedia.org/wikipedia/commons/9/91/Icon_of_Zalo.svg"
      alt="Zalo"
      loading="lazy"
    >
  </a>

  <a
    class="float-btn messenger"
    href="<?= e($config['messengerLink'] ?? '#') ?>"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Messenger"
  >
    <img
      class="float-icon-img"
      src="https://upload.wikimedia.org/wikipedia/commons/b/be/Facebook_Messenger_logo_2020.svg"
      alt="Messenger"
      loading="lazy"
    >
  </a>

  <a
    class="float-btn hotline"
    href="<?= e($config['hotlineHref'] ?? 'tel:0923098098') ?>"
    aria-label="Hotline"
  >
    <span class="float-phone-icon" aria-hidden="true">📞</span>
  </a>
</div>

<div id="toast" class="toast"></div>

<div id="promoModal" class="modal-overlay promo-overlay">
  <div class="modal promo-modal">
    <button id="closePromoBtn" class="promo-close-btn" type="button" aria-label="Đóng popup">×</button>
    <div class="promo-head">
      <h3 class="promo-title"><span data-title="BẬT MOOD HÈ">BẬT MOOD HÈ</span><span data-title="NHẬN MAGIC TICKET NGAY!">NHẬN MAGIC TICKET NGAY!</span></h3>
    </div>
    <div class="modal-body">
      <?php $promo = asset_url($assets['popupPromoImageUrl'] ?? ''); ?>
      <div class="promo-image-wrap">
        <?php if ($promo): ?><img class="promo-image" src="<?= e($promo) ?>" alt="Magic Ticket Nam Thắng Travel Bus"><?php else: ?><div class="promo-image-placeholder">Magic Ticket Nam Thắng Travel Bus</div><?php endif; ?>
      </div>
      <div class="promo-voucher-grid">
        <div class="promo-box"><div class="voucher-label">NHẬN VOUCHER</div><strong>600K</strong><span>Chia sẻ <b>Video Review</b> chuyến đi</span></div>
        <div class="promo-box"><div class="voucher-label">NHẬN VOUCHER</div><strong>300K</strong><span>Chia sẻ <b>Ảnh Check-in</b> chuyến đi</span></div>
      </div>
      <ul class="promo-footer-note"><li>Số lượng có hạn</li><li>Mỗi số điện thoại chỉ được tham gia 1 lần</li></ul>
      <div class="promo-actions"><button id="joinNowBtn" class="btn btn-primary promo-join-btn" type="button">THAM GIA NGAY</button><button id="viewRulesBtn" class="btn btn-secondary promo-rules-btn" type="button">THỂ LỆ</button></div>
    </div>
  </div>
</div>

<div id="appPopup" class="modal-overlay">
  <div class="modal app-modal">
    <button class="promo-close-btn" type="button" data-close-app aria-label="Đóng popup">×</button>
    <div class="promo-head"><h3 class="app-title">Tải ứng dụng Taxi Nam Thắng</h3></div>
    <div class="modal-body">
      <p id="appPopupText">Tải ứng dụng để nhận ưu đãi và sử dụng dịch vụ thuận tiện hơn.</p>
      <div class="promo-actions"><a id="appPopupDownloadBtn" class="btn btn-primary" href="#" target="_blank" rel="noopener noreferrer">TẢI ỨNG DỤNG</a><button class="btn btn-secondary" type="button" data-close-app>Đóng</button></div>
    </div>
  </div>
</div>

<script src="assets/app.js?v=<?= rawurlencode(APP_VERSION) ?>"></script>
</body>
</html>
