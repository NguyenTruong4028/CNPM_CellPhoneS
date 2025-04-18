<?php
include '../db.php'; // Kết nối đến cơ sở dữ liệu

// Khởi tạo biến thông báo
$notification = '';

// Xử lý khi form được submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_order'])) {
    // Lấy thông tin từ form
    $maKH = $_POST['maKH'];
    $ngayLap = date('Y-m-d');
    $products = isset($_POST['products']) ? $_POST['products'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];
    
    if (count($products) > 0) {
        // Bắt đầu transaction
        $conn->begin_transaction();
        
        try {
            // Tạo hóa đơn mới
            $sqlHoaDon = "INSERT INTO HoaDon (MaKH, NgayLap, TongTien) VALUES (?, ?, 0)";
            $stmtHoaDon = $conn->prepare($sqlHoaDon);
            $stmtHoaDon->bind_param("is", $maKH, $ngayLap);
            $stmtHoaDon->execute();
            
            // Lấy ID hóa đơn vừa tạo
            $maHD = $conn->insert_id;
            $tongTien = 0;
            
            // Thêm chi tiết hóa đơn
            $sqlCTHD = "INSERT INTO CTHD (MaHD, MaMH, SL, DGMua) VALUES (?, ?, ?, ?)";
            $stmtCTHD = $conn->prepare($sqlCTHD);
            
            // Cập nhật số lượng sản phẩm
            $sqlUpdateProduct = "UPDATE ThietBi SET SL = SL - ? WHERE MaHH = ?";
            $stmtUpdateProduct = $conn->prepare($sqlUpdateProduct);
            
            // Thêm từng sản phẩm vào chi tiết hóa đơn
            for ($i = 0; $i < count($products); $i++) {
                if ($quantities[$i] > 0) {
                    $productId = $products[$i];
                    $quantity = $quantities[$i];
                    
                    // Lấy thông tin sản phẩm
                    $sqlProduct = "SELECT DGBan, SL FROM ThietBi WHERE MaHH = ?";
                    $stmtProduct = $conn->prepare($sqlProduct);
                    $stmtProduct->bind_param("i", $productId);
                    $stmtProduct->execute();
                    $resultProduct = $stmtProduct->get_result();
                    $rowProduct = $resultProduct->fetch_assoc();
                    
                    // Kiểm tra số lượng tồn
                    if ($rowProduct['SL'] < $quantity) {
                        throw new Exception("Sản phẩm không đủ số lượng tồn kho!");
                    }
                    
                    $donGia = $rowProduct['DGBan'];
                    $thanhTien = $donGia * $quantity;
                    $tongTien += $thanhTien;
                    
                    // Thêm chi tiết hóa đơn
                    $stmtCTHD->bind_param("iiid", $maHD, $productId, $quantity, $donGia);
                    $stmtCTHD->execute();
                    
                    // Cập nhật số lượng tồn kho
                    $stmtUpdateProduct->bind_param("ii", $quantity, $productId);
                    $stmtUpdateProduct->execute();
                }
            }
            
            // Cập nhật tổng tiền hóa đơn
            $sqlUpdateTotal = "UPDATE HoaDon SET TongTien = ? WHERE MaHD = ?";
            $stmtUpdateTotal = $conn->prepare($sqlUpdateTotal);
            $stmtUpdateTotal->bind_param("di", $tongTien, $maHD);
            $stmtUpdateTotal->execute();
            
            // Commit transaction
            $conn->commit();
            
            $notification = '<div class="alert alert-success">Đã tạo đơn hàng thành công!</div>';
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $conn->rollback();
            $notification = '<div class="alert alert-danger">Lỗi: ' . $e->getMessage() . '</div>';
        }
    } else {
        $notification = '<div class="alert alert-warning">Chưa chọn sản phẩm nào!</div>';
    }
}

// Lấy danh sách khách hàng
$sqlKhachHang = "SELECT * FROM KhachHang ORDER BY TenKH";
$resultKhachHang = $conn->query($sqlKhachHang);

// Lấy danh sách sản phẩm có số lượng > 0
$sqlProducts = "SELECT tb.MaHH, tb.TenHH, tb.SL, tb.DGBan, tb.HinhAnh, 
                         ltb.TenLoai, th.TenTH
                  FROM ThietBi AS tb
                  LEFT JOIN LoaiThietBi AS ltb ON tb.MaLoai = ltb.MaLoai
                  LEFT JOIN ThuongHieu AS th ON tb.MaTH = th.MaTH
                  WHERE tb.SL > 0
                  ORDER BY tb.TenHH";
$resultProducts = $conn->query($sqlProducts);

// Lấy danh sách các đơn hàng gần đây
$sqlRecentOrders = "SELECT hd.MaHD, hd.NgayLap, hd.TongTien, kh.TenKH, 
                          COUNT(cthd.MaMH) AS SoMatHang,
                          SUM(cthd.SL) AS TongSoLuong
                   FROM HoaDon hd
                   LEFT JOIN KhachHang kh ON hd.MaKH = kh.MaKH
                   LEFT JOIN CTHD cthd ON hd.MaHD = cthd.MaHD
                   GROUP BY hd.MaHD
                   ORDER BY hd.NgayLap DESC
                   LIMIT 10";
$resultRecentOrders = $conn->query($sqlRecentOrders);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>Bán Hàng - CellphoneS</title>
  <link rel="stylesheet" href="../index.css" />
  <link rel="stylesheet" href="banhang.css" />
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

    <form method="POST" action="">
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
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const productGrid = document.getElementById('productGrid');
      const cartItems = document.getElementById('cartItems');
      const totalAmount = document.getElementById('totalAmount');
      const checkoutBtn = document.getElementById('checkoutBtn');
      const searchInput = document.getElementById('searchProduct');
      const filterCategory = document.getElementById('filterCategory');
      const filterBrand = document.getElementById('filterBrand');
      
      let cart = [];
      
      // Toggle mobile menu
      document.querySelector(".mobile-menu-toggle").addEventListener("click", function() {
        document.querySelector(".sidebar").classList.toggle("active");
      });
      
      // Thêm sản phẩm vào giỏ hàng
      productGrid.addEventListener('click', function(e) {
        const productCard = e.target.closest('.product-card');
        if (!productCard) return;
        
        const productId = productCard.dataset.id;
        const productName = productCard.dataset.name;
        const productPrice = parseFloat(productCard.dataset.price);
        const productStock = parseInt(productCard.dataset.stock);
        const productImage = productCard.dataset.image;
        
        // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
        const existingItemIndex = cart.findIndex(item => item.id === productId);
        
        if (existingItemIndex !== -1) {
          // Nếu đã có, tăng số lượng nếu còn hàng
          if (cart[existingItemIndex].quantity < productStock) {
            cart[existingItemIndex].quantity++;
          } else {
            alert('Số lượng sản phẩm đã đạt tối đa!');
            return;
          }
        } else {
          // Nếu chưa có, thêm vào giỏ hàng
          cart.push({
            id: productId,
            name: productName,
            price: productPrice,
            stock: productStock,
            image: productImage,
            quantity: 1
          });
        }
        
        updateCart();
      });
      
      // Cập nhật hiển thị giỏ hàng
      function updateCart() {
        if (cart.length === 0) {
          cartItems.innerHTML = '<div class="cart-empty">Chưa có sản phẩm nào trong giỏ hàng</div>';
          checkoutBtn.disabled = true;
        } else {
          checkoutBtn.disabled = false;
          
          let cartHTML = '';
          let total = 0;
          
          cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            
            cartHTML += `
              <div class="cart-item">
                <img src="../Them+TraCuu/${item.image}" alt="${item.name}" class="cart-item-image">
                <div class="cart-item-details">
                  <div class="cart-item-name">${item.name}</div>
                  <div class="cart-item-price">${formatCurrency(item.price)}</div>
                </div>
                <div class="cart-item-actions">
                  <div class="quantity-control">
                    <button type="button" class="quantity-btn" onclick="updateQuantity(${index}, -1)">-</button>
                    <input type="text" class="quantity-input" value="${item.quantity}" readOnly>
                    <input type="hidden" name="products[]" value="${item.id}">
                    <input type="hidden" name="quantities[]" value="${item.quantity}">
                    <button type="button" class="quantity-btn" onclick="updateQuantity(${index}, 1)">+</button>
                  </div>
                  <button type="button" class="remove-btn" onclick="removeItem(${index})">×</button>
                </div>
              </div>
            `;
          });
          
          cartItems.innerHTML = cartHTML;
          totalAmount.textContent = formatCurrency(total);
        }
      }
      
      // Định dạng tiền tệ
      function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount).replace('₫', '') + '₫';
      }
      
      // Cập nhật số lượng sản phẩm
      window.updateQuantity = function(index, change) {
        const newQuantity = cart[index].quantity + change;
        
        if (newQuantity < 1) {
          removeItem(index);
          return;
        }
        
        if (newQuantity > cart[index].stock) {
          alert('Số lượng sản phẩm không đủ!');
          return;
        }
        
        cart[index].quantity = newQuantity;
        updateCart();
      };
      
      // Xóa sản phẩm khỏi giỏ hàng
      window.removeItem = function(index) {
        cart.splice(index, 1);
        updateCart();
      };
      
      // Lọc sản phẩm
      function filterProducts() {
        const searchText = searchInput.value.toLowerCase();
        const categoryFilter = filterCategory.value;
        const brandFilter = filterBrand.value;
        
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
          const productName = card.dataset.name.toLowerCase();
          const productCategory = card.dataset.category;
          const productBrand = card.dataset.brand;
          
          const matchesSearch = productName.includes(searchText);
          const matchesCategory = categoryFilter === '' || productCategory === categoryFilter;
          const matchesBrand = brandFilter === '' || productBrand === brandFilter;
          
          if (matchesSearch && matchesCategory && matchesBrand) {
            card.style.display = 'block';
          } else {
            card.style.display = 'none';
          }
        });
      }
      
      // Thiết lập các sự kiện lọc
      searchInput.addEventListener('input', filterProducts);
      filterCategory.addEventListener('change', filterProducts);
      filterBrand.addEventListener('change', filterProducts);
    });
  </script>
</body>
</html>