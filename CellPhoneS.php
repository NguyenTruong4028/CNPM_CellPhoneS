<?php
include 'db.php';

// Lấy dữ liệu sản phẩm từ database
$sql = "SELECT tb.MaHH, tb.TenHH, tb.SL, tb.DGBan, tb.SoHieu, tb.HinhAnh,
               ltb.TenLoai, th.TenTH
        FROM ThietBi AS tb
        LEFT JOIN LoaiThietBi AS ltb ON tb.MaLoai = ltb.MaLoai
        LEFT JOIN ThuongHieu AS th ON tb.MaTH = th.MaTH
        ORDER BY tb.MaHH";

$result = $conn->query($sql);

// Lấy danh sách sản phẩm, nối với bảng LoaiThietBi và ThuongHieu để lấy TenLoai và TenTH
$sql = "SELECT ThietBi.*, LoaiThietBi.TenLoai, ThuongHieu.TenTH 
        FROM ThietBi 
        LEFT JOIN LoaiThietBi ON ThietBi.maLoai = LoaiThietBi.MaLoai 
        LEFT JOIN ThuongHieu ON ThietBi.maTH = ThuongHieu.MaTH";
$result = $conn->query($sql);
$sql_loai = "SELECT * FROM LoaiThietBi";
$result_loai = $conn->query($sql_loai);

$sql_thuonghieu = "SELECT * FROM ThuongHieu";
$result_thuonghieu = $conn->query($sql_thuonghieu);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$ngayNhap = isset($_GET['ngay_nhap']) ? $_GET['ngay_nhap'] : '';

if ($action == 'filter' && !empty($ngayNhap)) {
  // Nếu nhấn nút "Tìm"
  $sql = "SELECT tb.MaHH, tb.TenHH, tb.SL, tb.DGBan, tb.SoHieu, tb.HinhAnh,
                   ltb.TenLoai, th.TenTH
            FROM ThietBi AS tb
            LEFT JOIN LoaiThietBi AS ltb ON tb.MaLoai = ltb.MaLoai
            LEFT JOIN ThuongHieu AS th ON tb.MaTH = th.MaTH
            WHERE DATE(tb.NgayNhap) = '$ngayNhap'
            ORDER BY tb.MaHH";
} else {
  // Mặc định hoặc khi nhấn nút "Xóa lọc"
  $sql = "SELECT tb.MaHH, tb.TenHH, tb.SL, tb.DGBan, tb.SoHieu, tb.HinhAnh,
                   ltb.TenLoai, th.TenTH
            FROM ThietBi AS tb
            LEFT JOIN LoaiThietBi AS ltb ON tb.MaLoai = ltb.MaLoai
            LEFT JOIN ThuongHieu AS th ON tb.MaTH = th.MaTH
            ORDER BY tb.MaHH";
}

$result = $conn->query($sql);
?>
<!-- Hiển thị thông báo nếu có -->
<?php if (!empty($notification)): ?>
  <?php echo $notification; ?>
<?php endif; ?>

<!-- Khi hiển thị danh sách sản phẩm, đánh dấu sản phẩm mới -->
<tr class="<?php echo ($product['MaTB'] == $new_product_id) ? 'highlight-new' : ''; ?>">
  <!-- Các cột dữ liệu của bảng sản phẩm -->
</tr>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>Quản Lý Hàng Hóa - CellphoneS</title>
  <link rel="stylesheet" href="./index.css" />
  <style>
    .highlight-new {
      background-color: #fffde7;
      animation: fadeBackground 3s ease-in-out;
    }

    @keyframes fadeBackground {
      from {
        background-color: #fff9c4;
      }

      to {
        background-color: #fffde7;
      }
    }

    /* Thêm style cho nút xem chi tiết */
    .detail-btn {
      margin-right: 5px;
      color: rgb(0, 0, 0);
      cursor: pointer;
      text-decoration: none;
    }
  </style>
</head>

<body>
  <header>
    <div class="container">
      <div class="navbar">
        <div class="logo">
          <img src="./Them+TraCuu/imgs/LogoCPS.jpg" alt="CellphoneS Logo" />
          <h1>CellphoneS Admin</h1>
        </div>
        <div class="mobile-menu-toggle">☰</div>
        <div class="user-info">
          <div class="avatar">A</div>
          <span>Admin</span>
        </div>
      </div>
    </div>
  </header>

  <aside class="sidebar">
    <div class="sidebar-menu">
      <a href="./CellPhoneS.php" class="menu-item active">
        <i>📱</i>
        <span>Quản lý sản phẩm</span>
      </a>
      <a href="./BanHang/banhang.php" class="menu-item">
        <i>🛒</i>
        <span>Bán hàng</span>
      </a>
      <a href="./ThongKe/thongke.php" class="menu-item">
        <i>📊</i>
        <span>Thống kê</span>
      </a>
      <a href="#" class="menu-item">
        <i>⚙️</i>
        <span>Cài đặt hệ thống</span>
      </a>
    </div>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <h2 class="page-title">Quản lý sản phẩm</h2>
      <div class="action-buttons">
        <a href="./Them+TraCuu/them.php" class="btn btn-primary" style="text-decoration: none;">+ Thêm sản phẩm</a>
      </div>
    </div>
    <div class="filters">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" placeholder="Tìm kiếm sản phẩm..." />
      </div>

      <div class="filter-options">

        <select class="filter-select" name="MaLoai">
          <option value="">Tất cả loại thiết bị</option>
          <?php
          $sql_loai = "SELECT * FROM LoaiThietBi";
          $result_loai = mysqli_query($conn, $sql_loai);
          while ($row = mysqli_fetch_assoc($result_loai)) {
            echo '<option value="' . $row['TenLoai'] . '">' . $row['TenLoai'] . '</option>';
          }
          ?>
        </select>

        <select class="filter-select" name="MaTH">
          <option value="">Tất cả thương hiệu</option>
          <?php
          $sql_th = "SELECT * FROM ThuongHieu";
          $result_th = mysqli_query($conn, $sql_th);
          while ($row = mysqli_fetch_assoc($result_th)) {
            echo '<option value="' . $row['TenTH'] . '">' . $row['TenTH'] . '</option>';
          }
          ?>
        </select>

        <form method="GET" action="">
          <div class="filter-date">
            <label for="ngay_nhap">Ngày nhập</label>
            <input type="date" name="ngay_nhap" id="ngay_nhap" value="<?php echo isset($_GET['ngay_nhap']) ? $_GET['ngay_nhap'] : ''; ?>">
            <button type="submit" name="action" value="filter">Tìm</button>
            <button type="submit" name="action" value="reset">Xóa lọc</button>
          </div>
        </form>
      </div>
    </div>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tên sản phẩm</th>
          <th>Danh mục</th>
          <th>Thương hiệu</th>
          <th>Giá bán</th>
          <th>Tồn kho</th>
          <th>Xem chi tiết</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr class="<?= (isset($_GET['new_product']) && $_GET['new_product'] == $row['MaHH']) ? 'highlight-new' : ''; ?>">
            <td>#SP<?= str_pad($row['MaHH'], 3, '0', STR_PAD_LEFT) ?></td>
            <td><?= htmlspecialchars($row['TenHH']) ?></td>
            <td><?= htmlspecialchars($row['TenLoai']) ?></td>
            <td><?= htmlspecialchars($row['TenTH']) ?></td>
            <td><?= number_format($row['DGBan'], 0, ',', '.') ?>₫</td>
            <td><?= $row['SL'] ?></td>
            <td>
              <!-- <a href="sua.php?id=<?= $row['MaHH'] ?>" class="edit-btn" title="Sửa">✏️</a>
              <a href="xoa.php?id=<?= $row['MaHH'] ?>" class="delete-btn" onclick="return confirm('Xóa sản phẩm này?');" title="Xóa">🗑️</a> -->
              <a href="Them+TraCuu/chitiet.php?MaHH=<?php echo $row['MaHH']; ?>" class="detail-btn" title="Xem chi tiết">☰</a>
            </td>
          </tr>
        <?php endwhile; ?>

      </tbody>
    </table>
  </main>
  <script src="./Loc.js"></script>
</body>

</html>