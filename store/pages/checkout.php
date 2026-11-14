<?php
/**
 * TẦNG CỬA HÀNG: Trang Thanh toán Đa tầng (store/pages/checkout.php)
 * Đổ dữ liệu từ dc_dia_chi_kh, gh_gio_hang, vkh_vi_voucher_kh, ttd_the_tich_diem, htv_hang_thanh_vien
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
          <button type="button" onclick="openAddressModal('create')"
            class="tap-feedback inline-flex items-center gap-1.5 rounded-full bg-charcoal px-4 py-2 text-xs font-medium text-white hover:bg-accent transition shadow-sm">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            + Thêm địa chỉ mới
          </button>
        </div>

        <div class="mt-6 space-y-3" id="checkoutAddressList">
          <?php if (empty($customerAddresses)): ?>
            <div class="rounded-xl border border-dashed border-stone-300 bg-white p-6 text-center text-sm text-muted">
              <p>Khách hàng hiện chưa có địa chỉ nhận hàng trong CSDL.</p>
              <button type="button" onclick="openAddressModal('create')" class="mt-3 rounded-full bg-charcoal px-4 py-1.5 text-xs font-medium text-white hover:bg-accent transition">
                + Thêm địa chỉ đầu tiên ngay
              </button>
            </div>
          <?php else: ?>
            <?php foreach ($customerAddresses as $addr): ?>
              <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-stone-400 has-[:checked]:border-charcoal has-[:checked]:ring-1 has-[:checked]:ring-charcoal/10">
                <div class="flex items-start justify-between gap-4">
                  <label class="flex cursor-pointer gap-3.5 flex-1 items-start">
                    <input type="radio" name="address" value="<?= (int)$addr['ma_dc'] ?>" <?= (int)$addr['mac_dinh'] === 1 ? 'checked' : '' ?> class="mt-1 h-4 w-4 accent-accent" />
                    <div>
                      <div class="flex items-center gap-2">
                        <span class="block text-sm font-semibold text-charcoal">
                          <?= htmlspecialchars($addr['ten_nguoi_nhan']) ?>
                        </span>
                        <?php if ((int)$addr['mac_dinh'] === 1): ?>
                          <span class="rounded-full bg-olive/10 px-2.5 py-0.5 text-xs font-semibold text-olive">Mặc định</span>
                        <?php endif; ?>
                      </div>
                      <span class="mt-1 block text-xs font-medium text-stone-600">SĐT nhận: <?= htmlspecialchars($addr['sdt_nhan']) ?></span>
                      <span class="mt-1 block text-sm text-muted leading-relaxed">
                        <?= htmlspecialchars($addr['dia_chi_cu_the']) ?>, <?= htmlspecialchars($addr['phuong_xa']) ?>, <?= htmlspecialchars($addr['quan_huyen']) ?>, <?= htmlspecialchars($addr['tinh_thanh']) ?>
                      </span>
                    </div>
                  </label>

                  <!-- Address Action Buttons -->
                  <div class="flex flex-col sm:flex-row items-end sm:items-center gap-2 shrink-0 pt-0.5">
                    <?php if ((int)$addr['mac_dinh'] !== 1): ?>
                      <button type="button" onclick="setDefaultAddress(<?= (int)$addr['ma_dc'] ?>)"
                        class="text-xs font-medium text-stone-500 hover:text-charcoal hover:underline">
                        Đặt mặc định
                      </button>
                      <span class="hidden sm:inline text-stone-300">&bull;</span>
                    <?php endif; ?>
                    <button type="button" onclick='openAddressModal("update", <?= htmlspecialchars(json_encode($addr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, "UTF-8") ?>)'
                      class="text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                      Sửa
                    </button>
                    <span class="hidden sm:inline text-stone-300">&bull;</span>
                    <button type="button" onclick="deleteAddress(<?= (int)$addr['ma_dc'] ?>)"
                      class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline">
                      Xóa
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <!-- Phương thức thanh toán -->
      <section aria-labelledby="paymentTitle">
        <h2 id="paymentTitle" class="text-xl font-semibold">Phương thức thanh toán</h2>
        <div class="mt-6 space-y-3">
          <label class="flex cursor-pointer gap-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-stone-400 has-[:checked]:border-charcoal">
            <input type="radio" name="payment" value="COD" checked class="mt-1 h-4 w-4 accent-accent" />
            <span>
              <span class="block text-sm font-medium">Thanh toán khi nhận hàng (COD)</span>
              <span class="mt-1 block text-sm text-muted">Kiểm tra sản phẩm trước khi thanh toán. Điểm thưởng cộng khi Admin duyệt hoàn thành đơn.</span>
            </span>
          </label>
          <label class="flex cursor-pointer gap-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-stone-400 has-[:checked]:border-charcoal">
            <input type="radio" name="payment" value="CHUYEN_KHOAN" class="mt-1 h-4 w-4 accent-accent" />
            <span>
              <span class="block text-sm font-medium">Chuyển khoản ngân hàng (VietQR: Đang phát triển)</span>
              <span class="mt-1 block text-sm text-muted">Hiển thị mã QR thanh toán tự động sau khi xác nhận đặt hàng.</span>
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
          <div class="rounded-xl bg-stone-100 p-6 text-center text-sm text-muted">
            Giỏ hàng của <strong><?= htmlspecialchars($hoTen) ?></strong> đang trống. Hãy chọn tài khoản khách hàng khác (VD: Neàng Ra, Hoàng Nam, Bảo Trân) hoặc thêm sản phẩm.
          </div>
        <?php else: ?>
          <ul class="divide-y divide-stone-200 text-sm">
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
              <span class="mt-1.5 block text-xs text-muted">Ví cá nhân chưa có Voucher khả dụng (Hãy sang mục Trung tâm Thành viên hoặc chọn tài khoản Hoàng Nam để xem Voucher trong ví).</span>
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
          data-dev-feature="Transaction Đặt hàng (Tạo dh_don_hang, trừ kho bt_bien_the_sp, cập nhật ví voucher & trừ điểm)"
          class="tap-feedback w-full rounded-full bg-charcoal px-6 py-4 text-sm font-medium text-white hover:bg-accent">
          Xác nhận Đặt hàng (Đang phát triển)
        </button>
      </div>
    </aside>
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

    function formatVnd(n) {
      return Math.round(n).toLocaleString('vi-VN') + 'đ';
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

      const finalTotal = Math.max(0, subtotal - tierDiscount - voucherDisc - pointsDisc);
      const expectedPts = Math.floor((finalTotal / 10000) * tierMultiplier);

      if (ckVoucher) ckVoucher.textContent = '-' + formatVnd(voucherDisc);
      if (ckPoints) ckPoints.textContent = '-' + formatVnd(pointsDisc);
      if (ckTotal) ckTotal.textContent = formatVnd(finalTotal);
      if (ckExpectedPoints) ckExpectedPoints.textContent = `+${expectedPts} điểm thưởng`;
    }

    voucherSelect?.addEventListener('change', recalculateCheckout);
    pointsToggle?.addEventListener('change', recalculateCheckout);
    recalculateCheckout();
  })();
</script>

