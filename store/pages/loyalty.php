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
            <p class="text-xs uppercase tracking-wider text-stone-300">Chủ thẻ (ma_nd = <?= (int)($loyaltyProfile['ma_nd'] ?? 0) ?>)</p>
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
          <p class="tabular-nums mt-2 text-3xl font-semibold text-olive">
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
        <h2 id="voucherTitle" class="text-xl font-semibold">Kho Đổi điểm nhận Voucher (Từ CSDL <code>pgg_phieu_giam_gia</code>)</h2>
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
        ?>
        <article class="fade-up relative flex flex-col justify-between rounded-xl border border-dashed border-stone-300 bg-white p-6 shadow-sm <?= !$v['is_tier_eligible'] ? 'opacity-75' : '' ?>"
          style="--stagger: <?= $idx ?>">
          <div>
            <div class="flex items-center justify-between gap-2">
              <span class="text-xs font-medium uppercase tracking-wider text-accent"><?= htmlspecialchars($v['ten_hang_yeu_cau']) ?></span>
              <span class="tabular-nums text-xs text-muted">Còn <?= (int)$v['so_luong'] ?> mã</span>
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
            <?php elseif (!$v['is_tier_eligible']): ?>
              <button type="button" disabled class="w-full rounded-full bg-stone-200 px-4 py-2.5 text-xs font-medium text-muted cursor-not-allowed">
                Yêu cầu từ <?= htmlspecialchars($v['ten_hang_yeu_cau']) ?>
              </button>
            <?php elseif (!$v['is_points_eligible']): ?>
              <button type="button" disabled class="w-full rounded-full bg-stone-200 px-4 py-2.5 text-xs font-medium text-muted cursor-not-allowed">
                Thiếu <?= (int)$v['diem_can_doi'] - $diemKhaDung ?> điểm khả dụng
              </button>
            <?php else: ?>
              <button type="button" data-dev-feature="Đổi <?= (int)$v['diem_can_doi'] ?> điểm lấy mã <?= htmlspecialchars($v['ma_code']) ?> vào bảng vkh_vi_voucher_kh"
                class="tap-feedback w-full rounded-full bg-charcoal px-4 py-2.5 text-xs font-medium text-white hover:bg-accent">
                Đổi ngay (-<?= (int)$v['diem_can_doi'] ?> điểm) &middot; Đang phát triển
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
        <h2 id="ledgerTitle" class="text-xl font-semibold">Lịch sử biến động điểm (Bảng <code>lsd_lich_su_diem</code>)</h2>
        <p class="mt-1 text-sm text-muted">Sổ cái đối soát từng điểm thưởng cộng vào và trừ ra của thẻ <strong><?= htmlspecialchars($maSoThe) ?></strong>.</p>
      </div>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl bg-white shadow-sm">
      <?php if (empty($pointLedger)): ?>
        <div class="p-10 text-center">
          <p class="text-base font-medium text-charcoal">Chưa có phát sinh giao dịch điểm nào trong CSDL</p>
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

  <!-- Danh sách Đơn đặt hàng của khách hàng từ bảng dh_don_hang -->
  <section aria-labelledby="myOrdersTitle">
    <h2 id="myOrdersTitle" class="text-xl font-semibold">Đơn hàng của <?= htmlspecialchars($hoTen) ?> (Bảng <code>dh_don_hang</code>)</h2>
    <div class="mt-6 overflow-x-auto rounded-xl bg-white shadow-sm">
      <?php if (empty($customerOrders)): ?>
        <div class="p-10 text-center text-sm text-muted">Khách hàng chưa có đơn đặt hàng nào trong CSDL.</div>
      <?php else: ?>
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-stone-200 text-xs uppercase tracking-wider text-muted">
            <tr>
              <th class="px-6 py-4 font-medium">Mã đơn</th>
              <th class="px-6 py-4 font-medium">Ngày đặt</th>
              <th class="px-6 py-4 text-right font-medium">Tiền hàng</th>
              <th class="px-6 py-4 text-right font-medium">Giảm Hạng TV</th>
              <th class="px-6 py-4 text-right font-medium">Giảm Voucher/Điểm</th>
              <th class="px-6 py-4 text-right font-medium">Thực trả</th>
              <th class="px-6 py-4 text-right font-medium">Điểm tích lũy</th>
              <th class="px-6 py-4 font-medium">Trạng thái</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-200 tabular-nums">
            <?php foreach ($customerOrders as $od): ?>
              <tr>
                <td class="whitespace-nowrap px-6 py-4 font-mono font-semibold text-charcoal">#DH<?= str_pad((string)$od['ma_dh'], 2, '0', STR_PAD_LEFT) ?></td>
                <td class="whitespace-nowrap px-6 py-4 text-muted"><?= formatDateVN($od['ngay_dat'], true) ?></td>
                <td class="whitespace-nowrap px-6 py-4 text-right"><?= formatVND($od['tong_tien_hang']) ?></td>
                <td class="whitespace-nowrap px-6 py-4 text-right text-olive">-<?= formatVND($od['tien_giam_hang_tv']) ?></td>
                <td class="whitespace-nowrap px-6 py-4 text-right text-accent">
                  -<?= formatVND((float)$od['tien_giam_voucher'] + ((int)$od['diem_su_dung'] * 1000)) ?>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-charcoal"><?= formatVND($od['thanh_tien']) ?></td>
                <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-emerald-700">+<?= (int)$od['diem_cong_du_kien'] ?> điểm</td>
                <td class="whitespace-nowrap px-6 py-4">
                  <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-charcoal"><?= htmlspecialchars($od['trang_thai_dh']) ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </section>
</div>

