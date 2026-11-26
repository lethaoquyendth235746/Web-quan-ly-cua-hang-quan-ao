<?php

/**
 * FOOTER DÙNG CHUNG CHO CLIENT (includes/footer.php)
 * Bootstrap 5 Footer & Script
 */
?>
  </main>

  <footer class="bg-white border-top py-5 mt-auto">
    <div class="container">
      <div class="row g-4 justify-content-between">
        <div class="col-lg-5 col-md-6">
          <h5 class="fw-bold brand-letter-spacing fs-6 text-dark mb-2">AURA STUDIO</h5>
          <p class="text-secondary small mb-3">
            Hệ thống Quản lý Cửa hàng Quần áo &amp; Khách hàng Thân thiết (Fashion Loyalty Web).
            Nền tảng thương mại điện tử tích hợp cơ chế xếp hạng thành viên và voucher ưu đãi đa tầng.
          </p>
          <div class="d-flex gap-2">
            <span class="badge bg-light text-dark border">PHP 8.2</span>
            <span class="badge bg-light text-dark border">MySQL PDO</span>
            <span class="badge bg-light text-dark border">Bootstrap 5</span>
          </div>
        </div>

        <div class="col-lg-2 col-6">
          <h6 class="fw-semibold text-dark small text-uppercase mb-3">Cửa hàng</h6>
          <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
            <li><a href="index.php" class="text-decoration-none text-secondary">Trang chủ</a></li>
            <li><a href="index.php?page=home&gender=nam" class="text-decoration-none text-secondary">Thời trang Nam</a></li>
            <li><a href="index.php?page=home&gender=nu" class="text-decoration-none text-secondary">Thời trang Nữ</a></li>
            <li><a href="index.php?page=loyalty" class="text-decoration-none text-secondary">Ưu đãi Loyalty</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-6">
          <h6 class="fw-semibold text-dark small text-uppercase mb-3">Tài khoản &amp; Hỗ trợ</h6>
          <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
            <li><a href="profile.php" class="text-decoration-none text-secondary">Hồ sơ thành viên</a></li>
            <li><a href="profile.php#address-section" class="text-decoration-none text-secondary">Sổ địa chỉ nhận hàng</a></li>
            <li><a href="index.php?page=checkout" class="text-decoration-none text-secondary">Giỏ hàng &amp; Thanh toán</a></li>
            <li><a href="admin/index.php" class="text-decoration-none text-primary fw-medium">Phân hệ Quản trị &rarr;</a></li>
          </ul>
        </div>
      </div>

      <div class="border-top pt-4 mt-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small text-secondary">
        <div>
          &copy; <?= date('Y') ?> AURA STUDIO. Bản quyền thuộc nhóm phát triển dự án.
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap 5 Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
