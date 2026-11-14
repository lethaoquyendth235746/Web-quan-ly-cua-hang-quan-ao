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

        <!-- Chọn kích cỡ từ bt_bien_the_sp -->
        <div>
          <p class="text-sm font-medium">Kích cỡ (Biến thể thực tế trong kho)</p>
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
            Mô tả chi tiết sản phẩm (Từ CSDL)
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
            Danh sách toàn bộ biến thể SKU của sản phẩm này (<?= count($variants) ?> SKU)
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
    <div class="grid gap-10 lg:grid-cols-3">
      <div class="lg:col-span-1">
        <h2 id="reviewTitle" class="text-xl font-semibold">Đánh giá thực tế từ CSDL</h2>
        <?php if ($reviewCount > 0): ?>
          <p class="mt-2 text-sm text-amber-700 tabular-nums">
            ★★★★★ <span class="text-muted"><?= $avgRating ?> trên 5 &middot; <?= $reviewCount ?> đánh giá trong <code>ctdh_chi_tiet_dh</code></span>
          </p>
        <?php else: ?>
          <p class="mt-2 text-sm text-muted">Chưa có lượt đánh giá nào cho sản phẩm này trong CSDL.</p>
        <?php endif; ?>

        <div class="mt-6 rounded-xl bg-gold p-5 text-sm leading-relaxed">
          <p class="font-medium text-charcoal">Đánh giá sản phẩm đã mua để nhận ngay +10 điểm thưởng vào Thẻ thành viên.</p>
          <button type="button" data-dev-feature="Gửi đánh giá sao & cộng +10 điểm thưởng tự động"
            class="tap-feedback mt-4 rounded-full bg-charcoal px-4 py-2 text-xs font-medium text-white hover:bg-accent">
            Viết đánh giá nhận +10 điểm (Đang phát triển)
          </button>
        </div>
      </div>

      <div class="lg:col-span-2">
        <?php if (empty($reviews)): ?>
          <div class="rounded-xl border border-dashed border-stone-300 bg-white p-8 text-center">
            <p class="text-base font-medium text-charcoal">Sản phẩm này hiện chưa có dữ liệu đánh giá trong CSDL</p>
            <p class="mt-1 text-sm text-muted">Chức năng gửi đánh giá mới từ đơn hàng hoàn thành hiện <strong>đang phát triển</strong>.</p>
          </div>
        <?php else: ?>
          <ul class="space-y-8">
            <?php foreach ($reviews as $rev): ?>
              <li class="border-b border-stone-200 pb-8 last:border-b-0">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div>
                    <p class="text-sm font-semibold text-charcoal"><?= htmlspecialchars($rev['ho_ten']) ?></p>
                    <p class="text-xs text-muted"><?= htmlspecialchars($rev['ten_hang'] ?? 'Thành viên') ?></p>
                  </div>
                  <?php if ((int)$rev['da_cong_diem_dg'] === 1): ?>
                    <span class="rounded-full bg-olive/10 px-3 py-1 text-xs font-medium text-olive">Đã cộng +10 điểm thưởng</span>
                  <?php else: ?>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800">Chờ cộng điểm đánh giá (Đang phát triển)</span>
                  <?php endif; ?>
                </div>
                <p class="mt-2 text-sm text-amber-600">
                  <?= str_repeat('★', (int)$rev['so_sao_danh_gia']) . str_repeat('☆', max(0, 5 - (int)$rev['so_sao_danh_gia'])) ?>
                </p>
                <p class="mt-1 text-xs text-muted">
                  Phân loại mua: Màu <?= htmlspecialchars($rev['mau_sac']) ?>, Size <?= htmlspecialchars($rev['kich_co']) ?> &middot; Ngày đặt: <?= formatDateVN($rev['ngay_dat']) ?>
                </p>
                <p class="mt-3 text-sm leading-relaxed text-charcoal"><?= htmlspecialchars($rev['noi_dung_danh_gia']) ?></p>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </section>
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
            window.showStoreToast?.(res?.message || 'Có lỗi khi thêm vào giỏ hàng');
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
  })();
</script>

