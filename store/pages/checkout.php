<?php
/**
 * TẦNG CỬA HÀNG: Trang Thanh toán Đa tầng (store/pages/checkout.php)
 * Đổ dữ liệu từ dc_dia_chi_kh, gh_gio_hang, vkh_vi_voucher_kh, ttd_the_tich_diem, htv_hang_thanh_vien
 * Tích hợp Transaction Đặt hàng & Khóa kho (api/checkout.php) kèm VietQR động
 */
$hoTen = $loyaltyProfile['ho_ten'] ?? 'Khách hàng';
$tenHang = $loyaltyProfile['ten_hang'] ?? 'Hạng Đồng';
$phanTramGiamHang = (float) ($loyaltyProfile['phan_tram_giam'] ?? 0);
$heSoTichDiem = (float) ($loyaltyProfile['he_so_tich_diem'] ?? 1.0);
$diemKhaDung = (int) ($loyaltyProfile['diem_kha_dung'] ?? 0);
$maxPointsToUse = min(50, $diemKhaDung);
?>
<div id="checkoutView" class="fade-up">
  <div class="grid gap-10 lg:grid-cols-5">
    <div class="space-y-10 lg:col-span-3">
      <!-- Sổ địa chỉ nhận hàng từ bảng dc_dia_chi_kh -->
      <section aria-labelledby="addressTitle">
        <div class="flex items-center justify-between">
          <div>
            <h2 id="addressTitle" class="text-xl font-semibold">Địa chỉ nhận hàng (Bảng <code>dc_dia_chi_kh</code>)</h2>
            <p class="mt-1 text-xs text-muted">Sổ địa chỉ thực tế của khách hàng <strong><?= htmlspecialchars($hoTen) ?></strong></p>
          </div>
          <button type="button" data-dev-feature="Thêm địa chỉ nhận hàng mới vào bảng dc_dia_chi_kh"
            class="tap-feedback rounded-full border border-stone-300 bg-white px-4 py-2 text-xs font-medium text-charcoal hover:border-charcoal">
            + Thêm địa chỉ (Đang phát triển)
          </button>
        </div>

        <div class="mt-6 space-y-3" id="addressList">
          <?php if (empty($customerAddresses)): ?>
            <div class="rounded-xl border border-dashed border-stone-300 bg-white p-6 text-center text-sm text-muted">
              Khách hàng hiện chưa có địa chỉ trong CSDL.
            </div>
          <?php else: ?>
            <?php foreach ($customerAddresses as $addr): ?>
              <label class="flex cursor-pointer gap-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-stone-400 has-[:checked]:border-charcoal has-[:checked]:ring-1 has-[:checked]:ring-charcoal">
                <input type="radio" name="address" value="<?= (int)$addr['ma_dc'] ?>" <?= (int)$addr['mac_dinh'] === 1 ? 'checked' : '' ?> class="mt-1 h-4 w-4 accent-accent" />
                <span>
                  <span class="block text-sm font-medium text-charcoal">
                    <?= htmlspecialchars($addr['ten_nguoi_nhan']) ?>
                    <?php if ((int)$addr['mac_dinh'] === 1): ?>
                      <span class="ml-2 rounded-full bg-olive/10 px-2.5 py-0.5 text-xs font-medium text-olive">Mặc định</span>
                    <?php endif; ?>
                  </span>
                  <span class="mt-1 block text-sm text-muted">SĐT nhận: <?= htmlspecialchars($addr['sdt_nhan']) ?></span>
                  <span class="mt-1 block text-sm text-muted">
                    <?= htmlspecialchars($addr['dia_chi_cu_the']) ?>, <?= htmlspecialchars($addr['phuong_xa']) ?>, <?= htmlspecialchars($addr['quan_huyen']) ?>, <?= htmlspecialchars($addr['tinh_thanh']) ?>
                  </span>
                </span>
              </label>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <!-- Phương thức thanh toán -->
      <section aria-labelledby="paymentTitle">
        <h2 id="paymentTitle" class="text-xl font-semibold">Phương thức thanh toán</h2>
        <div class="mt-6 space-y-3">
          <label class="flex cursor-pointer gap-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-stone-400 has-[:checked]:border-charcoal has-[:checked]:ring-1 has-[:checked]:ring-charcoal">
            <input type="radio" name="payment" value="COD" checked class="mt-1 h-4 w-4 accent-accent" />
            <span>
              <span class="block text-sm font-medium">Thanh toán khi nhận hàng (COD)</span>
              <span class="mt-1 block text-sm text-muted">Kiểm tra sản phẩm trước khi thanh toán. Điểm thưởng cộng khi Admin duyệt hoàn thành đơn.</span>
            </span>
          </label>
          <label class="flex cursor-pointer gap-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-stone-400 has-[:checked]:border-charcoal has-[:checked]:ring-1 has-[:checked]:ring-charcoal">
            <input type="radio" name="payment" value="CHUYEN_KHOAN" class="mt-1 h-4 w-4 accent-accent" />
            <span>
              <span class="inline-flex items-center gap-2 text-sm font-medium">
                Chuyển khoản ngân hàng (VietQR Tự động)
                <span class="rounded-full bg-accent/10 px-2 py-0.5 text-[11px] font-semibold text-accent">Khuyên dùng</span>
              </span>
              <span class="mt-1 block text-sm text-muted">Hiển thị mã VietQR động tự động điền đúng số tiền và nội dung đơn hàng ngay sau khi xác nhận.</span>
            </span>
          </label>
        </div>
      </section>
    </div>

    <!-- Tóm tắt đơn hàng & Khuyến mãi đa tầng -->
    <aside class="lg:col-span-2">
      <div class="space-y-6 rounded-xl bg-white p-6 shadow-sm lg:sticky lg:top-24">
        <div>
          <h2 class="text-xl font-semibold">Tóm tắt đơn hàng</h2>
          <p class="mt-1 text-xs text-muted">Dữ liệu giỏ hàng từ bảng <code>gh_gio_hang</code></p>
        </div>

        <?php if (empty($cartItems)): ?>
          <div id="checkoutEmptyNotice" class="rounded-xl bg-stone-100 p-6 text-center text-sm text-muted">
            Giỏ hàng của <strong><?= htmlspecialchars($hoTen) ?></strong> đang trống. Hãy chọn tài khoản khách hàng khác (VD: Neàng Ra, Hoàng Nam, Bảo Trân) hoặc thêm sản phẩm.
          </div>
        <?php else: ?>
          <ul id="checkoutItemList" class="divide-y divide-stone-200 text-sm">
            <?php foreach ($cartItems as $item): ?>
              <li class="flex justify-between gap-4 py-3">
                <div>
                  <p class="font-medium text-charcoal"><?= htmlspecialchars($item['ten_sp']) ?></p>
                  <p class="text-xs text-muted">
                    Màu: <?= htmlspecialchars($item['mau_sac']) ?>, Size: <?= htmlspecialchars($item['kich_co']) ?> &times; <?= (int)$item['so_luong_mua'] ?>
                  </p>
                </div>
                <span class="tabular-nums whitespace-nowrap font-medium text-charcoal"><?= formatVND($item['thanh_tien']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <!-- Chọn Voucher từ Ví vkh_vi_voucher_kh & Tiêu điểm khả dụng -->
        <div class="space-y-4 rounded-xl bg-gold p-5">
          <label class="block">
            <span class="text-sm font-medium text-charcoal">Voucher khả dụng trong Ví của bạn (<code>vkh_vi_voucher_kh</code>)</span>
            <select id="voucherSelect" class="mt-2 w-full rounded-full border border-stone-200 bg-white px-4 py-2.5 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
              <option value="0" data-type="TIEN" data-value="0" data-min="0">-- Không áp dụng Voucher --</option>
              <?php foreach ($availableVouchers as $vch): ?>
                <option value="<?= (int)$vch['ma_pgg'] ?>"
                  data-type="<?= htmlspecialchars($vch['loai_giam']) ?>"
                  data-value="<?= (float)$vch['gia_tri_giam'] ?>"
                  data-min="<?= (float)$vch['don_toi_thieu'] ?>">
                  <?= htmlspecialchars($vch['ma_code']) ?> — <?= htmlspecialchars($vch['ten_pgg']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($availableVouchers)): ?>
              <span class="mt-1.5 block text-xs text-muted">Ví cá nhân chưa có Voucher khả dụng (Hãy sang mục Trung tâm Thành viên để đổi voucher).</span>
            <?php endif; ?>
          </label>

          <label class="flex cursor-pointer items-start justify-between gap-4 border-t border-stone-200/70 pt-3">
            <span class="text-sm leading-relaxed text-charcoal">
              Sử dụng <strong class="tabular-nums"><?= $maxPointsToUse ?> điểm</strong> khả dụng để trừ trực tiếp <strong class="tabular-nums"><?= formatVND($maxPointsToUse * 1000) ?></strong>
              <span class="block text-xs text-muted">(Số dư hiện có: <?= $diemKhaDung ?> điểm)</span>
            </span>
            <input id="pointsToggle" type="checkbox"
              data-points="<?= $maxPointsToUse ?>"
              <?= $maxPointsToUse > 0 ? '' : 'disabled' ?>
              class="mt-0.5 h-5 w-5 shrink-0 accent-olive" />
          </label>
        </div>

        <!-- Chi tiết công thức tính tiền đa tầng -->
        <dl class="space-y-3 text-sm tabular-nums">
          <div class="flex justify-between gap-4">
            <dt class="text-muted">Tổng tiền hàng gốc</dt>
            <dd id="ckBase" class="font-medium text-charcoal"><?= formatVND($checkoutSummary['subtotal']) ?></dd>
          </div>
          <div class="flex justify-between gap-4">
            <dt class="text-muted">Chiết khấu <?= htmlspecialchars($tenHang) ?> (<?= number_format($phanTramGiamHang, 0) ?>%)</dt>
            <dd id="ckTier" class="font-medium text-olive">-<?= formatVND($checkoutSummary['tier_discount_amount']) ?></dd>
          </div>
          <div class="flex justify-between gap-4">
            <dt class="text-muted">Giảm từ Voucher ví cá nhân</dt>
            <dd id="ckVoucher" class="font-medium text-accent">-0đ</dd>
          </div>
          <div class="flex justify-between gap-4">
            <dt class="text-muted">Giảm từ điểm tiêu dùng</dt>
            <dd id="ckPoints" class="font-medium text-accent">-0đ</dd>
          </div>
        </dl>

        <div class="flex items-baseline justify-between gap-4 border-t border-stone-200 pt-5 tabular-nums">
          <span class="text-sm font-semibold uppercase tracking-wider">Tổng thanh toán</span>
          <span id="ckTotal" class="text-2xl font-semibold text-charcoal"><?= formatVND($checkoutSummary['final_total']) ?></span>
        </div>

        <p class="rounded-xl bg-olive/10 p-4 text-sm leading-relaxed text-olive tabular-nums">
          Đơn hàng này sẽ cộng thêm <strong id="ckExpectedPoints" class="font-semibold">+<?= (int)$checkoutSummary['expected_points'] ?> điểm thưởng</strong>
          (<?= htmlspecialchars($tenHang) ?> nhân hệ số x<?= number_format($heSoTichDiem, 1) ?>) sau khi Admin duyệt hoàn thành.
        </p>

        <button type="button" id="placeOrderBtn"
          <?= empty($cartItems) ? 'disabled' : '' ?>
          class="tap-feedback w-full rounded-full bg-charcoal px-6 py-4 text-sm font-medium text-white hover:bg-accent disabled:opacity-50 disabled:cursor-not-allowed">
          Xác nhận Đặt hàng
        </button>
      </div>
    </aside>
  </div>
</div>

<!-- ================= MODAL VIETQR THANH TOÁN TỰ ĐỘNG ================= -->
<div id="vietQrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-charcoal/50 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300" role="dialog" aria-modal="true" aria-labelledby="qrTitle">
  <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 md:p-8 shadow-2xl transition-transform duration-300 scale-95" id="vietQrPanel">
    <div class="flex items-center justify-between border-b border-stone-100 pb-4">
      <div>
        <span class="rounded-full bg-olive/10 px-3 py-1 text-xs font-semibold text-olive">Thanh toán VietQR 24/7</span>
        <h3 id="qrTitle" class="mt-2 text-xl font-bold text-charcoal">
          Chuyển khoản đơn hàng <span id="modalQrOrderCode" class="font-mono text-accent">#DH...</span>
        </h3>
      </div>
      <button type="button" id="closeQrModalBtn" class="tap-feedback rounded-full p-2 text-stone-400 hover:bg-stone-100 hover:text-charcoal" aria-label="Đóng">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" /></svg>
      </button>
    </div>

    <div class="mt-6 flex flex-col items-center">
      <!-- Ảnh VietQR Quick Link -->
      <div class="relative overflow-hidden rounded-xl border border-stone-200 bg-white p-2 shadow-sm">
        <img id="modalQrImage" src="" alt="Mã VietQR Thanh toán" class="h-64 w-64 object-contain" />
      </div>
      <p class="mt-3 text-center text-xs text-muted">
        Mở ứng dụng ngân hàng bất kỳ để quét mã QR và tự động điền đầy đủ thông tin.
      </p>

      <!-- Chi tiết chuyển khoản & nút Copy -->
      <div class="mt-6 w-full space-y-2.5 rounded-xl bg-cream p-4 text-xs">
        <div class="flex items-center justify-between">
          <span class="text-muted">Ngân hàng:</span>
          <span id="modalQrBankName" class="font-semibold text-charcoal">MB Bank (Ngân hàng Quân Đội)</span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-muted">Số tài khoản:</span>
          <div class="flex items-center gap-2">
            <span id="modalQrAccountNo" class="font-mono font-bold text-charcoal text-sm">0901235629</span>
            <button type="button" data-copy-target="modalQrAccountNo" class="tap-feedback rounded bg-stone-200 px-2 py-0.5 text-[11px] font-medium text-charcoal hover:bg-stone-300">Sao chép</button>
          </div>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-muted">Chủ tài khoản:</span>
          <span id="modalQrAccountName" class="font-semibold uppercase text-charcoal">AURA STUDIO</span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-muted">Số tiền cần chuyển:</span>
          <div class="flex items-center gap-2">
            <span id="modalQrAmount" class="font-bold text-accent text-sm tabular-nums">0đ</span>
            <button type="button" id="copyAmountBtn" class="tap-feedback rounded bg-stone-200 px-2 py-0.5 text-[11px] font-medium text-charcoal hover:bg-stone-300">Sao chép</button>
          </div>
        </div>
        <div class="flex items-center justify-between border-t border-stone-200/70 pt-2.5">
          <span class="text-muted">Nội dung chuyển khoản:</span>
          <div class="flex items-center gap-2">
            <span id="modalQrAddInfo" class="font-mono font-bold text-emerald-800 text-sm">DH...</span>
            <button type="button" data-copy-target="modalQrAddInfo" class="tap-feedback rounded bg-stone-200 px-2 py-0.5 text-[11px] font-medium text-charcoal hover:bg-stone-300">Sao chép</button>
          </div>
        </div>
      </div>

      <div class="mt-4 flex items-center gap-2 text-[11px] text-amber-700">
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
        <span>Vui lòng giữ đúng <strong>Nội dung chuyển khoản</strong> để hệ thống tự động xác nhận đơn.</span>
      </div>

      <div class="mt-6 flex w-full flex-col gap-2.5 sm:flex-row">
        <a id="modalQrViewOrderBtn" href="index.php?page=loyalty#myOrdersTitle"
          class="tap-feedback flex flex-1 items-center justify-center rounded-full bg-charcoal px-6 py-3.5 text-center text-sm font-medium text-white hover:bg-accent">
          Tôi đã chuyển khoản / Xem đơn hàng &rarr;
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ================= MODAL XÁC NHẬN ĐẶT HÀNG THÀNH CÔNG (COD) ================= -->
<div id="codSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-charcoal/50 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300" role="dialog" aria-modal="true" aria-labelledby="codTitle">
  <div class="relative w-full max-w-md rounded-2xl bg-white p-6 md:p-8 text-center shadow-2xl transition-transform duration-300 scale-95" id="codSuccessPanel">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
      <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" /></svg>
    </div>
    <h3 id="codTitle" class="mt-4 text-xl font-bold text-charcoal">Đặt hàng thành công!</h3>
    <p class="mt-1 text-sm text-muted">Mã đơn hàng: <strong id="modalCodOrderCode" class="font-mono text-accent">#DH...</strong></p>

    <div class="mt-6 space-y-2 rounded-xl bg-stone-50 p-4 text-left text-xs">
      <div class="flex justify-between">
        <span class="text-muted">Phương thức:</span>
        <span class="font-medium text-charcoal">Thanh toán khi nhận hàng (COD)</span>
      </div>
      <div class="flex justify-between">
        <span class="text-muted">Tổng thanh toán:</span>
        <span id="modalCodTotal" class="font-semibold text-charcoal tabular-nums">0đ</span>
      </div>
      <div class="flex justify-between">
        <span class="text-muted">Điểm tích lũy dự kiến:</span>
        <span id="modalCodPoints" class="font-semibold text-emerald-700 tabular-nums">+0 điểm</span>
      </div>
    </div>

    <p class="mt-4 text-xs text-muted">Đơn hàng của bạn đã được tiếp nhận ở trạng thái <strong>Chờ xác nhận</strong> và đang được xử lý.</p>

    <div class="mt-6 flex flex-col gap-2">
      <a href="index.php?page=loyalty#myOrdersTitle" class="tap-feedback rounded-full bg-charcoal py-3.5 text-center text-sm font-medium text-white hover:bg-accent">
        Xem danh sách đơn hàng &rarr;
      </a>
      <a href="index.php?page=home" class="tap-feedback rounded-full border border-stone-300 py-2.5 text-center text-xs font-medium text-muted hover:border-charcoal hover:text-charcoal">
        Tiếp tục mua sắm
      </a>
    </div>
  </div>
</div>

<script>
  (() => {
    const subtotal = <?= json_encode((float)$checkoutSummary['subtotal']) ?>;
    const tierDiscount = <?= json_encode((float)$checkoutSummary['tier_discount_amount']) ?>;
    const tierMultiplier = <?= json_encode($heSoTichDiem) ?>;

    const voucherSelect = document.getElementById('voucherSelect');
    const pointsToggle = document.getElementById('pointsToggle');
    const ckVoucher = document.getElementById('ckVoucher');
    const ckPoints = document.getElementById('ckPoints');
    const ckTotal = document.getElementById('ckTotal');
    const ckExpectedPoints = document.getElementById('ckExpectedPoints');
    const placeOrderBtn = document.getElementById('placeOrderBtn');

    let rawFinalTotal = <?= json_encode((float)$checkoutSummary['final_total']) ?>;

    function formatVnd(n) {
      return Math.round(Number(n) || 0).toLocaleString('vi-VN') + 'đ';
    }

    function recalculateCheckout() {
      let voucherDisc = 0;
      if (voucherSelect) {
        const opt = voucherSelect.options[voucherSelect.selectedIndex];
        const minOrder = Number(opt?.dataset.min || 0);
        const val = Number(opt?.dataset.value || 0);
        const type = opt?.dataset.type || 'TIEN';
        if (val > 0 && subtotal >= minOrder) {
          voucherDisc = type === 'PHAN_TRAM' ? Math.round(subtotal * (val / 100)) : val;
        } else if (val > 0 && subtotal < minOrder) {
          window.showStoreToast?.(`Đơn hàng cần tối thiểu ${formatVnd(minOrder)} để áp dụng mã này`);
          voucherSelect.value = '0';
        }
      }

      const ptsUsed = (pointsToggle && pointsToggle.checked) ? Number(pointsToggle.dataset.points || 0) : 0;
      const pointsDisc = ptsUsed * 1000;

      rawFinalTotal = Math.max(0, subtotal - tierDiscount - voucherDisc - pointsDisc);
      const expectedPts = Math.floor((rawFinalTotal / 10000) * tierMultiplier);

      if (ckVoucher) ckVoucher.textContent = '-' + formatVnd(voucherDisc);
      if (ckPoints) ckPoints.textContent = '-' + formatVnd(pointsDisc);
      if (ckTotal) ckTotal.textContent = formatVnd(rawFinalTotal);
      if (ckExpectedPoints) ckExpectedPoints.textContent = `+${expectedPts} điểm thưởng`;
    }

    voucherSelect?.addEventListener('change', recalculateCheckout);
    pointsToggle?.addEventListener('change', recalculateCheckout);
    recalculateCheckout();

    // Xử lý sao chép văn bản
    function copyToClipboard(text, triggerBtn) {
      navigator.clipboard.writeText(text).then(() => {
        const original = triggerBtn.textContent;
        triggerBtn.textContent = '✓ Đã chép';
        triggerBtn.classList.add('bg-emerald-100', 'text-emerald-800');
        setTimeout(() => {
          triggerBtn.textContent = original;
          triggerBtn.classList.remove('bg-emerald-100', 'text-emerald-800');
        }, 1800);
      }).catch(() => {
        window.showStoreToast?.(`Đã sao chép: ${text}`);
      });
    }

    document.querySelectorAll('[data-copy-target]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const targetId = btn.dataset.copyTarget;
        const text = document.getElementById(targetId)?.textContent?.trim() || '';
        copyToClipboard(text, btn);
      });
    });

    document.getElementById('copyAmountBtn')?.addEventListener('click', function() {
      copyToClipboard(String(Math.round(rawFinalTotal)), this);
    });

    function showModal(modalId, panelId) {
      const modal = document.getElementById(modalId);
      const panel = document.getElementById(panelId);
      if (!modal || !panel) return;
      modal.classList.remove('opacity-0', 'pointer-events-none');
      modal.classList.add('opacity-100', 'pointer-events-auto');
      panel.classList.remove('scale-95');
      panel.classList.add('scale-100');
    }

    function hideModal(modalId, panelId) {
      const modal = document.getElementById(modalId);
      const panel = document.getElementById(panelId);
      if (!modal || !panel) return;
      modal.classList.add('opacity-0', 'pointer-events-none');
      modal.classList.remove('opacity-100', 'pointer-events-auto');
      panel.classList.add('scale-95');
      panel.classList.remove('scale-100');
    }

    document.getElementById('closeQrModalBtn')?.addEventListener('click', () => {
      hideModal('vietQrModal', 'vietQrPanel');
      window.location.href = 'index.php?page=loyalty#myOrdersTitle';
    });

    // BẮT SỰ KIỆN XÁC NHẬN ĐẶT HÀNG (TRANSACTION CHECKOUT)
    if (placeOrderBtn) {
      placeOrderBtn.addEventListener('click', async () => {
        const selectedAddress = document.querySelector('input[name="address"]:checked');
        if (!selectedAddress) {
          window.showStoreToast?.('Vui lòng chọn địa chỉ nhận hàng.');
          return;
        }

        const maDc = Number(selectedAddress.value);
        const selectedPayment = document.querySelector('input[name="payment"]:checked');
        const ptThanhToan = selectedPayment ? selectedPayment.value : 'COD';
        const maPgg = voucherSelect ? Number(voucherSelect.value) : 0;
        const dungDiem = pointsToggle ? pointsToggle.checked : false;

        placeOrderBtn.disabled = true;
        const originalBtnHtml = placeOrderBtn.innerHTML;
        placeOrderBtn.innerHTML = `
          <span class="inline-flex items-center gap-2">
            <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            Đang xử lý Transaction đặt hàng & khóa kho...
          </span>
        `;

        try {
          const res = await fetch('api/checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              ma_dc: maDc,
              pt_thanh_toan: ptThanhToan,
              ma_pgg: maPgg > 0 ? maPgg : null,
              dung_diem: dungDiem,
            }),
          });

          const data = await res.json();

          if (!data.success) {
            window.showStoreToast?.(data.message || 'Đặt hàng không thành công');
            placeOrderBtn.disabled = false;
            placeOrderBtn.innerHTML = originalBtnHtml;
            return;
          }

          // Cập nhật ngay Badge giỏ hàng trên Header về 0
          const badge = document.getElementById('cartBadge');
          if (badge) {
            badge.textContent = '0';
            badge.classList.remove('flex');
            badge.classList.add('hidden');
          }
          if (window.cartAPI) {
            window.cartAPI.get();
          }

          // Nếu thanh toán Chuyển khoản -> Hiển thị Modal VietQR
          if (data.pt_thanh_toan === 'CHUYEN_KHOAN' && data.vietqr) {
            const qrImg = document.getElementById('modalQrImage');
            const qrOrderCode = document.getElementById('modalQrOrderCode');
            const qrAccountNo = document.getElementById('modalQrAccountNo');
            const qrAccountName = document.getElementById('modalQrAccountName');
            const qrAmount = document.getElementById('modalQrAmount');
            const qrAddInfo = document.getElementById('modalQrAddInfo');
            const qrBankName = document.getElementById('modalQrBankName');

            if (qrImg) qrImg.src = data.vietqr.qr_url;
            if (qrOrderCode) qrOrderCode.textContent = `#${data.ma_dh_code}`;
            if (qrAccountNo) qrAccountNo.textContent = data.vietqr.account_no;
            if (qrAccountName) qrAccountName.textContent = data.vietqr.account_name;
            if (qrAmount) qrAmount.textContent = formatVnd(data.thanh_tien);
            if (qrAddInfo) qrAddInfo.textContent = data.vietqr.add_info;
            if (qrBankName) qrBankName.textContent = data.vietqr.bank_name;

            rawFinalTotal = data.thanh_tien;
            showModal('vietQrModal', 'vietQrPanel');
          } else {
            // Thanh toán COD -> Hiển thị Modal thông báo thành công
            const codCode = document.getElementById('modalCodOrderCode');
            const codTotal = document.getElementById('modalCodTotal');
            const codPoints = document.getElementById('modalCodPoints');

            if (codCode) codCode.textContent = `#${data.ma_dh_code}`;
            if (codTotal) codTotal.textContent = formatVnd(data.thanh_tien);
            if (codPoints) codPoints.textContent = `+${data.expected_points} điểm`;

            showModal('codSuccessModal', 'codSuccessPanel');
          }

        } catch (err) {
          console.error(err);
          window.showStoreToast?.('Lỗi kết nối khi gửi yêu cầu thanh toán');
          placeOrderBtn.disabled = false;
          placeOrderBtn.innerHTML = originalBtnHtml;
        }
      });
    }
  })();
</script>
