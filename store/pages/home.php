<?php
/**
 * TẦNG CỬA HÀNG: Trang chủ & Danh sách sản phẩm (store/pages/home.php)
 * Đổ 100% dữ liệu từ bảng sp_san_pham, dm_danh_muc_sp, bt_bien_the_sp, ha_hinh_anh_sp
 */
$currentGender = $_GET['gender'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');
$tierMultiplier = (float) ($loyaltyProfile['he_so_tich_diem'] ?? 1.0);
$tierName = $loyaltyProfile['ten_hang'] ?? 'Hạng Đồng';
?>
<div id="homeView" class="space-y-16">
  <!-- Hero Banner -->
  <section class="fade-up grid overflow-hidden rounded-2xl bg-stone-100 shadow-sm lg:grid-cols-2">
    <div class="flex flex-col justify-center px-8 py-12 lg:px-16 lg:py-20">
      <p class="text-xs font-medium uppercase tracking-[0.2em] text-accent">Bộ sưu tập Thời trang &amp; Loyalty 2026</p>
      <h1 class="mt-4 text-3xl font-semibold leading-tight sm:text-4xl lg:text-5xl">Ấm áp cho những ngày se lạnh</h1>
      <p class="mt-5 max-w-md text-base leading-relaxed text-muted">
        Tích điểm mỗi ngày — Thăng hạng nhận ngay ưu đãi tự động đến 10% và nhân đôi điểm thưởng. Dữ liệu kết nối trực tiếp từ CSDL thực tế.
      </p>
      <div class="mt-8 flex flex-wrap gap-3">
        <a href="#cua-hang" class="tap-feedback rounded-full bg-charcoal px-6 py-3 text-sm font-medium text-white hover:bg-accent">Khám phá <?= count($allProducts) ?> sản phẩm</a>
        <a href="index.php?page=loyalty" class="tap-feedback rounded-full border border-charcoal/20 px-6 py-3 text-sm font-medium text-charcoal hover:border-accent hover:text-accent">Xem thẻ <?= htmlspecialchars($tierName) ?></a>
      </div>
    </div>
    <div class="relative h-72 sm:h-96 lg:h-auto">
      <img src="https://images.pexels.com/photos/9968526/pexels-photo-9968526.jpeg?auto=compress&cs=tinysrgb&h=650&w=940"
        alt="Bộ sưu tập thời trang AURA STUDIO" class="absolute inset-0 h-full w-full object-cover" />
    </div>
  </section>

  <!-- Gợi ý sản phẩm nổi bật (Top lượt xem từ CSDL) -->
  <section aria-labelledby="goi-y-title">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h2 id="goi-y-title" class="text-2xl font-semibold leading-tight">Gợi ý riêng cho bạn</h2>
        <p class="mt-2 inline-flex items-center rounded-full bg-olive/10 px-3 py-1 text-xs font-medium text-olive">
          Top 4 sản phẩm có lượt xem cao nhất từ bảng <code class="mx-1 font-semibold">sp_san_pham</code> (Thuật toán AI cá nhân hóa: Đang phát triển)
        </p>
      </div>
    </div>

    <div class="mt-8 grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-4">
      <?php foreach ($recommendedProducts as $idx => $p): ?>
        <?php
          $pts = (int) floor(((float)$p['gia_khuyen_mai'] / POINTS_PER_VND) * $tierMultiplier);
        ?>
        <article class="fade-up group" style="--stagger: <?= $idx ?>">
          <div class="relative aspect-[3/4] overflow-hidden rounded-xl bg-stone-100 shadow-sm">
            <a href="index.php?page=product&id=<?= (int)$p['ma_sp'] ?>" class="block h-full w-full">
              <img src="<?= htmlspecialchars($p['anh_chinh_url']) ?>" alt="<?= htmlspecialchars($p['ten_sp']) ?>" loading="lazy"
                class="h-full w-full object-cover transition duration-700 group-hover:scale-105" />
            </a>
            <span class="tabular-nums absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-medium text-charcoal backdrop-blur">
              <?= number_format((int)$p['luot_xem'], 0, ',', '.') ?> lượt xem
            </span>
            <button type="button" data-dev-feature="Thêm nhanh sản phẩm #<?= (int)$p['ma_sp'] ?> vào giỏ hàng"
              class="tap-feedback absolute bottom-3 right-3 rounded-full bg-white px-4 py-2 text-xs font-medium text-charcoal opacity-100 shadow-sm transition duration-300 hover:bg-charcoal hover:text-white md:translate-y-2 md:opacity-0 md:group-hover:translate-y-0 md:group-hover:opacity-100">
              + Thêm vào giỏ (Đang phát triển)
            </button>
          </div>
          <div class="mt-4 space-y-2">
            <div class="flex items-center gap-2">
              <?php foreach ($p['colors'] as $cIdx => $colorName): ?>
                <span title="<?= htmlspecialchars($colorName) ?>"
                  class="h-4 w-4 rounded-full border border-stone-300 <?= $cIdx === 0 ? 'ring-2 ring-charcoal ring-offset-2 ring-offset-cream' : '' ?>"
                  style="background: <?= htmlspecialchars(getColorHex($colorName)) ?>"></span>
              <?php endforeach; ?>
              <span class="ml-auto text-[11px] text-muted"><?= htmlspecialchars($p['ten_dm']) ?></span>
            </div>
            <a href="index.php?page=product&id=<?= (int)$p['ma_sp'] ?>" class="block text-sm font-medium leading-snug text-charcoal transition hover:text-accent">
              <?= htmlspecialchars($p['ten_sp']) ?>
            </a>
            <div class="flex items-baseline gap-2 tabular-nums">
              <span class="text-sm font-semibold text-charcoal"><?= formatVND($p['gia_khuyen_mai']) ?></span>
              <?php if ((float)$p['gia_niem_yet'] > (float)$p['gia_khuyen_mai']): ?>
                <span class="text-xs text-muted line-through"><?= formatVND($p['gia_niem_yet']) ?></span>
              <?php endif; ?>
            </div>
            <p class="tabular-nums text-xs font-medium text-accent">+<?= $pts ?> điểm thưởng (x<?= number_format($tierMultiplier, 1) ?>)</p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Khu vực Cửa hàng & Bộ lọc sản phẩm từ CSDL -->
  <section id="cua-hang" class="grid scroll-mt-24 gap-10 lg:grid-cols-4">
    <aside class="lg:col-span-1">
      <div class="space-y-8 rounded-xl bg-white p-6 shadow-sm lg:sticky lg:top-24">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-semibold">Bộ lọc CSDL</h2>
          <button type="button" id="resetFilters" class="text-xs font-medium text-accent transition hover:underline">Xóa tất cả</button>
        </div>

        <!-- Lọc theo Danh mục thực tế từ dm_danh_muc_sp -->
        <fieldset>
          <legend class="mb-3 text-sm font-medium text-charcoal">Danh mục sản phẩm</legend>
          <div class="space-y-2.5 text-sm text-muted">
            <?php foreach ($subCategories as $cat): ?>
              <label class="flex cursor-pointer items-center gap-3 transition hover:text-charcoal">
                <input type="checkbox" value="<?= (int)$cat['ma_dm'] ?>" data-filter-cat class="h-4 w-4 rounded border-stone-300 accent-accent" />
                <span><?= htmlspecialchars($cat['ten_dm']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <!-- Lọc theo Khoảng giá -->
        <fieldset>
          <legend class="mb-3 text-sm font-medium text-charcoal">Khoảng giá</legend>
          <div class="space-y-2.5 text-sm text-muted">
            <label class="flex cursor-pointer items-center gap-3 transition hover:text-charcoal">
              <input type="radio" name="price" value="all" checked class="h-4 w-4 border-stone-300 accent-accent" /> Tất cả mức giá
            </label>
            <label class="flex cursor-pointer items-center gap-3 transition hover:text-charcoal">
              <input type="radio" name="price" value="under300" class="h-4 w-4 border-stone-300 accent-accent" /> Dưới 300.000đ
            </label>
            <label class="flex cursor-pointer items-center gap-3 transition hover:text-charcoal">
              <input type="radio" name="price" value="300-400" class="h-4 w-4 border-stone-300 accent-accent" /> 300.000đ - 400.000đ
            </label>
            <label class="flex cursor-pointer items-center gap-3 transition hover:text-charcoal">
              <input type="radio" name="price" value="over400" class="h-4 w-4 border-stone-300 accent-accent" /> Trên 400.000đ
            </label>
          </div>
        </fieldset>

        <!-- Lọc theo Kích cỡ thực tế từ bt_bien_the_sp -->
        <fieldset>
          <legend class="mb-3 text-sm font-medium text-charcoal">Kích cỡ (SKU)</legend>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($filterOptions['sizes'] as $size): ?>
              <button type="button" data-filter-size="<?= htmlspecialchars($size) ?>" aria-pressed="false"
                class="tap-feedback rounded-full border border-stone-200 px-3.5 py-2 text-xs font-medium text-charcoal hover:border-charcoal">
                <?= htmlspecialchars($size) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <!-- Lọc theo Màu sắc thực tế từ bt_bien_the_sp -->
        <fieldset>
          <legend class="mb-3 text-sm font-medium text-charcoal">Màu sắc</legend>
          <div class="flex flex-wrap gap-3">
            <?php foreach ($filterOptions['colors'] as $color): ?>
              <button type="button" data-filter-color="<?= htmlspecialchars($color) ?>"
                aria-label="<?= htmlspecialchars($color) ?>" aria-pressed="false" title="<?= htmlspecialchars($color) ?>"
                class="h-7 w-7 rounded-full border border-stone-300 transition hover:scale-110"
                style="background: <?= htmlspecialchars(getColorHex($color)) ?>"></button>
            <?php endforeach; ?>
          </div>
        </fieldset>
      </div>
    </aside>

    <div class="lg:col-span-3">
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-stone-200 pb-4">
        <div>
          <h2 id="storeTitle" class="text-xl font-semibold">
            <?php
              if ($currentGender === 'nam') echo 'Thời trang Nam';
              elseif ($currentGender === 'nu') echo 'Thời trang Nữ';
              else echo 'Tất cả sản phẩm trong CSDL';
            ?>
          </h2>
          <p id="resultCount" class="tabular-nums mt-1 text-sm text-muted">Hiển thị <?= count($allProducts) ?> sản phẩm</p>
        </div>
        <label class="flex items-center gap-3 text-sm text-muted">
          Sắp xếp
          <select id="sortSelect" class="rounded-full border border-stone-200 bg-white px-4 py-2 text-sm text-charcoal focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
            <option value="default">Mặc định (Mã SP)</option>
            <option value="views-desc">Lượt xem nhiều nhất</option>
            <option value="price-asc">Giá tăng dần</option>
            <option value="price-desc">Giá giảm dần</option>
          </select>
        </label>
      </div>

      <!-- Lưới sản phẩm được kết xuất trực tiếp từ PHP + hỗ trợ lọc nhanh trên DOM -->
      <div id="productGrid" class="mt-8 grid grid-cols-2 gap-x-6 gap-y-12 lg:grid-cols-3">
        <?php foreach ($allProducts as $idx => $p): ?>
          <?php
            $pts = (int) floor(((float)$p['gia_khuyen_mai'] / POINTS_PER_VND) * $tierMultiplier);
            $sizesAttr = implode('|', $p['sizes']);
            $colorsAttr = implode('|', $p['colors']);
          ?>
          <article class="product-card fade-up group"
            style="--stagger: <?= $idx % 6 ?>"
            data-id="<?= (int)$p['ma_sp'] ?>"
            data-name="<?= htmlspecialchars(mb_strtolower($p['ten_sp'])) ?>"
            data-gender="<?= htmlspecialchars($p['gender_group']) ?>"
            data-cat="<?= (int)$p['ma_dm'] ?>"
            data-price="<?= (float)$p['gia_khuyen_mai'] ?>"
            data-views="<?= (int)$p['luot_xem'] ?>"
            data-sizes="<?= htmlspecialchars($sizesAttr) ?>"
            data-colors="<?= htmlspecialchars($colorsAttr) ?>">
            <div class="relative aspect-[3/4] overflow-hidden rounded-xl bg-stone-100 shadow-sm">
              <a href="index.php?page=product&id=<?= (int)$p['ma_sp'] ?>" class="block h-full w-full">
                <img src="<?= htmlspecialchars($p['anh_chinh_url']) ?>" alt="<?= htmlspecialchars($p['ten_sp']) ?>" loading="lazy"
                  class="h-full w-full object-cover transition duration-700 group-hover:scale-105" />
              </a>
              <span class="tabular-nums absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-medium text-charcoal backdrop-blur">
                Tồn: <?= (int)$p['tong_ton_kho'] ?>
              </span>
              <button type="button" data-dev-feature="Thêm sản phẩm '<?= htmlspecialchars($p['ten_sp']) ?>' vào bảng gh_gio_hang"
                class="tap-feedback absolute bottom-3 right-3 rounded-full bg-white px-4 py-2 text-xs font-medium text-charcoal opacity-100 shadow-sm transition duration-300 hover:bg-charcoal hover:text-white md:translate-y-2 md:opacity-0 md:group-hover:translate-y-0 md:group-hover:opacity-100">
                + Thêm vào giỏ
              </button>
            </div>
            <div class="mt-4 space-y-2">
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5">
                  <?php foreach ($p['colors'] as $cIdx => $colorName): ?>
                    <span title="<?= htmlspecialchars($colorName) ?>"
                      class="h-4 w-4 rounded-full border border-stone-300 <?= $cIdx === 0 ? 'ring-2 ring-charcoal ring-offset-2 ring-offset-cream' : '' ?>"
                      style="background: <?= htmlspecialchars(getColorHex($colorName)) ?>"></span>
                  <?php endforeach; ?>
                </div>
                <span class="text-[11px] text-muted"><?= htmlspecialchars($p['ten_dm']) ?></span>
              </div>
              <a href="index.php?page=product&id=<?= (int)$p['ma_sp'] ?>" class="block text-sm font-medium leading-snug text-charcoal transition hover:text-accent">
                <?= htmlspecialchars($p['ten_sp']) ?>
              </a>
              <div class="flex items-baseline gap-2 tabular-nums">
                <span class="text-sm font-semibold text-charcoal"><?= formatVND($p['gia_khuyen_mai']) ?></span>
                <?php if ((float)$p['gia_niem_yet'] > (float)$p['gia_khuyen_mai']): ?>
                  <span class="text-xs text-muted line-through"><?= formatVND($p['gia_niem_yet']) ?></span>
                <?php endif; ?>
              </div>
              <p class="tabular-nums text-xs font-medium text-accent">+<?= $pts ?> điểm thưởng khi mua</p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div id="emptyState" class="mt-16 hidden text-center">
        <p class="text-base font-medium text-charcoal">Không tìm thấy sản phẩm phù hợp trong CSDL</p>
        <p class="mt-2 text-sm text-muted">Hãy thử thay đổi bộ lọc danh mục, kích cỡ, màu sắc hoặc từ khóa tìm kiếm.</p>
        <button type="button" id="emptyResetBtn" class="tap-feedback mt-6 rounded-full border border-charcoal/20 px-6 py-2.5 text-sm font-medium hover:border-accent hover:text-accent">
          Xóa bộ lọc
        </button>
      </div>
    </div>
  </section>
</div>

<script>
  (() => {
    const initialGender = <?= json_encode($currentGender) ?>;
    const initialQuery = <?= json_encode(mb_strtolower($searchQuery)) ?>;

    const state = {
      gender: initialGender,
      query: initialQuery,
      cats: new Set(),
      price: 'all',
      sizes: new Set(),
      colors: new Set(),
      sort: 'default',
    };

    const grid = document.getElementById('productGrid');
    const cards = Array.from(grid.querySelectorAll('.product-card'));
    const resultCount = document.getElementById('resultCount');
    const emptyState = document.getElementById('emptyState');

    function matchesFilter(card) {
      if (state.gender !== 'all' && card.dataset.gender !== state.gender) return false;
      if (state.query && !card.dataset.name.includes(state.query)) return false;
      if (state.cats.size > 0 && !state.cats.has(card.dataset.cat)) return false;

      const price = Number(card.dataset.price);
      if (state.price === 'under300' && !(price < 300000)) return false;
      if (state.price === '300-400' && !(price >= 300000 && price <= 400000)) return false;
      if (state.price === 'over400' && !(price > 400000)) return false;

      const cardSizes = (card.dataset.sizes || '').split('|');
      if (state.sizes.size > 0 && ![...state.sizes].some((s) => cardSizes.includes(s))) return false;

      const cardColors = (card.dataset.colors || '').split('|');
      if (state.colors.size > 0 && ![...state.colors].some((c) => cardColors.includes(c))) return false;

      return true;
    }

    function applyFilterAndSort() {
      const matched = [];
      cards.forEach((card) => {
        const show = matchesFilter(card);
        card.classList.toggle('hidden', !show);
        if (show) matched.push(card);
      });

      matched.sort((a, b) => {
        if (state.sort === 'price-asc') return Number(a.dataset.price) - Number(b.dataset.price);
        if (state.sort === 'price-desc') return Number(b.dataset.price) - Number(a.dataset.price);
        if (state.sort === 'views-desc') return Number(b.dataset.views) - Number(a.dataset.views);
        return Number(a.dataset.id) - Number(b.dataset.id);
      });

      matched.forEach((card, i) => {
        card.style.setProperty('--stagger', i % 6);
        grid.appendChild(card);
      });

      resultCount.textContent = `Hiển thị ${matched.length} trên ${cards.length} sản phẩm trong CSDL`;
      emptyState.classList.toggle('hidden', matched.length > 0);
    }

    document.querySelectorAll('[data-filter-cat]').forEach((cb) => {
      cb.addEventListener('change', () => {
        if (cb.checked) state.cats.add(cb.value);
        else state.cats.delete(cb.value);
        applyFilterAndSort();
      });
    });

    document.querySelectorAll('input[name="price"]').forEach((rb) => {
      rb.addEventListener('change', () => {
        state.price = rb.value;
        applyFilterAndSort();
      });
    });

    document.querySelectorAll('[data-filter-size]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const val = btn.dataset.filterSize;
        if (state.sizes.has(val)) state.sizes.delete(val);
        else state.sizes.add(val);
        const active = state.sizes.has(val);
        btn.setAttribute('aria-pressed', String(active));
        btn.classList.toggle('bg-charcoal', active);
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('border-charcoal', active);
        applyFilterAndSort();
      });
    });

    document.querySelectorAll('[data-filter-color]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const val = btn.dataset.filterColor;
        if (state.colors.has(val)) state.colors.delete(val);
        else state.colors.add(val);
        const active = state.colors.has(val);
        btn.setAttribute('aria-pressed', String(active));
        btn.classList.toggle('ring-2', active);
        btn.classList.toggle('ring-charcoal', active);
        btn.classList.toggle('ring-offset-2', active);
        applyFilterAndSort();
      });
    });

    document.getElementById('sortSelect')?.addEventListener('change', (e) => {
      state.sort = e.target.value;
      applyFilterAndSort();
    });

    function resetAll() {
      state.cats.clear();
      state.sizes.clear();
      state.colors.clear();
      state.price = 'all';
      state.query = '';
      document.querySelectorAll('[data-filter-cat]').forEach((cb) => { cb.checked = false; });
      document.querySelectorAll('input[name="price"]').forEach((rb) => { rb.checked = rb.value === 'all'; });
      document.querySelectorAll('[data-filter-size]').forEach((btn) => {
        btn.setAttribute('aria-pressed', 'false');
        btn.classList.remove('bg-charcoal', 'text-white', 'border-charcoal');
      });
      document.querySelectorAll('[data-filter-color]').forEach((btn) => {
        btn.setAttribute('aria-pressed', 'false');
        btn.classList.remove('ring-2', 'ring-charcoal', 'ring-offset-2');
      });
      const sInput = document.getElementById('searchInput');
      if (sInput) sInput.value = '';
      applyFilterAndSort();
    }

    document.getElementById('resetFilters')?.addEventListener('click', resetAll);
    document.getElementById('emptyResetBtn')?.addEventListener('click', resetAll);

    applyFilterAndSort();
  })();
</script>

