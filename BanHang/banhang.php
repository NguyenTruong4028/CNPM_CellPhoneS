<?php
include '../db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Biến thông báo
$notification = '';

// Kiểm tra xem request có phải AJAX không
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Xử lý khi submit tạo đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
  $maKH = $_POST['maKH'] ?? '';
  $products = $_POST['products'] ?? [];
  $quantities = $_POST['quantities'] ?? [];

  if (empty($maKH)) {
    $message = 'Vui lòng chọn khách hàng';
  } elseif (count($products) === 0) {
    $message = 'Vui lòng thêm sản phẩm vào giỏ hàng';
  } else {
    $conn->begin_transaction();
    try {
      // Sinh mã hóa đơn tự động
      $res = $conn->query("SELECT MAX(MaHD) AS maxID FROM hoadon");
      $row = $res->fetch_assoc();
      $last = $row['maxID'] ? (int)substr($row['maxID'], 2) : 0;
      $newMaHD = 'HD' . str_pad($last + 1, 3, '0', STR_PAD_LEFT);

      // Tạo hóa đơn
      $stmtHD = $conn->prepare(
        "INSERT INTO hoadon (MaHD, MaKH, NgayLap, TongTien) VALUES (?, ?, NOW(), 0)"
      );
      $stmtHD->bind_param('ss', $newMaHD, $maKH);
      $stmtHD->execute();

      // Chuẩn bị statement thêm chi tiết và cập nhật tồn kho
      $stmtCT = $conn->prepare(
        "INSERT INTO cthd (MaHD, MaMH, SL, DGMua) VALUES (?, ?, ?, ?)"
      );
      $stmtUpd = $conn->prepare(
        "UPDATE thietbi SET SL = SL - ? WHERE MaHH = ?"
      );

      $tongTien = 0;
      $invoiceItems = [];

      // Thêm từng sản phẩm
      foreach ($products as $i => $pid) {
        $qty = (int)$quantities[$i];
        if ($qty <= 0) continue;

        // Lấy giá và kiểm tra tồn kho
        $stmtP = $conn->prepare(
          "SELECT DGBan, SL, TenHH FROM thietbi WHERE MaHH = ?"
        );
        $stmtP->bind_param('s', $pid);
        $stmtP->execute();
        $rP = $stmtP->get_result()->fetch_assoc();
        if (!$rP) throw new Exception("Sản phẩm $pid không tồn tại.");
        if ($rP['SL'] < $qty) throw new Exception("Sản phẩm $pid không đủ tồn kho.");

        $donGia = $rP['DGBan'];
        $thanhTien = $donGia * $qty;
        $tongTien += $thanhTien;

        // Ghi chi tiết
        $stmtCT->bind_param('ssis', $newMaHD, $pid, $qty, $donGia);
        $stmtCT->execute();

        // Cập nhật tồn kho
        $stmtUpd->bind_param('is', $qty, $pid);
        $stmtUpd->execute();

        // Lưu thông tin cho hóa đơn
        $invoiceItems[] = [
          'name' => $rP['TenHH'],
          'quantity' => $qty,
          'price' => $donGia
        ];
      }

      // Cập nhật tổng tiền hóa đơn
      $stmtUpdTotal = $conn->prepare(
        "UPDATE hoadon SET TongTien = ? WHERE MaHD = ?"
      );
      $stmtUpdTotal->bind_param('ds', $tongTien, $newMaHD);
      $stmtUpdTotal->execute();

      // Lấy thông tin khách hàng
      $stmtKH = $conn->prepare("SELECT TenKH FROM khachhang WHERE MaKH = ?");
      $stmtKH->bind_param('s', $maKH);
      $stmtKH->execute();
      $kh = $stmtKH->get_result()->fetch_assoc();

      $conn->commit();

      // Chuẩn bị thông báo
      $notification = '<div class="alert alert-success">Tạo hóa đơn ' . $newMaHD . ' thành công!</div>';

      if ($isAjax) {
        // Trả về JSON cho AJAX
        header('Content-Type: application/json');
        echo json_encode([
          'success' => true,
          'invoice' => [
            'id' => $newMaHD,
            'date' => date('d/m/Y H:i:s'),
            'customer' => $kh['TenKH'] ?? 'Khách lẻ',
            'items' => $invoiceItems,
            'total' => $tongTien
          ]
        ]);
        exit;
      }
 generator:
    } catch (Exception $e) {
      $conn->rollback();
      $notification = '<div class="alert alert-danger">Lỗi: ' . $e->getMessage() . '</div>';
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        exit;
      }
    }
  }

  if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message ?? 'Dữ liệu không hợp lệ']);
    exit;
  }
}

// Lấy dữ liệu để hiển thị
$sql = "SELECT tb.MaHH, tb.TenHH, tb.SL, tb.DGBan, tb.HinhAnh,
           ltb.TenLoai, th.TenTH
        FROM thietbi tb
        LEFT JOIN LoaiThietBi ltb ON tb.MaLoai = ltb.MaLoai
        LEFT JOIN ThuongHieu th ON tb.MaTH = th.MaTH
        WHERE tb.SL > 0
        ORDER BY tb.TenHH";
$resultProducts = $conn->query($sql);

$sqlKH = "SELECT MaKH, TenKH, SDT FROM khachhang ORDER BY TenKH";
$resultKhachHang = $conn->query($sqlKH);

$sqlRecent = "SELECT hd.MaHD, kh.TenKH, hd.NgayLap,
                  COUNT(ct.MaMH) AS SoMatHang,
                  SUM(ct.SL) AS TongSoLuong, hd.TongTien
               FROM hoadon hd
               JOIN khachhang kh ON hd.MaKH = kh.MaKH
               JOIN cthd ct ON hd.MaHD = ct.MaHD
               GROUP BY hd.MaHD
               ORDER BY hd.NgayLap DESC
               LIMIT 10";
$resultRecentOrders = $conn->query($sqlRecent);

?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>Bán Hàng - CellphoneS</title>
  <link rel="stylesheet" href="../index.css" />
  <link rel="stylesheet" href="banhang.css" />
  <style>
    .invoice-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .invoice-content {
      background-color: white;
      padding: 20px;
      width: 80%;
      max-width: 800px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .invoice-header {
      text-align: center;
      margin-bottom: 20px;
    }

    .invoice-header h2 {
      margin: 0;
      font-size: 24px;
      color: #333;
    }

    .invoice-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }

    .invoice-table th,
    .invoice-table td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: left;
      font-size: 14px;
    }

    .invoice-table th {
      background-color: #f2f2f2;
      font-weight: bold;
    }

    .invoice-total {
      text-align: right;
      font-size: 16px;
      margin-top: 20px;
    }

    .invoice-actions {
      text-align: right;
      margin-top: 20px;
    }

    .close-btn,
    .print-btn,
    .export-btn {
      padding: 10px 20px;
      margin-left: 10px;
      cursor: pointer;
      border: none;
      border-radius: 4px;
      font-size: 14px;
    }

    .print-btn {
      background-color: #4CAF50;
      color: white;
    }

    .export-btn {
      background-color: #2196F3;
      color: white;
    }

    .close-btn {
      background-color: #f44336;
      color: white;
    }
  </style>
</head>

<body>
  <header>
    <div class="container">
      <div class="navbar">
        <div class="logo">
          <img src="../Them+TraCuu/imgs/LogoCPS.jpg" alt="CellphoneS Logo" />
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
      <a href="../CellPhoneS.php" class="menu-item">
        <i>📱</i>
        <span>Quản lý sản phẩm</span>
      </a>
      <a href="#" class="menu-item active">
        <i>🛒</i>
        <span>Bán hàng</span>
      </a>
      <a href="../ThongKe/thongke.php" class="menu-item">
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
      <h2 class="page-title">Bán hàng</h2>
    </div>

    <?php if (!empty($notification)): ?>
      <?php echo $notification; ?>
    <?php endif; ?>

    <form method="POST" action="banhang.php">
      <div class="order-section">
        <div class="products-section">
          <div class="filters">
            <div class="search-box">
              <span class="search-icon">🔍</span>
              <input type="text" id="searchProduct" placeholder="Tìm kiếm sản phẩm..." />
            </div>

            <div class="filter-options">
              <select class="filter-select" id="filterCategory">
                <option value="">Tất cả loại thiết bị</option>
                <?php
                $sql_loai = "SELECT * FROM LoaiThietBi";
                $result_loai = mysqli_query($conn, $sql_loai);
                while ($row = mysqli_fetch_assoc($result_loai)) {
                  echo '<option value="' . $row['TenLoai'] . '">' . $row['TenLoai'] . '</option>';
                }
                ?>
              </select>

              <select class="filter-select" id="filterBrand">
                <option value="">Tất cả thương hiệu</option>
                <?php
                $sql_th = "SELECT * FROM ThuongHieu";
                $result_th = mysqli_query($conn, $sql_th);
                while ($row = mysqli_fetch_assoc($result_th)) {
                  echo '<option value="' . $row['TenTH'] . '">' . $row['TenTH'] . '</option>';
                }
                ?>
              </select>
            </div>
          </div>

          <div class="product-grid" id="productGrid">
            <?php while ($product = $resultProducts->fetch_assoc()): ?>
              <div class="product-card" data-id="<?= $product['MaHH'] ?>" data-name="<?= htmlspecialchars($product['TenHH']) ?>"
                data-price="<?= $product['DGBan'] ?>" data-stock="<?= $product['SL'] ?>" data-image="<?= $product['HinhAnh'] ?>"
                data-category="<?= htmlspecialchars($product['TenLoai']) ?>" data-brand="<?= htmlspecialchars($product['TenTH']) ?>">
                <?php if (!empty($product['HinhAnh'])): ?>
                  <img src="../Them+TraCuu/<?= htmlspecialchars($product['HinhAnh']) ?>" alt="<?= htmlspecialchars($product['TenHH']) ?>" class="product-image">
                <?php else: ?>
                  <div class="product-image-placeholder">Không có ảnh</div>
                <?php endif; ?>
                <div class="product-name"><?= htmlspecialchars($product['TenHH']) ?></div>
                <div class="product-price"><?= number_format($product['DGBan'], 0, ',', '.') ?>₫</div>
                <div class="product-stock">Còn <?= $product['SL'] ?> sản phẩm</div>
              </div>
            <?php endwhile; ?>
          </div>
        </div>

        <div class="cart-section">
          <div class="cart-header">
            <div class="cart-title">Giỏ hàng</div>
          </div>

          <select class="customer-select" name="maKH" required>
            <option value="">-- Chọn khách hàng --</option>
            <?php while ($customer = $resultKhachHang->fetch_assoc()): ?>
              <option value="<?= $customer['MaKH'] ?>"><?= htmlspecialchars($customer['TenKH']) ?> - <?= htmlspecialchars($customer['SDT']) ?></option>
            <?php endwhile; ?>
          </select>

          <div class="cart-items" id="cartItems">
            <div class="cart-empty">Chưa có sản phẩm nào trong giỏ hàng</div>
          </div>

          <div class="cart-summary">
            <div class="cart-total">
              <span>Tổng tiền:</span>
              <span class="total-amount" id="totalAmount">0₫</span>
            </div>
            <button type="submit" name="create_order" class="checkout-btn" id="checkoutBtn" disabled>Tạo đơn hàng</button>
          </div>
        </div>
      </div>
    </form>

    <div class="recent-orders">
      <h3>Đơn hàng gần đây</h3>
      <table>
        <thead>
          <tr>
            <th>Mã ĐH</th>
            <th>Khách hàng</th>
            <th>Ngày lập</th>
            <th>Số mặt hàng</th>
            <th>Số lượng</th>
            <th>Tổng tiền</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($order = $resultRecentOrders->fetch_assoc()): ?>
            <tr>
              <td>#<?= str_pad($order['MaHD'], 5, '0', STR_PAD_LEFT) ?></td>
              <td><?= htmlspecialchars($order['TenKH']) ?></td>
              <td><?= date('d/m/Y', strtotime($order['NgayLap'])) ?></td>
              <td><?= $order['SoMatHang'] ?></td>
              <td><?= $order['TongSoLuong'] ?></td>
              <td><?= number_format($order['TongTien'], 0, ',', '.') ?>₫</td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Invoice Modal -->
    <div class="invoice-modal" id="invoiceModal">
      <div class="invoice-content">
        <div id="invoiceContent"></div>
        <div class="invoice-actions">
          <button class="close-btn" id="closeInvoice">Đóng</button>
          <button class="export-btn" id="exportInvoice">Xuất CSV</button>
          <button class="print-btn" id="printInvoice">In hóa đơn</button>
        </div>
      </div>
    </div>
  </main>

  <script>
    // Define global invoice data object for export functionality
    let currentInvoiceData = null;

    // Update the event handlers for the new export button
    document.addEventListener("DOMContentLoaded", function() {
      const exportInvoiceBtn = document.getElementById("exportInvoice");

      if (exportInvoiceBtn) {
        exportInvoiceBtn.addEventListener("click", function() {
          if (currentInvoiceData) {
            exportInvoiceToCSV(currentInvoiceData);
          } else {
            alert('Không tìm thấy dữ liệu hóa đơn để xuất!');
          }
        });
      }

      // Store reference to current invoice data for export functionality
      window.originalShowInvoice = window.showInvoice;
      window.showInvoice = function(invoice) {
        currentInvoiceData = invoice;
        saveInvoiceToLocalStorage(invoice);
        if (typeof window.originalShowInvoice === 'function') {
          window.originalShowInvoice(invoice);
        }
      };
    });
  </script>
  <script src="banhang.js"></script>
</body>

</html>