<?php
/**
 * TẦNG CỬA HÀNG: Trang Chi tiết Sản phẩm (store/pages/product_detail.php)
 * Đổ dữ liệu thực tế từ sp_san_pham, bt_bien_the_sp (20 SKU), ha_hinh_anh_sp và đánh giá ctdh_chi_tiet_dh
 */
if (!$productDetail) {
    echo '<div class="py-20 text-center">'
       . '<h1 class="text-2xl font-semibold text-charcoal">Không tìm thấy sản phẩm trong CSDL</h1>'
       . '<p class="mt-2 text-sm text-muted">Sản phẩm bạn yêu cầu không tồn tại hoặc đang tạm ẩn.</p>'
       . '<a href="index.php" class="mt-6 inline-block rounded-full bg-charcoal px-6 py-3 text-sm font-medium text-white">Quay về trang chủ</a>'
       . '</div>';
    return;
}

$p = $productDetail;
$variants = $p['variants'] ?? [];
$images = $p['images'] ?? [];
$reviews = $p['reviews'] ?? [];
$firstVariant = $variants[0] ?? null;

$tierMultiplier = (float) ($loyaltyProfile['he_so_tich_diem'] ?? 1.0);
$tierName = $loyaltyProfile['ten_hang'] ?? 'Hạng Đồng';
$pointsExpected = (int) floor(((float) ($firstVariant['gia_ban_rieng'] ?? $p['gia_khuyen_mai']) / POINTS_PER_VND) * $tierMultiplier);

// Tự động tăng lượt xem sản phẩm hợp lệ
if (isset($productService) && isset($p['ma_sp'])) {
    $productService->incrementViewCount((int)$p['ma_sp']);
}

// Kiểm tra xem khách đã mua sản phẩm này trong đơn HOAN_THANH và chưa đánh giá (da_cong_diem_dg = 0)
$canReviewAndEarnPoints = false;
$currentUserId = getCurrentUserId();
if ($currentUserId > 0 && isset($productService) && isset($p['ma_sp'])) {
    $canReviewAndEarnPoints = $productService->canCustomerReviewProduct($currentUserId, (int)$p['ma_sp']);
}

// Tính trung bình sao đánh giá thực tế
$reviewCount = count($reviews);
$avgRating = 0;
if ($reviewCount > 0) {
    $sumStars = array_sum(array_column($reviews, 'so_sao_danh_gia'));
    $avgRating = round($sumStars / $reviewCount, 1);
}
?>
<div id="productView" class="fade-up">
  <nav aria-label="Đường dẫn" class="text-xs text-muted">
    <ol class="flex flex-wrap items-center gap-2">
      <li><a href="index.php" class="transition hover:text-charcoal">Trang chủ</a></li>
      <li aria-hidden="true">/</li>
      <li>
        <a href="index.php?page=home&gender=<?= htmlspecialchars($p['gender_group']) ?>#cua-hang" class="transition hover:text-charcoal">
          <?= htmlspecialchars($p['ten_dm_cha'] ?? $p['ten_dm']) ?>
        </a>
      </li>
      <li aria-hidden="true">/</li>
      <li class="text-charcoal font-medium" aria-current="page"><?= htmlspecialchars($p['ten_sp']) ?></li>
    </ol>
  </nav>

  <section class="mt-6 grid gap-12 lg:grid-cols-2 lg:gap-16">
    <!-- Bộ sưu tập ảnh theo màu từ bảng ha_hinh_anh_sp -->
    <div class="grid gap-4 md:grid-cols-[auto_1fr] lg:grid-cols-[88px_1fr]">
      <div id="thumbList" class="order-2 flex gap-3 overflow-x-auto md:order-1 md:flex-col">
        <?php foreach ($images as $idx => $img): ?>
          <button type="button"
            data-thumb-url="<?= htmlspecialchars($img['resolved_url']) ?>"
            data-thumb-color="<?= htmlspecialchars($img['mau_sac_lien_ket'] ?? '') ?>"
            class="tap-feedback h-20 w-16 shrink-0 overflow-hidden rounded-lg border-2 transition md:w-full <?= $idx === 0 ? 'border-charcoal' : 'border-transparent hover:border-stone-300' ?>">
            <img src="<?= htmlspecialchars($img['resolved_url']) ?>" alt="<?= htmlspecialchars($p['ten_sp']) ?>" class="h-full w-full object-cover" />
          </button>
        <?php endforeach; ?>
      </div>
      <div class="order-1 overflow-hidden rounded-2xl bg-stone-100 shadow-sm md:order-2">
        <img id="mainImage" src="<?= htmlspecialchars($images[0]['resolved_url'] ?? $p['anh_chinh_url']) ?>"
          alt="<?= htmlspecialchars($p['ten_sp']) ?>" class="aspect-[3/4] w-full object-cover transition duration-500" />
      </div>
    </div>

    <!-- Thông tin biến thể SKU & Đặt mua -->
    <div class="lg:pt-2">
      <div class="inline-flex items-center gap-2 rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-muted">
        <span>Danh mục: <?= htmlspecialchars($p['ten_dm']) ?></span>
        <span>&bull;</span>
        <span class="tabular-nums"><?= number_format((int)$p['luot_xem'], 0, ',', '.') ?> lượt xem</span>
      </div>

      <h1 class="mt-3 text-2xl font-semibold leading-tight sm:text-3xl"><?= htmlspecialchars($p['ten_sp']) ?></h1>

      <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted">
        <span>Mã SKU: <strong id="skuText" class="font-mono text-charcoal"><?= htmlspecialchars($firstVariant['ma_sku'] ?? 'N/A') ?></strong></span>
        <span id="stockText" class="tabular-nums inline-flex items-center gap-2 text-olive font-medium">
          <span class="h-2 w-2 rounded-full bg-olive"></span>
          Còn <?= (int)($firstVariant['so_luong_ton'] ?? 0) ?> sản phẩm trong kho
        </span>
      </div>

      <div class="mt-6 flex items-baseline gap-3 tabular-nums">
        <span id="priceText" class="text-2xl font-semibold text-charcoal"><?= formatVND($firstVariant['gia_ban_rieng'] ?? $p['gia_khuyen_mai']) ?></span>
        <?php if ((float)$p['gia_niem_yet'] > (float)$p['gia_khuyen_mai']): ?>
          <span id="originalPriceText" class="text-sm text-muted line-through"><?= formatVND($p['gia_niem_yet']) ?></span>
        <?php endif; ?>
      </div>

      <div class="mt-6 flex gap-3 rounded-xl bg-gold p-4 text-sm leading-relaxed text-charcoal">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-accent" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 2l1.8 6.2L20 10l-6.2 1.8L12 18l-1.8-6.2L4 10l6.2-1.8L12 2Z" />
        </svg>
        <p>
          Mua sản phẩm này bạn nhận được <strong id="pointsText" class="tabular-nums font-semibold">+<?= $pointsExpected ?> điểm tích lũy</strong>
          (<?= htmlspecialchars($tierName) ?> nhân hệ số <span class="tabular-nums font-medium">x<?= number_format($tierMultiplier, 1) ?></span>)
        </p>
      </div>

      <div class="mt-8 space-y-6">
        <!-- Chọn màu sắc từ bt_bien_the_sp -->
        <div>
          <p class="text-sm font-medium">Màu sắc: <span id="colorLabel" class="font-normal text-muted"><?= htmlspecialchars($firstVariant['mau_sac'] ?? '') ?></span></p>
          <div id="colorOptions" class="mt-3 flex flex-wrap gap-3">
            <?php foreach ($p['colors'] as $cIdx => $colorName): ?>
              <button type="button" data-color="<?= htmlspecialchars($colorName) ?>"
                class="tap-feedback flex items-center gap-2 rounded-full border-2 py-1.5 pl-2 pr-4 text-sm transition <?= $cIdx === 0 ? 'border-charcoal font-medium' : 'border-stone-200 hover:border-stone-400' ?>">
                <span class="h-5 w-5 rounded-full border border-stone-300" style="background: <?= htmlspecialchars(getColorHex($colorName)) ?>"></span>
                <span><?= htmlspecialchars($colorName) ?></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Chọn kích cỡ -->
        <div>
          <p class="text-sm font-medium">Kích cỡ</p>
          <div id="sizeOptions" class="mt-3 flex flex-wrap gap-3">
            <?php foreach ($p['sizes'] as $sIdx => $sizeName): ?>
              <button type="button" data-size="<?= htmlspecialchars($sizeName) ?>"
                class="tap-feedback min-w-[3.5rem] rounded-full border-2 px-4 py-2.5 text-sm font-medium transition <?= $sIdx === 0 ? 'border-charcoal bg-charcoal text-white' : 'border-stone-200 hover:border-stone-400' ?>">
                <?= htmlspecialchars($sizeName) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Số lượng -->
        <div>
          <p class="text-sm font-medium">Số lượng</p>
          <div class="mt-3 inline-flex items-center rounded-full border border-stone-200 bg-white">
            <button type="button" id="qtyMinus" aria-label="Giảm số lượng" class="tap-feedback h-10 w-10 rounded-full text-lg hover:bg-stone-100">-</button>
            <span id="qtyValue" class="tabular-nums w-10 text-center text-sm font-medium" aria-live="polite">1</span>
            <button type="button" id="qtyPlus" aria-label="Tăng số lượng" class="tap-feedback h-10 w-10 rounded-full text-lg hover:bg-stone-100">+</button>
          </div>
        </div>
      </div>

      <div class="mt-8 grid gap-3 sm:grid-cols-2">
        <button type="button" id="addToCartBtn"
          class="tap-feedback rounded-full bg-charcoal px-6 py-4 text-sm font-medium text-white hover:bg-accent disabled:cursor-not-allowed disabled:bg-stone-300">
          Thêm vào giỏ hàng
        </button>
        <a href="index.php?page=checkout"
          class="tap-feedback flex items-center justify-center rounded-full border border-charcoal px-6 py-4 text-sm font-medium text-charcoal hover:border-accent hover:text-accent">
          Xem trang Thanh toán
        </a>
      </div>

      <!-- Accordion thông tin sản phẩm -->
      <div class="mt-10 divide-y divide-stone-200 border-y border-stone-200 text-sm">
        <details class="group py-4" open>
          <summary class="flex cursor-pointer list-none items-center justify-between font-medium">
            Mô tả chi tiết sản phẩm
            <span class="accordion-icon text-xl leading-none text-muted">+</span>
          </summary>
          <p class="mt-4 leading-relaxed text-muted"><?= nl2br(htmlspecialchars($p['mo_ta_chi_tiet'] ?? 'Đang cập nhật')) ?></p>
        </details>
        <details class="group py-4" open>
          <summary class="flex cursor-pointer list-none items-center justify-between font-medium">
            Chất liệu &amp; Bảo quản
            <span class="accordion-icon text-xl leading-none text-muted">+</span>
          </summary>
          <p class="mt-4 leading-relaxed text-muted">Chất liệu: <strong><?= htmlspecialchars($p['chat_lieu'] ?? 'Đang cập nhật') ?></strong>. Giặt nhẹ ở nhiệt độ thường, không dùng chất tẩy mạnh.</p>
        </details>
        <details class="group py-4">
          <summary class="flex cursor-pointer list-none items-center justify-between font-medium">
            Danh sách phân loại sản phẩm (<?= count($variants) ?> phân loại)
            <span class="accordion-icon text-xl leading-none text-muted">+</span>
          </summary>
          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-xs">
              <thead class="border-b border-stone-200 text-muted">
                <tr>
                  <th class="py-2">Mã SKU</th>
                  <th class="py-2">Màu sắc</th>
                  <th class="py-2">Kích cỡ</th>
                  <th class="py-2 text-right">Giá bán</th>
                  <th class="py-2 text-right">Tồn kho</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-stone-100 tabular-nums">
                <?php foreach ($variants as $v): ?>
                  <tr>
                    <td class="py-2 font-mono font-medium text-charcoal"><?= htmlspecialchars($v['ma_sku']) ?></td>
                    <td class="py-2"><?= htmlspecialchars($v['mau_sac']) ?></td>
                    <td class="py-2"><?= htmlspecialchars($v['kich_co']) ?></td>
                    <td class="py-2 text-right"><?= formatVND($v['gia_ban_rieng']) ?></td>
                    <td class="py-2 text-right font-semibold text-olive"><?= (int)$v['so_luong_ton'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </details>
      </div>
    </div>
  </section>

  <!-- Khu vực Đánh giá sản phẩm thực tế từ bảng ctdh_chi_tiet_dh -->
  <section class="mt-20 border-t border-stone-200 pt-12" aria-labelledby="reviewTitle">
    <?php if ($canReviewAndEarnPoints): ?>
      <div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm">
        <div class="flex items-center gap-3">
          <span class="text-2xl" aria-hidden="true">🎁</span>
          <div>
            <p class="text-sm font-semibold text-amber-900">
              Bạn đã mua sản phẩm này! Hãy vào Trung tâm Thành viên để đánh giá và nhận ngay +10 điểm thưởng.
            </p>
            <p class="mt-0.5 text-xs text-amber-700">
              Mỗi đánh giá hợp lệ sẽ được cộng trực tiếp vào Thẻ tích điểm và hỗ trợ nâng hạng thành viên.
            </p>
          </div>
        </div>
        <a href="index.php?page=loyalty" class="tap-feedback whitespace-nowrap rounded-full bg-charcoal px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-accent transition">
          Vào đổi điểm & Đánh giá &rarr;
        </a>
      </div>
    <?php endif; ?>
    <div class="grid gap-10 lg:grid-cols-3">
      <div class="lg:col-span-1">
        <h2 id="reviewTitle" class="text-xl font-semibold">Đánh giá sản phẩm</h2>
        <div id="reviewRatingSummary">
          <?php if ($reviewCount > 0): ?>
            <p class="mt-2 text-sm text-amber-700 tabular-nums">
              ★★★★★ <span class="text-muted"><span id="avgRatingText"><?= $avgRating ?></span> trên 5 &middot; <span id="reviewCountText"><?= $reviewCount ?></span> đánh giá thực tế</span>
            </p>
          <?php else: ?>
            <p class="mt-2 text-sm text-muted">Chưa có lượt đánh giá nào cho sản phẩm này trong CSDL.</p>
          <?php endif; ?>
        </div>

        <div class="mt-6 rounded-xl bg-gold p-5 text-sm leading-relaxed">
          <p class="font-medium text-charcoal">Đánh giá sản phẩm đã mua để nhận ngay +10 điểm thưởng vào Thẻ thành viên.</p>
          <button type="button" id="btnOpenReviewModal"
            class="tap-feedback mt-4 rounded-full bg-charcoal px-4 py-2 text-xs font-medium text-white hover:bg-accent transition shadow-sm">
            Viết đánh giá nhận +10 điểm
          </button>
        </div>
      </div>

      <div class="lg:col-span-2">
        <div id="reviewEmptyBox" class="<?= !empty($reviews) ? 'hidden' : '' ?> rounded-xl border border-dashed border-stone-300 bg-white p-8 text-center">
          <p class="text-base font-medium text-charcoal">Sản phẩm này hiện chưa có lượt đánh giá nào</p>
          <p class="mt-1 text-sm text-muted">Hãy là người đầu tiên trải nghiệm và để lại nhận xét nhận +10 điểm thưởng!</p>
        </div>

        <ul id="reviewListContainer" class="space-y-8 <?= empty($reviews) ? 'hidden' : '' ?>">
          <?php foreach ($reviews as $rev): ?>
            <li class="border-b border-stone-200 pb-8 last:border-b-0">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <p class="text-sm font-semibold text-charcoal"><?= htmlspecialchars($rev['ho_ten']) ?></p>
                  <p class="text-xs text-muted"><?= htmlspecialchars($rev['ten_hang'] ?? 'Thành viên') ?></p>
                </div>
                <?php if ((int)$rev['da_cong_diem_dg'] === 1): ?>
                  <span class="rounded-full bg-olive/10 px-3 py-1 text-xs font-medium text-olive">✓ Đã nhận +10 điểm thưởng</span>
                <?php endif; ?>
              </div>
              <p class="mt-2 text-sm text-amber-600">
                <?= str_repeat('★', (int)$rev['so_sao_danh_gia']) . str_repeat('☆', max(0, 5 - (int)$rev['so_sao_danh_gia'])) ?>
              </p>
              <p class="mt-1 text-xs text-muted">
                Phân loại mua: Màu <?= htmlspecialchars($rev['mau_sac'] ?: 'Mặc định') ?>, Size <?= htmlspecialchars($rev['kich_co'] ?: 'Tiêu chuẩn') ?> &middot; Ngày đặt: <?= formatDateVN($rev['ngay_dat']) ?>
              </p>
              <p class="mt-3 text-sm leading-relaxed text-charcoal"><?= htmlspecialchars($rev['noi_dung_danh_gia']) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </section>
</div>

<!-- Modal Viết Đánh Giá Sản Phẩm Nhận +10 Điểm -->
<div id="reviewModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm transition-opacity" onclick="closeReviewModal()"></div>
  <div class="flex min-h-full items-center justify-center p-4">
    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl transition-all">
      <div class="flex items-center justify-between border-b border-stone-200 px-6 py-4">
        <div>
          <h3 class="text-lg font-semibold text-charcoal">Đánh giá sản phẩm</h3>
          <p class="text-xs text-muted mt-0.5">Thưởng ngay <strong class="text-olive">+10 điểm tích lũy</strong> vào thẻ thành viên</p>
        </div>
        <button type="button" onclick="closeReviewModal()" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 hover:text-charcoal">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
        </button>
      </div>

      <form id="reviewForm" class="p-6 space-y-5">
        <input type="hidden" name="ma_sp" value="<?= (int)$p['ma_sp'] ?>" />
        <input type="hidden" id="reviewStarValue" name="so_sao" value="5" />

        <!-- Chọn số sao trực quan 1-5 -->
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-2">Đánh giá độ hài lòng</label>
          <div class="flex items-center gap-2" id="starPicker">
            <?php for ($s = 1; $s <= 5; $s++): ?>
              <button type="button" data-star="<?= $s ?>" class="star-btn text-2xl text-amber-500 hover:scale-125 transition-transform" title="<?= $s ?> sao">★</button>
            <?php endfor; ?>
            <span id="starRatingLabel" class="ml-2 text-xs font-semibold text-stone-600">5 sao (Rất hài lòng)</span>
          </div>
        </div>

        <!-- Nội dung nhận xét -->
        <div>
          <label for="reviewContent" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">Nội dung nhận xét <span class="text-rose-600">*</span></label>
          <textarea id="reviewContent" name="noi_dung" rows="4" required
            placeholder="Chia sẻ cảm nhận về chất liệu vải, form dáng, độ vừa vặn và đóng gói..."
            class="w-full rounded-xl border border-stone-300 p-3.5 text-sm text-charcoal placeholder:text-muted focus:border-charcoal focus:outline-none focus:ring-1 focus:ring-charcoal"></textarea>
          <p class="mt-1 text-xs text-muted">Đánh giá chân thực giúp các thành viên khác lựa chọn sản phẩm phù hợp hơn.</p>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-stone-200 pt-4">
          <button type="button" onclick="closeReviewModal()"
            class="tap-feedback rounded-full border border-stone-300 px-5 py-2 text-xs font-medium text-stone-600 hover:bg-stone-50">
            Hủy
          </button>
          <button type="submit" id="btnSubmitReview"
            class="tap-feedback inline-flex items-center gap-1.5 rounded-full bg-charcoal px-6 py-2.5 text-xs font-medium text-white hover:bg-accent transition shadow-sm">
            <span>Gửi đánh giá & Nhận +10 điểm</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  (() => {
    const variants = <?= json_encode($variants) ?>;
    const images = <?= json_encode($images) ?>;
    const tierMultiplier = <?= json_encode($tierMultiplier) ?>;

    let currentColor = variants[0]?.mau_sac || '';
    let currentSize = variants[0]?.kich_co || '';
    let currentQty = 1;

    const skuText = document.getElementById('skuText');
    const stockText = document.getElementById('stockText');
    const priceText = document.getElementById('priceText');
    const pointsText = document.getElementById('pointsText');
    const colorLabel = document.getElementById('colorLabel');
    const mainImage = document.getElementById('mainImage');
    const qtyValue = document.getElementById('qtyValue');

    function findVariant(color, size) {
      return variants.find((v) => v.mau_sac === color && v.kich_co === size)
          || variants.find((v) => v.mau_sac === color)
          || variants[0];
    }

    function updateVariantUI() {
      const matched = findVariant(currentColor, currentSize);
      if (!matched) return;
      currentColor = matched.mau_sac;
      currentSize = matched.kich_co;

      const stock = Number(matched.so_luong_ton || 0);
      currentQty = Math.min(Math.max(1, currentQty), Math.max(1, stock));

      if (skuText) skuText.textContent = matched.ma_sku;
      if (colorLabel) colorLabel.textContent = currentColor;
      if (qtyValue) qtyValue.textContent = String(currentQty);

      const price = Number(matched.gia_ban_rieng || 0);
      if (priceText) priceText.textContent = price.toLocaleString('vi-VN') + 'đ';

      const pts = Math.floor((price / 10000) * tierMultiplier);
      if (pointsText) pointsText.textContent = `+${pts} điểm tích lũy`;

      if (stockText) {
        stockText.innerHTML = stock > 0
          ? `<span class="h-2 w-2 rounded-full bg-olive"></span>Còn ${stock} sản phẩm trong kho`
          : `<span class="h-2 w-2 rounded-full bg-red-500"></span>Tạm hết hàng`;
      }

      const addToCartBtn = document.getElementById('addToCartBtn');
      if (addToCartBtn) {
        addToCartBtn.disabled = stock <= 0;
        if (stock <= 0) {
          addToCartBtn.textContent = 'Tạm hết hàng';
        } else {
          addToCartBtn.textContent = 'Thêm vào giỏ hàng';
        }
      }

      // Đổi ảnh theo màu nếu có trong ha_hinh_anh_sp
      const colorImg = images.find((img) => img.mau_sac_lien_ket === currentColor);
      if (colorImg && mainImage) {
        mainImage.src = colorImg.resolved_url;
      }

      document.querySelectorAll('#colorOptions [data-color]').forEach((btn) => {
        const active = btn.dataset.color === currentColor;
        btn.classList.toggle('border-charcoal', active);
        btn.classList.toggle('font-medium', active);
        btn.classList.toggle('border-stone-200', !active);
      });

      document.querySelectorAll('#sizeOptions [data-size]').forEach((btn) => {
        const s = btn.dataset.size;
        const active = s === currentSize;
        const hasCombination = variants.some((v) => v.mau_sac === currentColor && v.kich_co === s && Number(v.so_luong_ton) > 0);
        btn.disabled = !hasCombination;
        btn.classList.toggle('opacity-40', !hasCombination);
        btn.classList.toggle('line-through', !hasCombination);
        btn.classList.toggle('border-charcoal', active);
        btn.classList.toggle('bg-charcoal', active);
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('border-stone-200', !active);
      });
    }

    document.querySelectorAll('#colorOptions [data-color]').forEach((btn) => {
      btn.addEventListener('click', () => {
        currentColor = btn.dataset.color;
        updateVariantUI();
      });
    });

    document.querySelectorAll('#sizeOptions [data-size]').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (btn.disabled) return;
        currentSize = btn.dataset.size;
        updateVariantUI();
      });
    });

    document.querySelectorAll('#thumbList [data-thumb-url]').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (mainImage) mainImage.src = btn.dataset.thumbUrl;
        document.querySelectorAll('#thumbList [data-thumb-url]').forEach((b) => {
          b.classList.toggle('border-charcoal', b === btn);
          b.classList.toggle('border-transparent', b !== btn);
        });
      });
    });

    document.getElementById('qtyMinus')?.addEventListener('click', () => {
      currentQty = Math.max(1, currentQty - 1);
      if (qtyValue) qtyValue.textContent = String(currentQty);
    });

    document.getElementById('qtyPlus')?.addEventListener('click', () => {
      const matched = findVariant(currentColor, currentSize);
      const maxStock = Number(matched?.so_luong_ton || 1);
      currentQty = Math.min(maxStock, currentQty + 1);
      if (qtyValue) qtyValue.textContent = String(currentQty);
    });

    const addToCartBtn = document.getElementById('addToCartBtn');
    if (addToCartBtn) {
      addToCartBtn.addEventListener('click', async () => {
        const matched = findVariant(currentColor, currentSize);
        if (!matched) {
          window.showStoreToast?.('Vui lòng chọn màu sắc và kích cỡ hợp lệ');
          return;
        }

        const stock = Number(matched.so_luong_ton || 0);
        if (stock <= 0) {
          window.showStoreToast?.('Sản phẩm đã hết hàng trong kho');
          return;
        }

        addToCartBtn.disabled = true;
        const originalText = addToCartBtn.innerHTML;
        addToCartBtn.innerHTML = `
          <span class="inline-flex items-center gap-2">
            <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            Đang thêm...
          </span>
        `;

        try {
          const res = await window.cartAPI.add(matched.ma_bt, currentQty);
          if (res && res.success) {
            window.showStoreToast?.(res.message || 'Đã thêm sản phẩm vào giỏ hàng!');
            window.openCartDrawer?.();
          } else {
            if (res && res.require_login && window.openAuthModal) {
              window.openAuthModal('login');
            } else {
              window.showStoreToast?.(res?.message || 'Có lỗi khi thêm vào giỏ hàng');
            }
          }
        } catch (err) {
          console.error(err);
          window.showStoreToast?.('Không thể kết nối đến máy chủ');
        } finally {
          addToCartBtn.disabled = false;
          addToCartBtn.innerHTML = originalText;
        }
      });
    }

    updateVariantUI();

    // -------------------------------------------------------------------------
    // XỬ LÝ MODAL VIẾT ĐÁNH GIÁ SẢN PHẨM & CỘNG +10 ĐIỂM
    // -------------------------------------------------------------------------
    const reviewModal = document.getElementById('reviewModal');
    const btnOpenReviewModal = document.getElementById('btnOpenReviewModal');
    const reviewForm = document.getElementById('reviewForm');
    const starPicker = document.getElementById('starPicker');
    const starRatingLabel = document.getElementById('starRatingLabel');
    const reviewStarValue = document.getElementById('reviewStarValue');
    const btnSubmitReview = document.getElementById('btnSubmitReview');

    const starLabels = {
      1: '1 sao (Rất tệ)',
      2: '2 sao (Chưa hài lòng)',
      3: '3 sao (Bình thường)',
      4: '4 sao (Hài lòng)',
      5: '5 sao (Rất hài lòng)'
    };

    window.openReviewModal = function() {
      reviewModal?.classList.remove('hidden');
    };

    window.closeReviewModal = function() {
      reviewModal?.classList.add('hidden');
    };

    btnOpenReviewModal?.addEventListener('click', () => {
      window.openReviewModal();
    });

    // Bắt sự kiện chọn sao
    starPicker?.addEventListener('click', (e) => {
      const btn = e.target.closest('.star-btn');
      if (!btn) return;
      const rating = Number(btn.dataset.star || 5);
      reviewStarValue.value = rating;
      if (starRatingLabel) starRatingLabel.textContent = starLabels[rating] || `${rating} sao`;

      document.querySelectorAll('#starPicker .star-btn').forEach((b) => {
        const val = Number(b.dataset.star);
        if (val <= rating) {
          b.textContent = '★';
          b.className = 'star-btn text-2xl text-amber-500 hover:scale-125 transition-transform';
        } else {
          b.textContent = '☆';
          b.className = 'star-btn text-2xl text-stone-300 hover:scale-125 transition-transform';
        }
      });
    });

    // Submit form đánh giá
    reviewForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const content = document.getElementById('reviewContent')?.value.trim();
      if (!content) {
        window.showStoreToast?.('Vui lòng nhập nội dung đánh giá');
        return;
      }

      btnSubmitReview.disabled = true;
      const originalBtnText = btnSubmitReview.innerHTML;
      btnSubmitReview.innerHTML = `
        <svg class="h-4 w-4 animate-spin text-white inline-block mr-1" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        Đang gửi đánh giá...
      `;

      try {
        const res = await fetch('api/review_product.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            ma_sp: Number(<?= (int)$p['ma_sp'] ?>),
            so_sao: Number(reviewStarValue.value || 5),
            noi_dung: content
          })
        });

        const data = await res.json();

        if (data.require_login) {
          window.closeReviewModal();
          if (window.openAuthModal) {
            window.openAuthModal('login');
          } else {
            window.showStoreToast?.('Vui lòng đăng nhập để gửi đánh giá.');
          }
          return;
        }

        if (!data.success) {
          window.showStoreToast?.(data.message || 'Không thể gửi đánh giá.');
          return;
        }

        // Thành công: thông báo và cập nhật giao diện
        window.showStoreToast?.(data.message || 'Gửi đánh giá thành công! Nhận ngay +10 điểm.');
        window.closeReviewModal();
        reviewForm.reset();

        // 1. Cập nhật điểm trên Header Popover nếu có
        const tierBtn = document.getElementById('tierBtn');
        if (tierBtn && data.diem_kha_dung !== undefined) {
          const pointsSpan = tierBtn.querySelector('.tabular-nums');
          if (pointsSpan) pointsSpan.textContent = Number(data.diem_kha_dung).toLocaleString('vi-VN') + ' điểm';
        }

        // 2. Chèn review mới vào đầu danh sách
        const listContainer = document.getElementById('reviewListContainer');
        const emptyBox = document.getElementById('reviewEmptyBox');
        if (emptyBox) emptyBox.classList.add('hidden');
        if (listContainer) {
          listContainer.classList.remove('hidden');
          const rev = data.review;
          const starsHtml = '★'.repeat(rev.so_sao_danh_gia) + '☆'.repeat(Math.max(0, 5 - rev.so_sao_danh_gia));
          const newLi = document.createElement('li');
          newLi.className = 'border-b border-stone-200 pb-8 last:border-b-0 fade-up';
          newLi.innerHTML = `
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <p class="text-sm font-semibold text-charcoal">${rev.ho_ten}</p>
                <p class="text-xs text-muted">${rev.ten_hang || 'Thành viên'}</p>
              </div>
              <span class="rounded-full bg-olive/10 px-3 py-1 text-xs font-medium text-olive">✓ Đã nhận +10 điểm thưởng</span>
            </div>
            <p class="mt-2 text-sm text-amber-600">${starsHtml}</p>
            <p class="mt-1 text-xs text-muted">
              Phân loại mua: Màu ${rev.mau_sac || 'Mặc định'}, Size ${rev.kich_co || 'Tiêu chuẩn'} &middot; Vừa xong
            </p>
            <p class="mt-3 text-sm leading-relaxed text-charcoal">${rev.noi_dung_danh_gia}</p>
          `;
          listContainer.prepend(newLi);
        }

        // 3. Cập nhật đếm review
        const countSpan = document.getElementById('reviewCountText');
        if (countSpan) {
          const currentCount = parseInt(countSpan.textContent || '0') + 1;
          countSpan.textContent = String(currentCount);
        }
      } catch (err) {
        console.error('Review submit error:', err);
        window.showStoreToast?.('Lỗi kết nối khi gửi đánh giá');
      } finally {
        btnSubmitReview.disabled = false;
        btnSubmitReview.innerHTML = originalBtnText;
      }
    });
  })();
</script>

