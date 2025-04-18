<?php
include '../db.php';

// Lấy danh sách loại thiết bị
$sql_loai = "SELECT * FROM LoaiThietBi";
$result_loai = $conn->query($sql_loai);

// Lấy danh sách thương hiệu
$sql_thuonghieu = "SELECT * FROM ThuongHieu";
$result_thuonghieu = $conn->query($sql_thuonghieu);

// Xử lý khi form được submit
$success_message = '';
$error_message = '';
$new_product_id = 0; // Biến để lưu ID của sản phẩm mới

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $maHH = $_POST['maHH'];
    $tenHH = $_POST['tenHH'];
    $maLoai = $_POST['maLoai'];
    $maTH = $_POST['maTH'];
    $soHieu = $_POST['soHieu'];
    $SL = $_POST['SL'];
    $ngayNhap = $_POST['ngayNhap'];
    $DGBan = $_POST['DGBan'];

    // Kiểm tra xem MaTB đã tồn tại trong cơ sở dữ liệu chưa
    $check_sql = "SELECT MaHH FROM ThietBi WHERE MaHH = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $maHH);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error_message = "Lỗi: Mã thiết bị '$maHH' đã tồn tại trong hệ thống. Vui lòng sử dụng mã khác.";
        $check_stmt->close();
    } else {
        $check_stmt->close();

        // Xử lý upload hình ảnh nếu có
        $hinhAnh = '';

        if (isset($_FILES['hinhAnh']) && $_FILES['hinhAnh']['error'] == 0) {
            $targetDir = "imgs/";
            $fileName = basename($_FILES["hinhAnh"]["name"]);
            $targetFile = $targetDir . $fileName;

            // Kiểm tra định dạng file ảnh
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES["hinhAnh"]["tmp_name"], $targetFile)) {
                    $hinhAnh = $targetFile; // lưu đường dẫn vào DB
                } else {
                    echo "Lỗi khi upload ảnh.";
                }
            } else {
                echo "Chỉ chấp nhận các file ảnh: jpg, jpeg, png, gif, webp.";
            }
        }


        // Nếu không có lỗi thì thêm vào database
        if (empty($error_message)) {
            // Chuẩn bị câu lệnh SQL
            $sql = "INSERT INTO ThietBi (MaHH, TenHH, maLoai, maTH, SoHieu, SL, NgayNhap, DGBan, HinhAnh) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            // Chuẩn bị statement
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                // Bind các tham số - thêm thông tin chi tiết vào câu lệnh SQL
                $stmt->bind_param("sssssisds", $maHH, $tenHH, $maLoai, $maTH, $soHieu, $SL, $ngayNhap, $DGBan, $hinhAnh);

                // Thực thi câu lệnh
                if ($stmt->execute()) {
                    $new_product_id = $stmt->insert_id; // Lấy ID của sản phẩm vừa thêm
                    $success_message = "Thêm sản phẩm thành công!";

                    // Chuyển hướng về trang index.php với tham số mới thêm
                    header("Location: ../CellPhoneS.php?new_product=$new_product_id");
                    exit;
                } else {
                    $error_message = "Lỗi: " . $stmt->error;
                }

                // Đóng statement
                $stmt->close();
            } else {
                $error_message = "Lỗi chuẩn bị truy vấn: " . $conn->error;
            }
        }
    }
}

// Lấy ngày hiện tại cho trường ngày nhập hàng
$default_date = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <title>Thêm Sản Phẩm Mới - CellphoneS</title>
    <link rel="stylesheet" href="them.css" />
    <link rel="stylesheet" href="style.css">

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
            <a href="index.php" class="menu-item active">
                <i>📱</i>
                <span>Quản lý sản phẩm</span>
            </a>
            <a href="#" class="menu-item">
                <i>🛒</i>
                <span>Bán hàng</span>
            </a>
            <a href="#" class="menu-item">
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
            <h2 class="page-title">Thêm sản phẩm mới</h2>
            <div class="action-buttons">
                <a href="../CellPhoneS.php" class="btn btn-secondary">← Quay lại</a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="maHH">Mã thiết bị *</label>
                    <input type="text" class="form-control" id="maHH" name="maHH" required value="<?php echo isset($maHH) ? htmlspecialchars($maHH) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="tenHH">Tên sản phẩm *</label>
                    <input type="text" class="form-control" id="tenHH" name="tenHH" required value="<?php echo isset($tenHH) ? htmlspecialchars($tenHH) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="maLoai">Loại thiết bị *</label>
                    <select name="maLoai" id="maLoai" class="form-control" required>
                        <option value="">-- Chọn loại thiết bị --</option>
                        <?php while ($row = $result_loai->fetch_assoc()): ?>
                            <option value="<?= $row['MaLoai'] ?>"
                                <?= (isset($maLoai) && $maLoai == $row['MaLoai']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['TenLoai']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="maTH">Thương hiệu *</label>
                    <select name="maTH" id="maTH" class="form-control" required>
                        <option value="">-- Chọn thương hiệu --</option>
                        <?php while ($row = $result_thuonghieu->fetch_assoc()): ?>
                            <option value="<?= $row['MaTH'] ?>"
                                <?= (isset($maTH) && $maTH == $row['MaTH']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['TenTH']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="soHieu">Số hiệu *</label>
                    <input type="text" class="form-control" id="soHieu" name="soHieu" required value="<?php echo isset($soHieu) ? htmlspecialchars($soHieu) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="SL">Số lượng nhập *</label>
                    <input type="number" class="form-control" id="SL" name="SL" min="0" required value="<?php echo isset($SL) ? $SL : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="ngayNhap">Ngày nhập hàng *</label>
                    <input type="date" class="form-control" id="ngayNhap" name="ngayNhap" required value="<?php echo isset($ngayNhap) ? $ngayNhap : $default_date; ?>">
                </div>

                <div class="form-group">
                    <label for="DGBan">Đơn giá (VNĐ) *</label>
                    <input type="number" class="form-control" id="DGBan" name="DGBan" min="0" step="1000" required value="<?php echo isset($DGBan) ? $DGBan : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="hinhAnh">Hình ảnh sản phẩm</label>
                    <input type="file" class="form-control" id="hinhAnh" name="hinhAnh" accept="image/*">
                </div>

                <div class="btn-container">
                    <button type="reset" class="btn btn-secondary">Làm mới</button>
                    <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>