<?php

/**
 * TRANG THANH TOÁN VIETQR ĐỘNG (thanh_toan_qr.php)
 * Phân hệ Giỏ hàng & Đặt hàng Đa tầng — Mục 2.3: Tích hợp mã QR Chuyển khoản
 * Cấu trúc URL VietQR chuẩn: https://img.vietqr.io/image/<BANK_ID>-<ACCOUNT_NO>-compact2.png?amount=<thanh_tien>&addInfo=<urlencode("#DH" + ma_dh)>&accountName=<urlencode(ACCOUNT_NAME)>
 */

require_once __DIR__ . '/config/db.php';

$currentUserId = getCurrentUserId();
$maDh = isset($_GET['ma_dh']) ? (int)$_GET['ma_dh'] : 0;

if ($maDh <= 0) {
    header('Location: index.php?page=loyalty#myOrdersTitle');
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT dh.*, nd.ho_ten, nd.sdt, nd.email
    FROM dh_don_hang dh
    INNER JOIN nd_nguoi_dung nd ON dh.ma_nd = nd.ma_nd
    WHERE dh.ma_dh = :ma_dh
");
$stmt->execute(['ma_dh' => $maDh]);
$order = $stmt->fetch();

if (!$order) {
    die('<div style="font-family:sans-serif;max-width:600px;margin:3rem auto;padding:2rem;background:#fef2f2;border:1px solid #f87171;border-radius:12px;color:#991b1b;text-align:center;">'
        . '<h2 style="margin-top:0;">Không tìm thấy đơn hàng</h2>'
        . '<p>Đơn hàng #' . htmlspecialchars((string)$maDh) . ' không tồn tại trong hệ thống.</p>'
        . '<a href="index.php" style="display:inline-block;margin-top:1rem;padding:0.6rem 1.5rem;background:#2d2d2d;color:#fff;border-radius:999px;text-decoration:none;">Về trang chủ</a>'
        . '</div>');
}

// Kiểm tra quyền sở hữu đơn hàng (chỉ chủ đơn hàng hoặc admin mới được xem)
if ($currentUserId <= 0 || ((int)$order['ma_nd'] !== $currentUserId && !hasAdminAccess())) {
    die('<div style="font-family:sans-serif;max-width:600px;margin:3rem auto;padding:2rem;background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;color:#92400e;text-align:center;">'
        . '<h2 style="margin-top:0;">Truy cập bị từ chối</h2>'
        . '<p>Bạn không có quyền truy cập vào thông tin thanh toán của đơn hàng này.</p>'
        . '<a href="index.php" style="display:inline-block;margin-top:1rem;padding:0.6rem 1.5rem;background:#2d2d2d;color:#fff;border-radius:999px;text-decoration:none;">Về trang chủ</a>'
        . '</div>');
}

$dhCode = 'DH' . str_pad((string)$order['ma_dh'], 2, '0', STR_PAD_LEFT);
$addInfo = '#DH' . str_pad((string)$order['ma_dh'], 2, '0', STR_PAD_LEFT);
$thanhTien = (float)$order['thanh_tien'];
$vietQrUrl = buildVietQRUrl($thanhTien, $order['ma_dh']);

// Kiểm tra nếu đơn hàng là COD
$isCOD = ($order['pt_thanh_toan'] === 'COD');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Thanh toán Đơn hàng #<?= htmlspecialchars($dhCode) ?> | AURA STUDIO</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['"Be Vietnam Pro"', 'system-ui', 'sans-serif'] },
          colors: {
            cream: '#FAF9F6',
            stone: { 100: '#F3F2EE', 200: '#E7E5E0' },
            charcoal: '#2D2D2D',
            muted: '#71717A',
            accent: '#8C6D53',
            olive: '#5F6F52',
            gold: '#F7F1E3',
          },
        },
      },
    };
  </script>
  <style>
    .tabular-nums { font-variant-numeric: tabular-nums; }
    .tap-feedback:active:not(:disabled) { transform: scale(0.97); }
    .tap-feedback { transition: transform 0.15s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.2s ease, color 0.2s ease; }
  </style>
</head>
<body class="bg-cream font-sans text-charcoal antialiased min-h-screen flex flex-col justify-between">

  <!-- Header Đơn giản -->
  <header class="border-b border-stone-200 bg-white/90 backdrop-blur sticky top-0 z-20">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6">
      <a href="index.php" class="text-sm font-semibold tracking-[0.25em] text-charcoal">AURA STUDIO</a>
      <div class="flex items-center gap-3">
        <a href="index.php?page=loyalty#myOrdersTitle" class="text-xs font-medium text-muted hover:text-charcoal transition">
          Đơn hàng của tôi
        </a>
        <span class="text-stone-300">&bull;</span>
        <span class="text-xs font-mono font-medium text-accent">#<?= htmlspecialchars($dhCode) ?></span>
      </div>
    </div>
  </header>

  <!-- Nội dung thanh toán VietQR -->
  <main class="mx-auto w-full max-w-3xl flex-1 px-4 py-10 sm:px-6">
    <?php if ($isCOD): ?>
      <div class="rounded-2xl bg-white p-8 shadow-sm text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
          <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" /></svg>
        </div>
        <h1 class="mt-4 text-2xl font-bold text-charcoal">Đơn hàng thanh toán khi nhận hàng (COD)</h1>
        <p class="mt-2 text-sm text-muted">Đơn hàng <strong class="font-mono text-charcoal">#<?= htmlspecialchars($dhCode) ?></strong> không yêu cầu chuyển khoản trước.</p>
        <div class="mt-6 inline-flex rounded-xl bg-stone-100 p-4 text-left text-sm">
          <span>Tổng tiền cần thanh toán khi nhận hàng: <strong class="text-charcoal"><?= formatVND($thanhTien) ?></strong></span>
        </div>
        <div class="mt-8">
          <a href="index.php?page=loyalty#myOrdersTitle" class="tap-feedback rounded-full bg-charcoal px-8 py-3 text-sm font-medium text-white hover:bg-accent transition">
            Xem đơn hàng của bạn &rarr;
          </a>
        </div>
      </div>
    <?php else: ?>
      <div id="qrMainCard" class="overflow-hidden rounded-2xl bg-white shadow-sm border border-stone-200">
        <!-- Banner Tiêu đề -->
        <div class="border-b border-stone-100 bg-stone-50 px-6 py-5 text-center sm:px-8">
          <span class="inline-block rounded-full bg-olive/10 px-3.5 py-1 text-xs font-semibold text-olive">
            Thanh toán VietQR 24/7 Tự động
          </span>
          <h1 class="mt-2 text-2xl font-bold text-charcoal">
            Chuyển khoản thanh toán Đơn hàng <span class="font-mono text-accent">#<?= htmlspecialchars($dhCode) ?></span>
          </h1>
          <p class="mt-1 text-xs text-muted">
            <?php
              $qrStatusMap = [
                  'CHO_XAC_NHAN' => 'Chờ xác nhận',
                  'DANG_GIAO'    => 'Đang giao hàng',
                  'HOAN_THANH'   => 'Hoàn thành',
                  'DA_HUY'       => 'Đã hủy',
              ];
              $vnOrderStatus = $qrStatusMap[$order['trang_thai_dh'] ?? ''] ?? ($order['trang_thai_dh'] ?? 'Chờ xác nhận');
            ?>
            Khách hàng: <strong><?= htmlspecialchars($order['ho_ten']) ?></strong> &middot; Trạng thái đơn: <span class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-800"><?= htmlspecialchars($vnOrderStatus) ?></span>
          </p>
        </div>

        <div class="grid gap-8 p-6 sm:p-8 md:grid-cols-2 items-center">
          <!-- Cột 1: Mã QR VietQR Quick Link -->
          <div class="flex flex-col items-center">
            <div class="relative overflow-hidden rounded-2xl border-2 border-stone-200 bg-white p-3 shadow-md">
              <img id="qrImage" src="<?= htmlspecialchars($vietQrUrl) ?>" alt="Mã VietQR Thanh toán" class="h-64 w-64 object-contain sm:h-72 sm:w-72" />
            </div>
            <p class="mt-3 text-center text-xs text-muted leading-relaxed">
              Quét mã bằng app ngân hàng bất kỳ để tự động điền đúng <strong>Số tiền</strong> và <strong>Nội dung</strong>.
            </p>
          </div>

          <!-- Cột 2: Bảng chi tiết thông tin chuyển khoản -->
          <div class="space-y-4">
            <div class="space-y-3 rounded-xl bg-cream p-5 text-xs sm:text-sm">
              <div class="flex items-center justify-between">
                <span class="text-muted">Ngân hàng:</span>
                <span class="font-semibold text-charcoal"><?= htmlspecialchars(VIETQR_BANK_NAME) ?></span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-muted">Số tài khoản:</span>
                <div class="flex items-center gap-2">
                  <span id="txtAccountNo" class="font-mono font-bold text-charcoal text-base"><?= htmlspecialchars(VIETQR_ACCOUNT_NO) ?></span>
                  <button type="button" data-copy="txtAccountNo" class="tap-feedback rounded bg-stone-200 px-2.5 py-1 text-xs font-medium text-charcoal hover:bg-stone-300">
                    Sao chép
                  </button>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-muted">Chủ tài khoản:</span>
                <span class="font-semibold uppercase text-charcoal"><?= htmlspecialchars(VIETQR_ACCOUNT_NAME) ?></span>
              </div>

              <div class="flex items-center justify-between border-t border-stone-200/70 pt-3">
                <span class="text-muted">Số tiền cần chuyển:</span>
                <div class="flex items-center gap-2">
                  <span id="txtAmount" class="font-bold text-accent text-lg tabular-nums"><?= formatVND($thanhTien) ?></span>
                  <button type="button" id="copyAmountBtn" data-value="<?= (int)round($thanhTien) ?>" class="tap-feedback rounded bg-stone-200 px-2.5 py-1 text-xs font-medium text-charcoal hover:bg-stone-300">
                    Sao chép
                  </button>
                </div>
              </div>

              <div class="flex items-center justify-between border-t border-stone-200/70 pt-3">
                <span class="text-muted">Nội dung chuyển khoản:</span>
                <div class="flex items-center gap-2">
                  <span id="txtAddInfo" class="font-mono font-bold text-emerald-800 text-base"><?= htmlspecialchars($addInfo) ?></span>
                  <button type="button" data-copy="txtAddInfo" class="tap-feedback rounded bg-stone-200 px-2.5 py-1 text-xs font-medium text-charcoal hover:bg-stone-300">
                    Sao chép
                  </button>
                </div>
              </div>
            </div>

            <!-- Cảnh báo đối soát -->
            <div class="flex items-start gap-2.5 rounded-xl bg-amber-50 p-3.5 text-xs text-amber-800 border border-amber-200/60">
              <svg class="h-4 w-4 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
              </svg>
              <span>Vui lòng chuyển <strong>chính xác số tiền</strong> và giữ nguyên cú pháp <strong><?= htmlspecialchars($addInfo) ?></strong> để hệ thống đối soát tự động.</span>
            </div>

            <!-- Trạng thái chờ hệ thống ngân hàng xác nhận (Thay thế nút bấm tay) -->
            <div id="paymentPendingBox" class="space-y-3 pt-1">
              <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-4 text-center">
                <div class="flex items-center justify-center gap-2 text-sm font-semibold text-blue-900">
                  <svg class="h-4 w-4 animate-spin text-blue-600" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                  </svg>
                  <span>Đang chờ hệ thống ngân hàng xác nhận thanh toán...</span>
                </div>
                <p class="mt-1 text-xs text-blue-700">Mã QR giữ đơn trong: <strong id="countdownTimer" class="font-mono text-sm font-bold text-blue-950">15:00</strong></p>
                <p class="mt-1 text-[11px] text-muted">Hệ thống tự động phát hiện giao dịch 24/7 và xác nhận ngay lập tức.</p>
              </div>

              <!-- Nút hỗ trợ Demo / Báo cáo đồ án -->
              <div class="rounded-xl border border-dashed border-stone-300 bg-stone-50 p-3 text-center">
                <p class="text-[11px] text-muted mb-1.5 font-medium">Chế độ Kiểm thử / Báo cáo đồ án:</p>
                <button type="button" id="simulatePaymentBtn" class="tap-feedback inline-flex items-center gap-1.5 rounded-full bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 transition shadow-sm">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                  </svg>
                  ⚡ Giả lập Quét QR &amp; Thanh toán thành công
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Màn hình thông báo Thanh toán Thành công (Tự động hiển thị khi paid === true) -->
      <div id="paymentSuccessScreen" class="hidden overflow-hidden rounded-2xl bg-white shadow-sm border border-stone-200 p-8 sm:p-12 text-center">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 shadow-md">
          <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="mt-5 text-2xl font-bold text-charcoal sm:text-3xl">Thanh toán &amp; Đặt hàng thành công!</h2>
        <p class="mt-2 text-sm text-muted">Hệ thống đã nhận được tiền chuyển khoản cho đơn hàng <strong class="font-mono text-charcoal">#<?= htmlspecialchars($dhCode) ?></strong></p>

        <div class="mx-auto mt-6 max-w-sm rounded-xl bg-stone-50 p-4 text-left text-xs sm:text-sm space-y-2 border border-stone-200/70">
          <div class="flex justify-between">
            <span class="text-muted">Mã đơn hàng:</span>
            <span class="font-mono font-bold text-charcoal">#<?= htmlspecialchars($dhCode) ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted">Số tiền đã nhận:</span>
            <span class="font-bold text-emerald-700"><?= formatVND($thanhTien) ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted">Điểm thưởng tích lũy:</span>
            <span class="font-semibold text-emerald-700">+<?= (int)$order['diem_cong_du_kien'] ?> điểm</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted">Trạng thái:</span>
            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Đã thanh toán (Hoàn thành)</span>
          </div>
        </div>

        <div class="mt-6 flex items-center justify-center gap-2 text-xs text-muted">
          <svg class="h-4 w-4 animate-spin text-charcoal" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
          </svg>
          <span>Tự động chuyển đến đơn hàng của bạn trong <strong id="redirectCountdown" class="font-bold text-charcoal">3</strong> giây...</span>
        </div>

        <div class="mt-6">
          <a href="index.php?page=loyalty#myOrdersTitle" class="tap-feedback inline-block rounded-full bg-charcoal px-8 py-3 text-sm font-semibold text-white hover:bg-accent transition shadow">
            Xem lịch sử đơn hàng ngay &rarr;
          </a>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <!-- Toast phản hồi copy -->
  <div id="copyToast" class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 translate-y-4 rounded-full bg-charcoal px-5 py-2.5 text-xs text-white opacity-0 shadow-lg transition-all duration-300 pointer-events-none">
    Đã sao chép vào bộ nhớ tạm!
  </div>

  <footer class="border-t border-stone-200 bg-white py-6 text-center text-xs text-muted">
    AURA STUDIO &middot; Hệ thống Quản lý Cửa hàng Quần áo &amp; Khách hàng Thân thiết
  </footer>

  <script>
    (() => {
      let toastTimer = null;
      function showToast(msg) {
        const toast = document.getElementById('copyToast');
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.remove('opacity-0', 'translate-y-4');
        toast.classList.add('opacity-100', 'translate-y-0');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
          toast.classList.add('opacity-0', 'translate-y-4');
          toast.classList.remove('opacity-100', 'translate-y-0');
        }, 2000);
      }

      function copyText(str, btn) {
        navigator.clipboard.writeText(str).then(() => {
          const original = btn.textContent;
          btn.textContent = '✓ Đã chép';
          btn.classList.add('bg-emerald-100', 'text-emerald-800');
          showToast('Đã sao chép: ' + str);
          setTimeout(() => {
            btn.textContent = original;
            btn.classList.remove('bg-emerald-100', 'text-emerald-800');
          }, 1800);
        }).catch(() => {
          showToast('Đã chép: ' + str);
        });
      }

      document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const targetId = btn.dataset.copy;
          const text = document.getElementById(targetId)?.textContent?.trim() || '';
          copyText(text, btn);
        });
      });

      document.getElementById('copyAmountBtn')?.addEventListener('click', function() {
        const val = this.dataset.value || '';
        copyText(val, this);
      });

      // =====================================================================
      // CƠ CHẾ POLLING TỰ ĐỘNG XÁC NHẬN THANH TOÁN (MỖI 3 GIÂY)
      // =====================================================================
      const orderId = <?= (int)$order['ma_dh'] ?>;
      const orderAmount = <?= (float)$thanhTien ?>;
      const initialPaid = <?= ($order['trang_thai_dh'] === 'HOAN_THANH' || $order['trang_thai_dh'] === 'DANG_GIAO') ? 'true' : 'false' ?>;

      const qrMainCard = document.getElementById('qrMainCard');
      const paymentSuccessScreen = document.getElementById('paymentSuccessScreen');
      const timerEl = document.getElementById('countdownTimer');
      const redirectCountdownEl = document.getElementById('redirectCountdown');

      let pollInterval = null;
      let countdownInterval = null;
      let hasFinished = false;

      function onPaymentSuccess(data) {
        if (hasFinished) return;
        hasFinished = true;

        if (pollInterval) clearInterval(pollInterval);
        if (countdownInterval) clearInterval(countdownInterval);

        if (qrMainCard) qrMainCard.classList.add('hidden');
        if (paymentSuccessScreen) {
          paymentSuccessScreen.classList.remove('hidden');
        }

        // Đếm ngược 3 giây tự động chuyển hướng sang trang đơn hàng
        let redirectSeconds = 3;
        if (redirectCountdownEl) redirectCountdownEl.textContent = String(redirectSeconds);
        const redirTimer = setInterval(() => {
          redirectSeconds--;
          if (redirectCountdownEl) redirectCountdownEl.textContent = String(redirectSeconds);
          if (redirectSeconds <= 0) {
            clearInterval(redirTimer);
            window.location.href = 'index.php?page=loyalty#myOrdersTitle';
          }
        }, 1000);
      }

      async function checkPaymentStatus() {
        if (hasFinished) return;
        try {
          const res = await fetch(`api/check_payment.php?ma_dh=${orderId}`);
          const data = await res.json();
          if (data.success && data.paid) {
            onPaymentSuccess(data);
          }
        } catch (err) {
          console.warn('Lỗi kiểm tra trạng thái thanh toán:', err);
        }
      }

      // Khởi động đồng hồ đếm ngược 15:00
      let timeLeft = 15 * 60; // 900 giây
      countdownInterval = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
          clearInterval(countdownInterval);
          if (pollInterval) clearInterval(pollInterval);
          if (timerEl) timerEl.textContent = 'Hết hạn';
          return;
        }
        const m = Math.floor(timeLeft / 60);
        const s = timeLeft % 60;
        if (timerEl) {
          timerEl.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }
      }, 1000);

      // Nếu đơn đã thanh toán từ trước
      if (initialPaid) {
        onPaymentSuccess();
      } else {
        // Polling mỗi 3 giây gọi API check_payment.php
        pollInterval = setInterval(checkPaymentStatus, 3000);
        // Kiểm tra ngay sau 1 giây
        setTimeout(checkPaymentStatus, 1000);
      }

      // =====================================================================
      // NÚT MÔ PHỎNG KIỂM THỬ ĐỒ ÁN (GIẢ LẬP TÍN HIỆU NGÂN HÀNG BÁO CÓ)
      // =====================================================================
      document.getElementById('simulatePaymentBtn')?.addEventListener('click', async function() {
        this.disabled = true;
        const originalHtml = this.innerHTML;
        this.innerHTML = `
          <svg class="h-3.5 w-3.5 animate-spin text-white inline-block" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
          </svg>
          Đang gửi webhook ngân hàng...
        `;

        try {
          const res = await fetch('api/payment_webhook.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              ma_dh: orderId,
              so_tien: orderAmount,
              content: `#DH${orderId}`
            })
          });
          const data = await res.json();
          if (data.success) {
            showToast('✓ Ngân hàng báo có thành công!');
            // Lập tức gọi checkPaymentStatus để kích hoạt chuyển màn hình
            await checkPaymentStatus();
          } else {
            alert(data.message || 'Mô phỏng thanh toán thất bại');
            this.disabled = false;
            this.innerHTML = originalHtml;
          }
        } catch (e) {
          console.error(e);
          alert('Lỗi kết nối khi gửi mô phỏng webhook');
          this.disabled = false;
          this.innerHTML = originalHtml;
        }
      });
    })();
  </script>
</body>
</html>
