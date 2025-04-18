<?php
// Hiển thị lỗi để debug
error_reporting(E_ALL);

ini_set('display_errors', 1);

// Debug thông tin URL
echo "<!-- Debug URL parameters: ";
print_r($_GET);
echo " -->";

include '../db.php';

// Kiểm tra xem ID sản phẩm có được truyền vào không
if (!isset($_GET['MaHH']) || empty($_GET['MaHH'])) {
    echo "<div style='color:red; padding:20px;'>Không tìm thấy ID sản phẩm!</div>";
    echo "<a href='../CellPhoneS.php'>Quay lại danh sách</a>";
    exit;
}

$raw_id = $_GET['MaHH'];


// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

try {
    // Sử dụng Prepared Statement để tránh SQL Injection
    $sql = "SELECT tb.*, ltb.TenLoai, th.TenTH, tb.NgayNhap
            FROM ThietBi AS tb
            LEFT JOIN LoaiThietBi AS ltb ON tb.MaLoai = ltb.MaLoai
            LEFT JOIN ThuongHieu AS th ON tb.MaTH = th.MaTH
            WHERE tb.MaHH = ?";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Lỗi prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("s", $raw_id); // Sử dụng "s" cho chuỗi, "i" cho số nguyên
    
    if (!$stmt->execute()) {
        throw new Exception("Lỗi execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Kiểm tra xem sản phẩm có tồn tại không
    if ($result->num_rows === 0) {
        echo "<div style='color:red; padding:20px;'>Không tìm thấy sản phẩm với ID: " . $raw_id . "</div>";
        echo "<a href='CellPhoneS.php'>Quay lại danh sách</a>";
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    // Debug dữ liệu sản phẩm
    echo "<!-- Debug Product Data: ";
    print_r($product);
    echo " -->";
    
} catch (Exception $e) {
    echo "<div style='color:red; padding:20px;'>Lỗi: " . $e->getMessage() . "</div>";
    echo "<a href='CellPhoneS.php'>Quay lại danh sách</a>";
    exit;
}
?>





<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <title>Chi tiết sản phẩm - <?= htmlspecialchars($product['TenHH']) ?></title>
    <link rel="stylesheet" href="../index.css" />
    <link rel="stylesheet" href="./chitiet.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="navbar">
                <div class="logo">
                    <img src="./imgs/LogoCPS.jpg" alt="CellphoneS Logo" />
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
            <a href="CellPhoneS.php" class="menu-item active">
                <i>📱</i>
                <span>Quản lý sản phẩm</span>
            </a>
            <a href="../BanHang/banhang.php" class="menu-item">
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
            <h2 class="page-title">Chi tiết sản phẩm</h2>
            <div class="action-buttons">
                <a href="../CellPhoneS.php" class="btn btn-secondary">Quay lại danh sách</a>
            </div>
        </div>

        <div class="product-detail">
            <div class="product-header">
                <h3>#SP<?= str_pad($product['MaHH'], 3, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($product['TenHH']) ?></h3>
            </div>

            <div class="product-content">
                <div class="product-image">
                    <?php if (!empty($product['HinhAnh'])): ?>
                        <img src="<?= htmlspecialchars($product['HinhAnh']) ?>" alt="<?= htmlspecialchars($product['TenHH']) ?>" />
                    <?php else: ?>
                        <img src="./Them+TraCuu/imgs/iphone-16e-den-thumb-600x600.jpg" alt="Không có hình ảnh" />
                    <?php endif; ?>
                </div>

                <div class="product-info">
                    <div class="info-row">
                        <div class="info-label">Mã sản phẩm:</div>
                        <div class="info-value">#SP<?= str_pad($product['MaHH'], 3, '0', STR_PAD_LEFT) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tên sản phẩm:</div>
                        <div class="info-value"><?= htmlspecialchars($product['TenHH']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Loại thiết bị:</div>
                        <div class="info-value"><?= htmlspecialchars($product['TenLoai']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Thương hiệu:</div>
                        <div class="info-value"><?= htmlspecialchars($product['TenTH']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Số hiệu:</div>
                        <div class="info-value"><?= htmlspecialchars($product['SoHieu']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Giá bán:</div>
                        <div class="info-value"><?= number_format($product['DGBan'], 0, ',', '.') ?>₫</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Số lượng tồn kho:</div>
                        <div class="info-value"><?= $product['SL'] ?></div>
                    </div>
                </div>

                <div class="product-meta">
                    <h4>Thông tin thêm</h4>
                    <div class="info-row">
                        <div class="info-label">Ngày nhập:</div>
                        <div class="info-value">
                            <?php 
                            if (isset($product['NgayNhap']) && !empty($product['NgayNhap'])) {
                                echo date('d/m/Y', strtotime($product['NgayNhap']));
                            } else {
                                echo "Chưa có thông tin";
                            }
                            ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Trạng thái:</div>
                        <div class="info-value"><?= $product['SL'] > 0 ? '<span style="color: green">Còn hàng</span>' : '<span style="color: red">Hết hàng</span>' ?></div>
                    </div>
                </div>
            </div>
            
            <br>

            <!-- Phần mô tả sản phẩm mới thêm vào -->
            <div class="product-description">
                <h4>Mô tả sản phẩm</h4>
                <div class="description-content">
                    <?php if (isset($product['MoTa']) && !empty($product['MoTa'])): ?>
                        <?= nl2br(htmlspecialchars($product['MoTa'])) ?>
                    <?php else: ?>
                        <p class="no-description">Chưa có mô tả cho sản phẩm này.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Hiển thị thông tin debug trong console
        console.log("ID sản phẩm từ URL: <?= $raw_id ?>");
        console.log("ID sản phẩm sau khi chuyển đổi: <?= $id ?>");
        console.log("Thông tin sản phẩm:", <?= json_encode($product) ?>);
    </script>
</body>

</html>