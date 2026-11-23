<?php
/**
 * TẦNG CỬA HÀNG: Trang Trung tâm Thành viên & Đổi điểm Voucher (store/pages/loyalty.php)
 * Đổ 100% dữ liệu từ ttd_the_tich_diem, htv_hang_thanh_vien, pgg_phieu_giam_gia, vkh_vi_voucher_kh, lsd_lich_su_diem, dh_don_hang
 */
$maSoThe = $loyaltyProfile['ma_so_the'] ?? 'CHƯA CẤP THẺ';
$hoTen = $loyaltyProfile['ho_ten'] ?? 'Khách hàng';
$tenHang = $loyaltyProfile['ten_hang'] ?? 'Hạng Đồng';
$mauSacThe = $loyaltyProfile['mau_sac_the'] ?? '#2D2D2D';
$tongDiemTichLuy = (int) ($loyaltyProfile['tong_diem_tich_luy'] ?? 0);
$diemKhaDung = (int) ($loyaltyProfile['diem_kha_dung'] ?? 0);
$nextTier = $loyaltyProfile['next_tier'] ?? null;
$pointsNeeded = (int) ($loyaltyProfile['points_needed'] ?? 0);
$progressPercent = (float) ($loyaltyProfile['progress_percent'] ?? 0);

$txLabels = [
    'CONG_MUA_HANG'   => ['label' => 'Cộng mua hàng',   'class' => 'text-emerald-700', 'bg' => 'bg-emerald-50 text-emerald-800'],
    'CONG_DANH_GIA'   => ['label' => 'Thưởng đánh giá', 'class' => 'text-emerald-700', 'bg' => 'bg-emerald-50 text-emerald-800'],
    'TRU_DOI_VOUCHER' => ['label' => 'Đổi voucher',     'class' => 'text-orange-700',  'bg' => 'bg-amber-50 text-amber-800'],
    'TRU_TIEU_DIEM'   => ['label' => 'Tiêu điểm đơn',   'class' => 'text-orange-700',  'bg' => 'bg-amber-50 text-amber-800'],
];

$orderStatusBadges = [
    'CHO_XAC_NHAN' => ['label' => 'Chờ xác nhận', 'class' => 'bg-amber-100 text-amber-800'],
    'DANG_GIAO'    => ['label' => 'Đang giao',     'class' => 'bg-blue-100 text-blue-800'],
    'HOAN_THANH'   => ['label' => 'Hoàn thành',    'class' => 'bg-emerald-100 text-emerald-800'],
    'DA_HUY'       => ['label' => 'Đã hủy',        'class' => 'bg-rose-100 text-rose-700'],
];
?>
<div id="loyaltyView" class="fade-up space-y-16">
  <!-- Thẻ tích điểm điện tử & Tiến trình nâng hạng từ CSDL -->
  <section class="grid gap-10 lg:grid-cols-5">
    <div class="lg:col-span-2 space-y-6">
      <div class="relative overflow-hidden rounded-2xl bg-charcoal p-8 text-cream shadow-md sm:p-10"
        style="border-top: 4px solid <?= htmlspecialchars($mauSacThe) ?>">
        <div class="flex items-start justify-between">
          <p class="text-xs uppercase tracking-[0.25em] text-stone-300">Fashion Loyalty Card</p>
          <p class="text-sm font-semibold tracking-[0.25em]">AURA STUDIO</p>
        </div>
        <p class="mt-12 font-mono text-xl tracking-[0.2em] text-white"><?= htmlspecialchars($maSoThe) ?></p>
        <div class="mt-10 flex items-end justify-between gap-6">
          <div>
            <p class="text-xs uppercase tracking-wider text-stone-300">Chủ thẻ</p>
            <p class="mt-1 text-base font-semibold uppercase"><?= htmlspecialchars($hoTen) ?></p>
          </div>
          <div class="text-right">
            <p class="text-xs uppercase tracking-wider text-stone-300">Hạng hiện tại</p>
            <p class="mt-1 text-base font-semibold text-gold uppercase"><?= htmlspecialchars($tenHang) ?></p>
          </div>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
        <div class="rounded-xl bg-white p-6 shadow-sm">
          <p class="text-sm text-muted">Tổng điểm xét hạng</p>
          <p class="tabular-nums mt-2 text-3xl font-semibold text-charcoal">
            <?= number_format($tongDiemTichLuy, 0, ',', '.') ?> <span class="text-base font-normal text-muted">điểm</span>
          </p>
          <p class="mt-1 text-xs text-muted">Tích lũy trọn đời để nâng hạng thẻ</p>
        </div>
        <div class="rounded-xl bg-white p-6 shadow-sm">
          <p class="text-sm text-muted">Điểm khả dụng đổi quà</p>
          <p id="loyaltyAvailablePoints" class="tabular-nums mt-2 text-3xl font-semibold text-olive">
            <?= number_format($diemKhaDung, 0, ',', '.') ?> <span class="text-base font-normal text-muted">điểm</span>
          </p>
          <p class="mt-1 text-xs text-muted">Dùng đổi Voucher hoặc trừ tiền đơn</p>
        </div>
      </div>
    </div>

    <div class="lg:col-span-3 flex flex-col justify-between rounded-xl bg-white p-8 shadow-sm">
      <div>
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="rounded-full bg-olive/10 px-3 py-1 text-xs font-medium text-olive">
            Đặc quyền hiện tại: Giảm <?= number_format((float)($loyaltyProfile['phan_tram_giam'] ?? 0), 0) ?>% &middot; Nhân x<?= number_format((float)($loyaltyProfile['he_so_tich_diem'] ?? 1), 1) ?> điểm
          </span>
          <span class="text-xs text-muted">Ngày cấp thẻ: <?= formatDateVN($loyaltyProfile['ngay_cap'] ?? null) ?></span>
        </div>

        <?php if ($nextTier): ?>
          <p class="tabular-nums mt-6 text-sm text-muted">
            Tiến trình lên hạng: <span class="font-semibold text-charcoal"><?= number_format($tongDiemTichLuy, 0, ',', '.') ?> / <?= number_format((int)$nextTier['diem_toi_thieu'], 0, ',', '.') ?> điểm</span> (<?= $progressPercent ?>%)
          </p>
          <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-stone-100" role="progressbar"
            aria-valuemin="0" aria-valuemax="<?= (int)$nextTier['diem_toi_thieu'] ?>" aria-valuenow="<?= $tongDiemTichLuy ?>">
            <div class="h-full rounded-full bg-olive transition-all duration-700" style="width: <?= $progressPercent ?>%"></div>
          </div>
          <p class="mt-5 text-base leading-relaxed text-charcoal">
            Chỉ cần tích thêm <span class="tabular-nums font-semibold text-accent"><?= number_format($pointsNeeded, 0, ',', '.') ?> điểm</span> nữa để tự động thăng lên <span class="font-semibold uppercase"><?= htmlspecialchars($nextTier['ten_hang']) ?></span>.
          </p>
          <p class="mt-2 text-sm leading-relaxed text-muted">
            Quyền lợi <?= htmlspecialchars($nextTier['ten_hang']) ?>: <?= htmlspecialchars($nextTier['mo_ta_uu_dai'] ?? '') ?>
          </p>
        <?php else: ?>
          <p class="mt-6 text-lg font-semibold text-olive">Chúc mừng! Bạn đã đạt bậc Hạng cao nhất trong hệ thống.</p>
          <p class="mt-2 text-sm text-muted"><?= htmlspecialchars($loyaltyProfile['mo_ta_uu_dai'] ?? '') ?></p>
        <?php endif; ?>
      </div>

      <!-- Lộ trình 4 Bậc Hạng Thành Viên từ bảng htv_hang_thanh_vien -->
      <div class="mt-8 grid grid-cols-2 gap-3 border-t border-stone-100 pt-6 sm:grid-cols-4">
        <?php foreach ($loyaltyProfile['all_tiers'] ?? [] as $t): ?>
          <?php $isCurrentTier = ((int)$t['ma_hang'] === (int)($loyaltyProfile['ma_hang'] ?? 0)); ?>
          <div class="rounded-lg border p-3 <?= $isCurrentTier ? 'border-charcoal bg-stone-100/80' : 'border-stone-200 bg-cream/50' ?>">
            <div class="flex items-center gap-1.5">
              <span class="h-2.5 w-2.5 rounded-full" style="background: <?= htmlspecialchars($t['mau_sac_the']) ?>"></span>
              <p class="text-xs font-semibold text-charcoal"><?= htmlspecialchars($t['ten_hang']) ?></p>
            </div>
            <p class="tabular-nums mt-1 text-xs text-muted">Mốc: <?= number_format((int)$t['diem_toi_thieu'], 0, ',', '.') ?> điểm</p>
            <p class="tabular-nums text-[11px] font-medium text-accent">Giảm <?= number_format((float)$t['phan_tram_giam'], 0) ?>% &middot; x<?= number_format((float)$t['he_so_tich_diem'], 1) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Kho Phiếu giảm giá (Đổi điểm nhận Voucher từ bảng pgg_phieu_giam_gia) -->
  <section aria-labelledby="voucherTitle">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h2 id="voucherTitle" class="text-xl font-semibold">Kho Đổi điểm nhận Voucher</h2>
        <p class="mt-1 text-sm text-muted">Chọn chiến dịch Voucher phù hợp với số điểm khả dụng (<strong class="tabular-nums text-charcoal"><?= $diemKhaDung ?> điểm</strong>) và hạng hiện tại của bạn.</p>
      </div>
    </div>

    <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
      <?php foreach ($voucherCatalog as $idx => $v): ?>
        <?php
          $owned = $v['owned_info'];
          $discountLabel = $v['loai_giam'] === 'PHAN_TRAM'
              ? 'Giảm ' . number_format((float)$v['gia_tri_giam'], 0) . '%'
              : 'Giảm ' . formatVND($v['gia_tri_giam']);
          $isOutOfStock = (int)$v['so_luong'] <= 0;
        ?>
        <article class="fade-up relative flex flex-col justify-between rounded-xl border border-dashed border-stone-300 bg-white p-6 shadow-sm <?= (!$v['is_tier_eligible'] || ($isOutOfStock && !$owned)) ? 'opacity-75' : '' ?>"
          style="--stagger: <?= $idx ?>">
          <div>
            <div class="flex items-center justify-between gap-2">
              <span class="text-xs font-medium uppercase tracking-wider text-accent"><?= htmlspecialchars($v['ten_hang_yeu_cau']) ?></span>
              <?php if ($isOutOfStock): ?>
                <span class="tabular-nums text-xs font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">Hết lượt đổi</span>
              <?php else: ?>
                <span class="tabular-nums text-xs text-muted">Còn <?= (int)$v['so_luong'] ?> mã</span>
              <?php endif; ?>
            </div>
            <p class="mt-2 font-mono text-xl font-semibold tracking-wide text-charcoal"><?= htmlspecialchars($v['ma_code']) ?></p>
            <p class="mt-2 text-sm font-medium text-charcoal"><?= htmlspecialchars($v['ten_pgg']) ?></p>
            <p class="tabular-nums mt-1 text-xs text-muted">
              Đơn tối thiểu: <?= formatVND($v['don_toi_thieu']) ?> &middot; HSD: <?= formatDateVN($v['ngay_kt']) ?>
            </p>
            <p class="tabular-nums mt-3 inline-block rounded-full bg-gold px-3 py-1 text-xs font-semibold text-charcoal">
              Cần <?= number_format((int)$v['diem_can_doi'], 0, ',', '.') ?> điểm khả dụng
            </p>
          </div>

          <div class="mt-6">
            <?php if ($owned): ?>
              <?php if ((int)$owned['trang_thai_su_dung'] === 1): ?>
                <button type="button" disabled class="w-full rounded-full bg-stone-200 px-4 py-2.5 text-xs font-medium text-muted cursor-not-allowed">
                  Đã sử dụng (<?= formatDateVN($owned['ngay_su_dung']) ?>)
                </button>
              <?php else: ?>
                <a href="index.php?page=checkout" class="tap-feedback block w-full rounded-full bg-olive px-4 py-2.5 text-center text-xs font-medium text-white hover:opacity-95">
                  Đã có trong Ví &rarr; Dùng ngay
                </a>
              <?php endif; ?>
            <?php elseif ($isOutOfStock): ?>
              <button type="button" disabled class="w-full rounded-full bg-stone-100 border border-stone-200 px-4 py-2.5 text-xs font-medium text-stone-500 cursor-not-allowed" title="Voucher này đã hết lượt hoặc đang tạm dừng đổi mới">
                Đã khóa / Hết lượt đổi
              </button>
            <?php elseif (!$v['is_tier_eligible']): ?>
              <button type="button" disabled class="w-full rounded-full bg-stone-200 px-4 py-2.5 text-xs font-medium text-muted cursor-not-allowed">
                Yêu cầu từ <?= htmlspecialchars($v['ten_hang_yeu_cau']) ?>
              </button>
            <?php elseif (!$v['is_points_eligible']): ?>
              <button type="button" disabled class="w-full rounded-full bg-stone-200 px-4 py-2.5 text-xs font-medium text-muted cursor-not-allowed">
                Thiếu <?= (int)$v['diem_can_doi'] - $diemKhaDung ?> điểm khả dụng
              </button>
            <?php else: ?>
              <button type="button"
                data-redeem-btn="<?= (int)$v['ma_pgg'] ?>"
                data-voucher-code="<?= htmlspecialchars($v['ma_code']) ?>"
                data-points-needed="<?= (int)$v['diem_can_doi'] ?>"
                class="tap-feedback w-full rounded-full bg-charcoal px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-accent transition">
                Đổi ngay (-<?= number_format((int)$v['diem_can_doi'], 0, ',', '.') ?> điểm)
              </button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Sổ cái Lịch sử biến động điểm từ bảng lsd_lich_su_diem -->
  <section aria-labelledby="ledgerTitle">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h2 id="ledgerTitle" class="text-xl font-semibold">Lịch sử biến động điểm</h2>
        <p class="mt-1 text-sm text-muted">Sổ cái đối soát từng điểm thưởng cộng vào và trừ ra của thẻ <strong><?= htmlspecialchars($maSoThe) ?></strong>.</p>
      </div>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl bg-white shadow-sm">
      <?php if (empty($pointLedger)): ?>
        <div class="p-10 text-center">
          <p class="text-base font-medium text-charcoal">Chưa có phát sinh giao dịch điểm nào</p>
          <p class="mt-1 text-sm text-muted">Khách hàng <strong><?= htmlspecialchars($hoTen) ?></strong> hiện chưa có lịch sử biến động điểm.</p>
        </div>
      <?php else: ?>
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-stone-200 text-xs uppercase tracking-wider text-muted">
            <tr>
              <th scope="col" class="px-6 py-4 font-medium">Thời gian</th>
              <th scope="col" class="px-6 py-4 font-medium">Mã đơn</th>
              <th scope="col" class="px-6 py-4 font-medium">Loại giao dịch</th>
              <th scope="col" class="px-6 py-4 text-right font-medium">Điểm thay đổi</th>
              <th scope="col" class="px-6 py-4 text-right font-medium">Số dư khả dụng</th>
              <th scope="col" class="px-6 py-4 font-medium">Nội dung đối soát</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-200 tabular-nums">
            <?php foreach ($pointLedger as $row): ?>
              <?php
                $txType = $txLabels[$row['loai_giao_dich']] ?? ['label' => $row['loai_giao_dich'], 'class' => 'text-charcoal', 'bg' => 'bg-stone-100'];
                $delta = (int) $row['so_diem_thay_doi'];
                $deltaFormatted = ($delta > 0 ? '+' : '') . number_format($delta, 0, ',', '.');
              ?>
              <tr>
                <td class="whitespace-nowrap px-6 py-4 text-muted"><?= formatDateVN($row['thoi_gian'], true) ?></td>
                <td class="whitespace-nowrap px-6 py-4 font-mono text-xs font-medium">
                  <?= $row['ma_dh'] ? '#DH' . str_pad((string)$row['ma_dh'], 2, '0', STR_PAD_LEFT) : '-' ?>
                </td>
                <td class="whitespace-nowrap px-6 py-4">
                  <span class="rounded-full px-2.5 py-1 text-xs font-medium <?= $txType['bg'] ?>">
                    <?= htmlspecialchars($txType['label']) ?>
                  </span>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-right font-semibold <?= $txType['class'] ?>">
                  <?= $deltaFormatted ?>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-charcoal">
                  <?= number_format((int)$row['so_du_sau_gd'], 0, ',', '.') ?>
                </td>
                <td class="px-6 py-4 text-muted"><?= htmlspecialchars($row['noi_dung']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </section>

  <!-- Quản lý Sổ địa chỉ nhận hàng từ bảng dc_dia_chi_kh (Yêu cầu 1.3) -->
  <section id="so-dia-chi" aria-labelledby="addressBookTitle" class="rounded-2xl bg-white p-6 sm:p-8 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-stone-100 pb-5">
      <div>
        <h2 id="addressBookTitle" class="text-xl font-semibold text-charcoal">
          Sổ địa chỉ nhận hàng
        </h2>
        <p class="mt-1 text-xs text-muted">
          Quản lý danh sách địa chỉ nhận hàng cá nhân của <strong><?= htmlspecialchars($hoTen) ?></strong>
        </p>
      </div>
      <button type="button" onclick="openAddressModal('create')"
        class="tap-feedback inline-flex items-center gap-1.5 rounded-full bg-charcoal px-4 py-2 text-xs font-medium text-white hover:bg-accent transition shadow-sm">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        + Thêm địa chỉ mới
      </button>
    </div>

    <div class="mt-6">
      <?php if (empty($customerAddresses)): ?>
        <div class="rounded-xl border border-dashed border-stone-300 p-8 text-center text-sm text-muted">
          <p>Bạn chưa lưu địa chỉ nhận hàng nào trong hệ thống.</p>
          <button type="button" onclick="openAddressModal('create')" class="mt-3 rounded-full bg-charcoal px-4 py-2 text-xs font-medium text-white hover:bg-accent transition">
            + Thêm địa chỉ nhận hàng đầu tiên
          </button>
        </div>
      <?php else: ?>
        <div class="grid gap-4 sm:grid-cols-2">
          <?php foreach ($customerAddresses as $addr): ?>
            <div class="flex flex-col justify-between rounded-xl border border-stone-200 p-5 shadow-sm transition hover:border-stone-400 <?= (int)$addr['mac_dinh'] === 1 ? 'border-charcoal bg-stone-50/40' : 'bg-white' ?>">
              <div>
                <div class="flex items-center justify-between">
                  <span class="text-sm font-semibold text-charcoal"><?= htmlspecialchars($addr['ten_nguoi_nhan']) ?></span>
                  <?php if ((int)$addr['mac_dinh'] === 1): ?>
                    <span class="rounded-full bg-olive/10 px-2.5 py-0.5 text-xs font-semibold text-olive">Mặc định</span>
                  <?php endif; ?>
                </div>
                <p class="mt-1 text-xs text-stone-600 font-medium">SĐT nhận: <?= htmlspecialchars($addr['sdt_nhan']) ?></p>
                <p class="mt-2 text-xs text-muted leading-relaxed">
                  <?php
                    $addrParts = array_filter([
                        trim((string)($addr['dia_chi_cu_the'] ?? '')),
                        trim((string)($addr['phuong_xa'] ?? '')),
                        trim((string)($addr['quan_huyen'] ?? '')),
                        trim((string)($addr['tinh_thanh'] ?? '')),
                    ], fn($v) => $v !== '');
                    echo htmlspecialchars(implode(', ', $addrParts));
                  ?>
                </p>
              </div>

              <div class="mt-4 flex items-center justify-between border-t border-stone-100 pt-3 text-xs">
                <div>
                  <?php if ((int)$addr['mac_dinh'] !== 1): ?>
                    <button type="button" onclick="setDefaultAddress(<?= (int)$addr['ma_dc'] ?>)"
                      class="font-medium text-stone-600 hover:text-charcoal hover:underline">
                      Đặt làm mặc định
                    </button>
                  <?php else: ?>
                    <span class="text-[11px] text-muted italic">Địa chỉ giao mặc định</span>
                  <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                  <button type="button" onclick='openAddressModal("update", <?= htmlspecialchars(json_encode($addr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, "UTF-8") ?>)'
                    class="font-medium text-blue-600 hover:underline">
                    Sửa
                  </button>
                  <span class="text-stone-300">&bull;</span>
                  <button type="button" onclick="deleteAddress(<?= (int)$addr['ma_dc'] ?>)"
                    class="font-medium text-red-500 hover:underline">
                    Xóa
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Danh sách Đơn đặt hàng của khách hàng từ bảng dh_don_hang -->
  <section aria-labelledby="myOrdersTitle" class="w-full">
    <div class="flex items-center justify-between">
      <h2 id="myOrdersTitle" class="text-xl font-semibold text-charcoal">Đơn hàng của <?= htmlspecialchars($hoTen) ?></h2>
      <span class="text-xs text-muted"><?= count($customerOrders) ?> đơn hàng</span>
    </div>
    <div class="mt-6 w-full overflow-x-auto rounded-2xl border border-stone-200 bg-white shadow-sm">
      <?php if (empty($customerOrders)): ?>
        <div class="p-10 text-center text-sm text-muted">Bạn chưa có đơn đặt hàng nào.</div>
      <?php else: ?>
        <table class="min-w-full text-left text-xs sm:text-sm border-collapse">
          <thead class="border-b border-stone-200 bg-stone-50/75 text-xs uppercase tracking-wider text-muted">
            <tr>
              <th scope="col" class="px-3 py-3 font-semibold text-charcoal whitespace-nowrap">Mã đơn</th>
              <th scope="col" class="px-3 py-3 font-semibold text-charcoal min-w-[200px]">Sản phẩm</th>
              <th scope="col" class="px-3 py-3 font-semibold text-charcoal whitespace-nowrap">Ngày đặt</th>
              <th scope="col" class="px-3 py-3 font-semibold text-charcoal text-right whitespace-nowrap">Thực trả</th>
              <th scope="col" class="px-3 py-3 font-semibold text-charcoal text-center whitespace-nowrap">Trạng thái</th>
              <th scope="col" class="px-3 py-3 font-semibold text-charcoal text-center whitespace-nowrap">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-200 tabular-nums">
            <?php foreach ($customerOrders as $od): ?>
              <?php $stKey = $od['trang_thai_dh'] ?? 'CHO_XAC_NHAN'; ?>
              <tr id="order-row-<?= (int)$od['ma_dh'] ?>" class="align-top hover:bg-stone-50/50 transition-colors">
                <td class="whitespace-nowrap px-3 py-3 font-mono font-semibold text-charcoal">
                  #<?= htmlspecialchars($od['ma_dh_code'] ?? ('DH' . str_pad((string)$od['ma_dh'], 2, '0', STR_PAD_LEFT))) ?>
                </td>
                <td class="px-3 py-3">
                  <?php if (!empty($od['items'])): ?>
                    <div class="space-y-2 min-w-0 max-w-sm">
                      <?php foreach ($od['items'] as $item): ?>
                        <div class="flex items-center justify-between gap-2.5 p-2 rounded-lg bg-stone-50/80 border border-stone-200/70">
                          <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            <img src="<?= htmlspecialchars($item['anh_chinh_url'] ?? resolveProductImage($item['anh_chinh'] ?? null)) ?>"
                                 alt="<?= htmlspecialchars($item['ten_sp']) ?>"
                                 class="w-10 h-12 rounded object-cover border border-stone-200 bg-white flex-shrink-0"
                                 loading="lazy" />
                            <div class="min-w-0 flex-1">
                              <p class="truncate text-xs font-semibold text-charcoal" title="<?= htmlspecialchars($item['ten_sp']) ?>">
                                <?= htmlspecialchars($item['ten_sp']) ?>
                              </p>
                              <p class="text-[11px] text-muted truncate">
                                <?= htmlspecialchars($item['mau_sac']) ?> &middot; Size <?= htmlspecialchars($item['kich_co']) ?> &middot; x<?= (int)$item['so_luong'] ?>
                              </p>
                            </div>
                          </div>

                          <div class="flex-shrink-0">
                            <?php if ($od['trang_thai_dh'] === 'HOAN_THANH'): ?>
                              <?php if ((int)$item['da_cong_diem_dg'] === 0): ?>
                                <button type="button"
                                  class="btn-open-review-modal tap-feedback inline-flex items-center gap-1 rounded-full bg-amber-500 hover:bg-amber-600 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm transition"
                                  data-ctdh="<?= (int)$item['ma_ctdh'] ?>"
                                  data-product-name="<?= htmlspecialchars($item['ten_sp']) ?>"
                                  data-variant="<?= htmlspecialchars($item['mau_sac'] . ' &middot; Size ' . $item['kich_co']) ?>">
                                  ⭐ Đánh giá (+10đ)
                                </button>
                              <?php else: ?>
                                <span class="inline-flex items-center gap-0.5 rounded-full bg-amber-50 border border-amber-200 px-1.5 py-0.5 text-[10px] font-medium text-amber-800" title="<?= htmlspecialchars($item['noi_dung_danh_gia'] ?? '') ?>">
                                  <?= str_repeat('★', (int)($item['so_sao_danh_gia'] ?: 5)) ?> <span class="text-[9px] text-amber-600">(+10đ)</span>
                                </span>
                              <?php endif; ?>
                            <?php endif; ?>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <span class="text-xs text-muted italic">Không có chi tiết</span>
                  <?php endif; ?>
                </td>
                <td class="whitespace-nowrap px-3 py-3 text-xs text-muted">
                  <?= formatDateVN($od['ngay_dat'], true) ?>
                </td>
                <td class="whitespace-nowrap px-3 py-3 text-right">
                  <div class="font-semibold text-charcoal"><?= formatVND($od['thanh_tien']) ?></div>
                  <?php if ((float)$od['tong_tien_hang'] > (float)$od['thanh_tien']): ?>
                    <div class="text-[11px] text-muted line-through"><?= formatVND($od['tong_tien_hang']) ?></div>
                  <?php endif; ?>
                  <?php if ((int)($od['diem_cong_du_kien'] ?? 0) > 0 && $od['trang_thai_dh'] !== 'DA_HUY'): ?>
                    <div id="order-points-<?= (int)$od['ma_dh'] ?>" class="text-[11px] text-emerald-700 font-medium">+<?= (int)$od['diem_cong_du_kien'] ?> điểm</div>
                  <?php endif; ?>
                </td>
                <td class="whitespace-nowrap px-3 py-3 text-center">
                  <?php
                    $stBadge = $orderStatusBadges[$stKey] ?? ['label' => $stKey, 'class' => 'bg-stone-100 text-charcoal'];
                  ?>
                  <span class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold <?= $stBadge['class'] ?>" id="order-badge-<?= (int)$od['ma_dh'] ?>">
                    <?= htmlspecialchars($stBadge['label']) ?>
                  </span>
                </td>
                <td class="whitespace-nowrap px-3 py-3 text-center">
                  <button type="button"
                    class="btn-open-order-detail tap-feedback inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-stone-300 bg-white text-xs font-medium text-stone-700 hover:bg-stone-100 transition shadow-sm"
                    data-order-id="<?= (int)$od['ma_dh'] ?>">
                    <svg class="w-3.5 h-3.5 text-stone-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Chi tiết
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </section>
</div>

<!-- Dữ liệu JSON các đơn hàng gắn sẵn cho Modal Chi tiết đơn hàng -->
<script id="customerOrdersJson" type="application/json">
<?= json_encode($customerOrders, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>
</script>

<!-- Modal Chi tiết đơn hàng của khách hàng (Yêu cầu 3) -->
<div id="customerOrderDetailModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-stone-900/60 backdrop-blur-sm p-4 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="modalDetailOrderTitle">
  <div class="relative w-full max-w-2xl my-8 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl flex flex-col max-h-[90vh]">
    <!-- Modal Header -->
    <div class="flex items-start justify-between border-b border-stone-100 px-6 py-4 bg-stone-50/70">
      <div>
        <div class="flex items-center gap-2.5">
          <h3 id="modalDetailOrderTitle" class="text-lg font-bold text-charcoal">#DH00</h3>
          <span id="modalDetailStatusBadge" class="rounded-full px-2.5 py-0.5 text-xs font-semibold bg-stone-100 text-charcoal">
            Chờ xác nhận
          </span>
        </div>
        <p class="mt-1 text-xs text-muted">
          Đặt lúc: <span id="modalDetailOrderDate" class="font-medium text-stone-600">--</span>
        </p>
      </div>
      <button type="button" id="closeOrderDetailModalBtn" aria-label="Đóng" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-200/60 hover:text-charcoal transition">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round" /></svg>
      </button>
    </div>

    <!-- Modal Body (Scrollable) -->
    <div class="p-6 overflow-y-auto space-y-6 flex-1 text-sm">
      <!-- Thông tin nhận hàng -->
      <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4 space-y-2.5">
        <h4 class="text-xs font-bold uppercase tracking-wider text-charcoal flex items-center gap-1.5">
          <svg class="w-4 h-4 text-stone-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
          </svg>
          Thông tin nhận hàng
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-1 border-t border-stone-200/60">
          <div>
            <span class="text-muted">Người nhận:</span>
            <span id="modalDetailReceiverName" class="font-semibold text-charcoal ml-1">--</span>
          </div>
          <div>
            <span class="text-muted">Số điện thoại:</span>
            <span id="modalDetailReceiverPhone" class="font-semibold text-charcoal ml-1">--</span>
          </div>
          <div class="sm:col-span-2">
            <span class="text-muted">Địa chỉ giao:</span>
            <span id="modalDetailReceiverAddress" class="text-stone-700 ml-1 leading-relaxed">--</span>
          </div>
          <div class="sm:col-span-2">
            <span class="text-muted">Phương thức thanh toán:</span>
            <span id="modalDetailPaymentMethod" class="inline-flex items-center gap-1 font-medium text-stone-800 ml-1">
              --
            </span>
          </div>
        </div>
      </div>

      <!-- Danh sách sản phẩm đã đặt -->
      <div>
        <h4 class="text-xs font-bold uppercase tracking-wider text-charcoal mb-3 flex items-center justify-between">
          <span>Sản phẩm đã đặt</span>
          <span id="modalDetailItemCount" class="text-xs font-normal text-muted">(0 món)</span>
        </h4>
        <div id="modalDetailItemsList" class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
          <!-- Rendered dynamically -->
        </div>
      </div>

      <!-- Tổng kết thanh toán -->
      <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4 space-y-2 text-xs">
        <h4 class="text-xs font-bold uppercase tracking-wider text-charcoal mb-2">Chi tiết thanh toán</h4>
        <div class="flex justify-between text-muted">
          <span>Tạm tính tiền hàng:</span>
          <span id="modalDetailSubtotal" class="font-medium text-charcoal">0₫</span>
        </div>
        <div class="flex justify-between text-muted">
          <span>Giảm giá Hạng thành viên:</span>
          <span id="modalDetailTierDiscount" class="font-medium text-olive">-0₫</span>
        </div>
        <div class="flex justify-between text-muted">
          <span>Giảm giá Voucher &amp; Tiêu điểm:</span>
          <span id="modalDetailVoucherDiscount" class="font-medium text-accent">-0₫</span>
        </div>
        <div class="flex justify-between text-muted">
          <span>Phí vận chuyển:</span>
          <span id="modalDetailShippingFee" class="font-medium text-charcoal">Miễn phí</span>
        </div>
        <div class="flex justify-between items-baseline pt-2.5 border-t border-stone-200 text-sm font-semibold text-charcoal">
          <span>Tổng thanh toán thực trả:</span>
          <span id="modalDetailTotal" class="text-base font-bold text-charcoal">0₫</span>
        </div>
      </div>
    </div>

    <!-- Modal Footer -->
    <div class="flex items-center justify-between border-t border-stone-100 px-6 py-4 bg-stone-50/70">
      <div>
        <button type="button" id="modalDetailCancelBtn"
          class="hidden tap-feedback items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition">
          <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
          </svg>
          Hủy đơn hàng
        </button>
      </div>
      <button type="button" id="modalDetailCloseFooterBtn"
        class="tap-feedback rounded-lg border border-stone-300 bg-white hover:bg-stone-100 px-5 py-2 text-xs font-semibold text-stone-700 shadow-sm transition">
        Đóng
      </button>
    </div>
  </div>
</div>

<!-- Modal Đánh giá sản phẩm nhận thưởng nóng +10 điểm (Mục 1.4) -->
<div id="loyaltyReviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-stone-900/60 backdrop-blur-sm px-4 py-8" role="dialog" aria-modal="true" aria-labelledby="loyaltyReviewModalTitle">
  <div class="max-h-full w-full max-w-lg overflow-y-auto rounded-2xl border border-stone-200 bg-white shadow-2xl">
    <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-6 py-5">
      <div>
        <span class="inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
          ⭐ Thưởng nóng +10 Điểm
        </span>
        <h3 id="loyaltyReviewModalTitle" class="mt-1 text-lg font-semibold text-charcoal">Đánh giá sản phẩm</h3>
      </div>
      <button type="button" id="closeLoyaltyReviewModalBtn" aria-label="Đóng" class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-charcoal transition">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round" /></svg>
      </button>
    </div>

    <form id="loyaltyReviewForm" class="space-y-5 p-6">
      <input type="hidden" id="modalReviewCtdh" name="ma_ctdh" value="" />
      <input type="hidden" id="modalReviewStars" name="so_sao" value="5" />

      <!-- Thông tin sản phẩm -->
      <div class="rounded-xl bg-stone-50 p-4 border border-stone-200">
        <p id="modalReviewProductName" class="text-sm font-semibold text-charcoal"></p>
        <p id="modalReviewVariant" class="mt-0.5 text-xs text-muted"></p>
      </div>

      <!-- Chọn số sao (1-5 sao) -->
      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-2">Đánh giá chất lượng sản phẩm</label>
        <div id="modalStarRatingPicker" class="flex items-center gap-1.5 text-3xl cursor-pointer select-none">
          <span class="star-rating-item text-amber-400 hover:scale-110 transition-transform" data-star="1">★</span>
          <span class="star-rating-item text-amber-400 hover:scale-110 transition-transform" data-star="2">★</span>
          <span class="star-rating-item text-amber-400 hover:scale-110 transition-transform" data-star="3">★</span>
          <span class="star-rating-item text-amber-400 hover:scale-110 transition-transform" data-star="4">★</span>
          <span class="star-rating-item text-amber-400 hover:scale-110 transition-transform" data-star="5">★</span>
          <span id="starRatingLabel" class="ml-2 text-xs font-medium text-stone-600">5 sao (Rất hài lòng)</span>
        </div>
      </div>

      <!-- Nội dung nhận xét -->
      <div>
        <label for="modalReviewContent" class="block text-xs font-semibold uppercase tracking-wider text-charcoal mb-1">
          Nội dung nhận xét <span class="text-rose-600">*</span>
        </label>
        <textarea id="modalReviewContent" name="noi_dung_danh_gia" rows="3" required
          placeholder="Hãy chia sẻ cảm nhận về chất liệu, form dáng và chất lượng sản phẩm..."
          class="w-full rounded-xl border border-stone-300 p-3 text-sm text-charcoal placeholder:text-stone-400 focus:border-charcoal focus:outline-none focus:ring-1 focus:ring-charcoal"></textarea>
      </div>

      <div class="flex items-center justify-end gap-3 border-t border-stone-100 pt-4">
        <button type="button" id="cancelLoyaltyReviewModalBtn" class="rounded-full border border-stone-300 px-5 py-2.5 text-xs font-semibold text-stone-600 hover:bg-stone-50">Hủy</button>
        <button type="submit" id="btnSubmitLoyaltyReview" class="tap-feedback inline-flex items-center gap-2 rounded-full bg-charcoal px-6 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-accent transition">
          Gửi đánh giá &amp; Nhận +10 điểm
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  (() => {
    // 1. Xử lý đổi điểm lấy Voucher thật qua API redeem_voucher.php
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-redeem-btn]');
      if (!btn) return;

      const maPgg = btn.dataset.redeemBtn;
      const code = btn.dataset.voucherCode || 'VOUCHER';
      const pointsNeeded = Number(btn.dataset.pointsNeeded || 0);

      if (!confirm(`Xác nhận dùng ${pointsNeeded.toLocaleString('vi-VN')} điểm khả dụng để đổi Voucher "${code}"?`)) {
        return;
      }

      btn.disabled = true;
      const originalHtml = btn.innerHTML;
      btn.innerHTML = `
        <span class="inline-flex items-center gap-1.5">
          <svg class="h-3.5 w-3.5 animate-spin text-white" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
          </svg>
          Đang đổi voucher...
        </span>
      `;

      try {
        const res = await fetch('api/redeem_voucher.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ma_pgg: Number(maPgg) })
        });
        const data = await res.json();

        if (data.require_login) {
          if (window.openAuthModal) {
            window.openAuthModal('login');
          } else {
            window.location.href = 'login.php';
          }
          btn.disabled = false;
          btn.innerHTML = originalHtml;
          return;
        }

        if (data.success) {
          if (window.showStoreToast) {
            window.showStoreToast('🎉 ' + data.message);
          }

          // Cập nhật số điểm khả dụng trên giao diện
          const newPoints = data.data?.diem_kha_dung_moi;
          if (newPoints !== undefined) {
            const ptsEl = document.getElementById('loyaltyAvailablePoints');
            if (ptsEl) {
              ptsEl.innerHTML = `${Number(newPoints).toLocaleString('vi-VN')} <span class="text-base font-normal text-muted">điểm</span>`;
            }
            // Cập nhật điểm trên Header Popover nếu có
            const tierBtn = document.getElementById('tierBtn');
            if (tierBtn) {
              const pointsSpan = tierBtn.querySelector('.tabular-nums');
              if (pointsSpan) pointsSpan.textContent = Number(newPoints).toLocaleString('vi-VN') + ' điểm';
            }
          }

          // Chuyển trạng thái nút thành "Đã sở hữu" / link dùng ngay
          const parentDiv = btn.parentElement;
          if (parentDiv) {
            parentDiv.innerHTML = `
              <div class="flex items-center gap-2">
                <span class="inline-block rounded-full bg-stone-100 px-3 py-2 text-center text-xs font-semibold text-stone-600 flex-1">
                  ✓ Đã sở hữu
                </span>
                <a href="index.php?page=checkout" class="tap-feedback rounded-full bg-olive px-3 py-2 text-center text-xs font-semibold text-white hover:opacity-95 shadow-sm">
                  Dùng &rarr;
                </a>
              </div>
            `;
          }

          // Tải lại sau 1.2s để đồng bộ toàn bộ thẻ và lịch sử điểm
          setTimeout(() => {
            window.location.reload();
          }, 1200);

        } else {
          alert(data.message || 'Đổi voucher không thành công.');
          btn.disabled = false;
          btn.innerHTML = originalHtml;
        }
      } catch (err) {
        console.error(err);
        alert('Lỗi kết nối khi gửi yêu cầu đổi voucher.');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
      }
    });

    // 2. Xử lý Modal Đánh giá sản phẩm nhận +10 điểm thưởng nóng (Mục 1.4)
    const reviewModal = document.getElementById('loyaltyReviewModal');
    const reviewForm = document.getElementById('loyaltyReviewForm');
    const inputCtdh = document.getElementById('modalReviewCtdh');
    const inputStars = document.getElementById('modalReviewStars');
    const starLabel = document.getElementById('starRatingLabel');
    const starItems = Array.from(document.querySelectorAll('.star-rating-item'));
    const btnSubmitReview = document.getElementById('btnSubmitLoyaltyReview');

    const starDescriptions = {
      1: '1 sao (Tệ)',
      2: '2 sao (Chưa ưng ý)',
      3: '3 sao (Bình thường)',
      4: '4 sao (Hài lòng)',
      5: '5 sao (Rất hài lòng)'
    };

    function setStarRating(rating) {
      if (inputStars) inputStars.value = rating;
      if (starLabel) starLabel.textContent = starDescriptions[rating] || (rating + ' sao');
      starItems.forEach(item => {
        const val = Number(item.dataset.star);
        if (val <= rating) {
          item.classList.add('text-amber-400');
          item.classList.remove('text-stone-300');
        } else {
          item.classList.remove('text-amber-400');
          item.classList.add('text-stone-300');
        }
      });
    }

    starItems.forEach(item => {
      item.addEventListener('click', () => {
        setStarRating(Number(item.dataset.star));
      });
    });

    function openReviewModal(ctdh, spName, variant) {
      if (inputCtdh) inputCtdh.value = ctdh;
      const nameEl = document.getElementById('modalReviewProductName');
      const varEl = document.getElementById('modalReviewVariant');
      const contentEl = document.getElementById('modalReviewContent');
      if (nameEl) nameEl.textContent = spName;
      if (varEl) varEl.innerHTML = variant;
      if (contentEl) contentEl.value = '';
      setStarRating(5);

      if (reviewModal) {
        reviewModal.classList.remove('hidden');
        reviewModal.classList.add('flex');
      }
    }

    function closeReviewModal() {
      if (reviewModal) {
        reviewModal.classList.add('hidden');
        reviewModal.classList.remove('flex');
      }
    }

    document.getElementById('closeLoyaltyReviewModalBtn')?.addEventListener('click', closeReviewModal);
    document.getElementById('cancelLoyaltyReviewModalBtn')?.addEventListener('click', closeReviewModal);

    reviewModal?.addEventListener('click', (e) => {
      if (e.target === reviewModal) closeReviewModal();
    });

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-open-review-modal');
      if (btn) {
        openReviewModal(
          btn.dataset.ctdh,
          btn.dataset.productName || 'Sản phẩm',
          btn.dataset.variant || ''
        );
      }
    });

    reviewForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const ctdh = Number(inputCtdh?.value || 0);
      const stars = Number(inputStars?.value || 5);
      const content = (document.getElementById('modalReviewContent')?.value || '').trim();

      if (!content) {
        alert('Vui lòng nhập nội dung đánh giá của bạn.');
        return;
      }

      if (btnSubmitReview) {
        btnSubmitReview.disabled = true;
        const originalHtml = btnSubmitReview.innerHTML;
        btnSubmitReview.innerHTML = `
          <svg class="h-3.5 w-3.5 animate-spin text-white inline-block mr-1" viewBox="0 0 24 24" fill="none">
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
              ma_ctdh: ctdh,
              so_sao: stars,
              noi_dung_danh_gia: content
            })
          });
          const data = await res.json();

          if (data.success) {
            closeReviewModal();
            if (window.showStoreToast) {
              window.showStoreToast('🎉 ' + data.message);
            } else {
              alert(data.message);
            }

            // Cập nhật số điểm khả dụng trên giao diện
            const newPoints = data.diem_kha_dung;
            if (newPoints !== undefined) {
              const ptsEl = document.getElementById('loyaltyAvailablePoints');
              if (ptsEl) {
                ptsEl.innerHTML = `${Number(newPoints).toLocaleString('vi-VN')} <span class="text-base font-normal text-muted">điểm</span>`;
              }
              const tierBtn = document.getElementById('tierBtn');
              if (tierBtn) {
                const pointsSpan = tierBtn.querySelector('.tabular-nums');
                if (pointsSpan) pointsSpan.textContent = Number(newPoints).toLocaleString('vi-VN') + ' điểm';
              }
            }

            // Tải lại sau 1.2s để cập nhật đồng bộ bảng đơn và lịch sử giao dịch điểm
            setTimeout(() => {
              window.location.reload();
            }, 1200);

          } else {
            alert(data.message || 'Gửi đánh giá không thành công.');
            btnSubmitReview.disabled = false;
            btnSubmitReview.innerHTML = originalHtml;
          }
        } catch (err) {
          console.error(err);
          alert('Lỗi kết nối khi gửi yêu cầu đánh giá.');
          btnSubmitReview.disabled = false;
          btnSubmitReview.innerHTML = originalHtml;
        }
      }
    });

    // 3. Xử lý Modal Chi tiết đơn hàng & Khách hàng tự hủy đơn hàng (Yêu cầu 2 & 3)

    // Bảng nhãn trạng thái đơn hàng (mirror từ PHP $orderStatusBadges)
    const orderStatusBadges = {
      'CHO_XAC_NHAN': { label: 'Chờ xác nhận', class: 'bg-amber-100 text-amber-800' },
      'DANG_GIAO':    { label: 'Đang giao',     class: 'bg-blue-100 text-blue-800' },
      'HOAN_THANH':   { label: 'Hoàn thành',    class: 'bg-emerald-100 text-emerald-800' },
      'DA_HUY':       { label: 'Đã hủy',         class: 'bg-rose-100 text-rose-700' },
    };

    function formatVND(amount) {
      return Number(amount || 0).toLocaleString('vi-VN') + '₫';
    }

    function formatDateVN(dateStr) {
      if (!dateStr) return '--';
      const d = new Date(dateStr.replace(' ', 'T'));
      if (isNaN(d.getTime())) return dateStr;
      const pad = (n) => String(n).padStart(2, '0');
      return `${pad(d.getHours())}:${pad(d.getMinutes())} ${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    // Đọc dữ liệu đơn hàng gắn sẵn
    const rawOrdersJson = document.getElementById('customerOrdersJson')?.textContent;
    let customerOrdersList = [];
    try {
      customerOrdersList = rawOrdersJson ? JSON.parse(rawOrdersJson) : [];
    } catch (e) {
      console.error('Không thể đọc dữ liệu JSON đơn hàng:', e);
    }
    const customerOrdersMap = {};
    customerOrdersList.forEach(od => {
      customerOrdersMap[od.ma_dh] = od;
    });

    const orderDetailModal = document.getElementById('customerOrderDetailModal');
    const closeOrderDetailModalBtn = document.getElementById('closeOrderDetailModalBtn');
    const modalDetailCloseFooterBtn = document.getElementById('modalDetailCloseFooterBtn');
    const modalCancelBtn = document.getElementById('modalDetailCancelBtn');
    let currentDetailOrderId = null;

    function closeCustomerOrderDetailModal() {
      if (orderDetailModal) {
        orderDetailModal.classList.add('hidden');
        orderDetailModal.classList.remove('flex');
      }
      currentDetailOrderId = null;
    }

    if (closeOrderDetailModalBtn) closeOrderDetailModalBtn.addEventListener('click', closeCustomerOrderDetailModal);
    if (modalDetailCloseFooterBtn) modalDetailCloseFooterBtn.addEventListener('click', closeCustomerOrderDetailModal);
    if (orderDetailModal) {
      orderDetailModal.addEventListener('click', (e) => {
        if (e.target === orderDetailModal) {
          closeCustomerOrderDetailModal();
        }
      });
    }
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && orderDetailModal && !orderDetailModal.classList.contains('hidden')) {
        closeCustomerOrderDetailModal();
      }
    });

    window.openCustomerOrderDetail = function(orderId) {
      const order = customerOrdersMap[orderId];
      if (!order) {
        alert('Không tìm thấy dữ liệu chi tiết của đơn hàng này.');
        return;
      }

      currentDetailOrderId = Number(orderId);
      const dhCode = order.ma_dh_code || ('DH' + String(orderId).padStart(2, '0'));

      // 1. Header Modal: Mã đơn hàng, Ngày đặt, Badge trạng thái
      const titleEl = document.getElementById('modalDetailOrderTitle');
      const dateEl = document.getElementById('modalDetailOrderDate');
      const badgeEl = document.getElementById('modalDetailStatusBadge');

      if (titleEl) titleEl.textContent = '#' + dhCode;
      if (dateEl) dateEl.textContent = formatDateVN(order.ngay_dat);

      const stKey = order.trang_thai_dh || 'CHO_XAC_NHAN';
      const stBadge = orderStatusBadges[stKey] || { label: stKey, class: 'bg-stone-100 text-charcoal' };
      if (badgeEl) {
        badgeEl.className = 'rounded-full px-2.5 py-0.5 text-xs font-semibold ' + stBadge.class;
        badgeEl.textContent = stBadge.label;
      }

      // 2. Thông tin nhận hàng
      const receiverNameEl = document.getElementById('modalDetailReceiverName');
      const receiverPhoneEl = document.getElementById('modalDetailReceiverPhone');
      const receiverAddressEl = document.getElementById('modalDetailReceiverAddress');
      const paymentMethodEl = document.getElementById('modalDetailPaymentMethod');

      if (receiverNameEl) receiverNameEl.textContent = order.ten_nguoi_nhan || order.ten_khach_hang || '--';
      if (receiverPhoneEl) receiverPhoneEl.textContent = order.sdt_nhan || order.sdt_khach_hang || '--';
      if (receiverAddressEl) receiverAddressEl.textContent = order.dia_chi_giao_hang || 'Theo địa chỉ đăng ký tài khoản';

      let ptText = 'Thanh toán khi nhận hàng (COD)';
      if (order.pt_thanh_toan === 'CHUYEN_KHOAN') {
        ptText = 'Chuyển khoản ngân hàng';
      }
      if (paymentMethodEl) paymentMethodEl.textContent = ptText;

      // 3. Danh sách sản phẩm đã đặt
      const itemsListEl = document.getElementById('modalDetailItemsList');
      const itemCountEl = document.getElementById('modalDetailItemCount');
      const items = Array.isArray(order.items) ? order.items : [];
      if (itemCountEl) itemCountEl.textContent = `(${items.length} món)`;

      if (itemsListEl) {
        if (items.length === 0) {
          itemsListEl.innerHTML = '<p class="text-xs text-muted italic p-3 text-center">Không có sản phẩm nào trong đơn hàng.</p>';
        } else {
          itemsListEl.innerHTML = items.map(item => {
            const donGia = Number(item.don_gia_luc_mua || 0);
            const thanhTien = Number(item.thanh_tien_muc || (donGia * Number(item.so_luong || 1)));
            const imgUrl = item.anh_chinh_url || 'images/default_product.png';

            let reviewHtml = '';
            if (stKey === 'HOAN_THANH') {
              if (Number(item.da_cong_diem_dg) === 0) {
                reviewHtml = `
                  <button type="button"
                    class="btn-open-review-modal tap-feedback inline-flex items-center gap-1 rounded-full bg-amber-500 hover:bg-amber-600 px-2.5 py-1 text-[11px] font-semibold text-white shadow-sm transition mt-1"
                    data-ctdh="${item.ma_ctdh}"
                    data-product-name="${escapeHtml(item.ten_sp || '')}"
                    data-variant="${escapeHtml((item.mau_sac || '') + ' · Size ' + (item.kich_co || ''))}">
                    ⭐ Đánh giá (+10đ)
                  </button>
                `;
              } else {
                const stars = Number(item.so_sao_danh_gia || 5);
                reviewHtml = `
                  <span class="inline-flex items-center gap-0.5 rounded-full bg-amber-50 border border-amber-200 px-1.5 py-0.5 text-[10px] font-medium text-amber-800 mt-1" title="${escapeHtml(item.noi_dung_danh_gia || '')}">
                    ${'★'.repeat(stars)} <span class="text-[9px] text-amber-600">(+10đ)</span>
                  </span>
                `;
              }
            }

            return `
              <div class="flex items-center justify-between gap-3 p-2.5 rounded-xl bg-stone-50/80 border border-stone-200/70">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                  <img src="${imgUrl}" alt="${escapeHtml(item.ten_sp || '')}" class="w-12 h-14 rounded-lg object-cover border border-stone-200 bg-white flex-shrink-0" />
                  <div class="min-w-0 flex-1">
                    <p class="font-semibold text-charcoal text-xs truncate" title="${escapeHtml(item.ten_sp || '')}">${escapeHtml(item.ten_sp || '')}</p>
                    <p class="text-[11px] text-muted mt-0.5">${escapeHtml(item.mau_sac || '')} &middot; Size ${escapeHtml(item.kich_co || '')} &middot; x${item.so_luong}</p>
                    <p class="text-[11px] text-stone-500 mt-0.5">Đơn giá: ${formatVND(donGia)}</p>
                    ${reviewHtml}
                  </div>
                </div>
                <div class="text-right flex-shrink-0">
                  <p class="font-semibold text-xs text-charcoal">${formatVND(thanhTien)}</p>
                </div>
              </div>
            `;
          }).join('');
        }
      }

      // 4. Tổng kết thanh toán
      const tongTienHang = Number(order.tong_tien_hang || 0);
      const giamHangTv = Number(order.tien_giam_hang_tv || 0);
      const giamVoucherDiem = Number(order.tien_giam_voucher || 0) + (Number(order.diem_su_dung || 0) * 1000);
      const phiShip = Number(order.phi_van_chuyen || 0);
      const thanhTien = Number(order.thanh_tien || 0);

      document.getElementById('modalDetailSubtotal').textContent = formatVND(tongTienHang);
      document.getElementById('modalDetailTierDiscount').textContent = `-${formatVND(giamHangTv)}`;
      document.getElementById('modalDetailVoucherDiscount').textContent = `-${formatVND(giamVoucherDiem)}`;
      document.getElementById('modalDetailShippingFee').textContent = phiShip > 0 ? formatVND(phiShip) : 'Miễn phí';

      // Tổng thanh toán + điểm tích lũy dự kiến / đã nhận
      const diemCongDuKien = Number(order.diem_cong_du_kien || 0);
      const totalEl = document.getElementById('modalDetailTotal');
      if (totalEl) {
        if (diemCongDuKien > 0 && stKey !== 'DA_HUY') {
          totalEl.innerHTML = `${formatVND(thanhTien)} <span class="ml-1 text-xs font-medium text-emerald-700">+${diemCongDuKien.toLocaleString('vi-VN')} điểm</span>`;
        } else {
          totalEl.textContent = formatVND(thanhTien);
        }
      }

      // 5. Nút "Hủy đơn hàng" bên trong Modal
      if (modalCancelBtn) {
        if (stKey === 'CHO_XAC_NHAN') {
          modalCancelBtn.classList.remove('hidden');
          modalCancelBtn.classList.add('inline-flex');
        } else {
          modalCancelBtn.classList.add('hidden');
          modalCancelBtn.classList.remove('inline-flex');
        }
      }

      // Mở modal
      if (orderDetailModal) {
        orderDetailModal.classList.remove('hidden');
        orderDetailModal.classList.add('flex');
      }
    };

    // Bắt sự kiện bấm nút "Chi tiết" ở bảng đơn hàng
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-open-order-detail');
      if (btn) {
        const orderId = btn.dataset.orderId;
        if (orderId) {
          window.openCustomerOrderDetail(orderId);
        }
      }
    });

    // Bắt sự kiện bấm nút "Hủy đơn hàng" bên trong Modal
    if (modalCancelBtn) {
      modalCancelBtn.addEventListener('click', async () => {
        if (!currentDetailOrderId) return;
        const order = customerOrdersMap[currentDetailOrderId];
        if (!order) return;
        const dhCode = order.ma_dh_code || ('DH' + String(currentDetailOrderId).padStart(2, '0'));

        if (!confirm(`Bạn có chắc chắn muốn hủy đơn hàng #${dhCode} không?\nTồn kho và Voucher (nếu có) sẽ được hoàn lại.`)) {
          return;
        }

        modalCancelBtn.disabled = true;
        const originalHtml = modalCancelBtn.innerHTML;
        modalCancelBtn.innerHTML = `
          <svg class="h-3.5 w-3.5 animate-spin text-white inline-block mr-1" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
          </svg>
          Đang hủy...
        `;

        try {
          const res = await fetch('api/cancel_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ma_dh: Number(currentDetailOrderId) })
          });
          const data = await res.json();

          if (data.require_login) {
            if (window.openAuthModal) {
              window.openAuthModal('login');
            } else {
              window.location.href = 'login.php';
            }
            modalCancelBtn.disabled = false;
            modalCancelBtn.innerHTML = originalHtml;
            return;
          }

          if (data.success) {
            if (window.showStoreToast) {
              window.showStoreToast('✓ ' + data.message);
            } else {
              alert(data.message);
            }

            // 1. Cập nhật dữ liệu trong bộ nhớ
            order.trang_thai_dh = 'DA_HUY';

            // 2. Cập nhật Badge trên Modal
            const modalBadge = document.getElementById('modalDetailStatusBadge');
            if (modalBadge) {
              modalBadge.className = 'rounded-full px-2.5 py-0.5 text-xs font-semibold bg-rose-100 text-rose-700';
              modalBadge.textContent = 'Đã hủy';
            }

            // 3. Ẩn nút Hủy đơn trên Modal
            modalCancelBtn.classList.add('hidden');
            modalCancelBtn.classList.remove('inline-flex');
            modalCancelBtn.disabled = false;
            modalCancelBtn.innerHTML = originalHtml;

            // 4. Cập nhật Badge trên bảng danh sách bên ngoài
            const tableBadge = document.getElementById(`order-badge-${currentDetailOrderId}`);
            if (tableBadge) {
              tableBadge.className = 'inline-block rounded-full px-2.5 py-1 text-xs font-semibold bg-rose-100 text-rose-700';
              tableBadge.textContent = 'Đã hủy';
            }
            const pointsEl = document.getElementById(`order-points-${currentDetailOrderId}`);
            if (pointsEl) {
              pointsEl.remove();
            }

            // 5. Reload sau 1.2s để đồng bộ lại điểm và ví voucher trên toàn giao diện
            setTimeout(() => {
              window.location.reload();
            }, 1200);

          } else {
            alert(data.message || 'Không thể hủy đơn hàng.');
            modalCancelBtn.disabled = false;
            modalCancelBtn.innerHTML = originalHtml;
          }
        } catch (err) {
          console.error(err);
          alert('Lỗi kết nối khi gửi yêu cầu hủy đơn hàng.');
          modalCancelBtn.disabled = false;
          modalCancelBtn.innerHTML = originalHtml;
        }
      });
    }

    // Helper tương thích cho các lệnh gọi cũ
    window.cancelCustomerOrder = async function(maDh, dhCode) {
      if (customerOrdersMap[maDh]) {
        window.openCustomerOrderDetail(maDh);
      }
    };
  })();
</script>

