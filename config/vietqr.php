<?php
/**
 * CẤU HÌNH THÔNG TIN TÀI KHOẢN THANH TOÁN VIETQR
 * Phân hệ Giỏ hàng & Đặt hàng Đa tầng — Mục 2.3: Tích hợp mã QR Chuyển khoản
 */

if (!defined('VIETQR_BANK_ID')) {
    define('VIETQR_BANK_ID', 'MB'); // Mã ngân hàng theo chuẩn Napas/VietQR (MB, VCB, TCB, ACB, ...)
}

if (!defined('VIETQR_BANK_NAME')) {
    define('VIETQR_BANK_NAME', 'MB Bank (Ngân hàng TMCP Quân Đội)');
}

if (!defined('VIETQR_ACCOUNT_NO')) {
    define('VIETQR_ACCOUNT_NO', '0901235629'); // Số tài khoản ngân hàng nhận thanh toán
}

if (!defined('VIETQR_ACCOUNT_NAME')) {
    define('VIETQR_ACCOUNT_NAME', 'AURA STUDIO'); // Tên chủ tài khoản (không dấu)
}

/**
 * Sinh đường dẫn ảnh mã VietQR Quick Link chuẩn
 * Cấu trúc: https://img.vietqr.io/image/<BANK_ID>-<ACCOUNT_NO>-compact2.png?amount=<thanh_tien>&addInfo=<urlencode("#DH" + ma_dh)>&accountName=<urlencode(ACCOUNT_NAME)>
 *
 * @param float|int $thanhTien Số tiền cần thanh toán
 * @param int $maDh Mã đơn hàng
 * @return string Đường dẫn ảnh QR đầy đủ
 */
function buildVietQRUrl(float|int $thanhTien, int $maDh): string
{
    $noiDung = '#DH' . str_pad((string)$maDh, 2, '0', STR_PAD_LEFT);
    return sprintf(
        'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s&accountName=%s',
        VIETQR_BANK_ID,
        VIETQR_ACCOUNT_NO,
        (int) round($thanhTien),
        urlencode($noiDung),
        urlencode(VIETQR_ACCOUNT_NAME)
    );
}
