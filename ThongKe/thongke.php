<?php
include '../db.php'; // Kết nối đến cơ sở dữ liệu

// Xử lý filter theo ngày nếu có
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Truy vấn tổng doanh thu từ bảng HoaDon
$queryTotalRevenue = "SELECT SUM(TongTien) as TotalRevenue FROM HoaDon WHERE NgayLap BETWEEN '$startDate' AND '$endDate'";
$resultTotalRevenue = mysqli_query($conn, $queryTotalRevenue);
$rowTotalRevenue = mysqli_fetch_assoc($resultTotalRevenue);
$totalRevenue = number_format($rowTotalRevenue['TotalRevenue'] / 1000000, 2) . "tr";

// Truy vấn tổng số đơn hàng
$queryTotalOrders = "SELECT COUNT(*) as TotalOrders FROM HoaDon WHERE NgayLap BETWEEN '$startDate' AND '$endDate'";
$resultTotalOrders = mysqli_query($conn, $queryTotalOrders);
$rowTotalOrders = mysqli_fetch_assoc($resultTotalOrders);
$totalOrders = $rowTotalOrders['TotalOrders'];

// Đếm số khách hàng mới (giả sử khách hàng mới là những người có đơn hàng đầu tiên trong khoảng thời gian được chọn)
$queryNewCustomers = "SELECT COUNT(DISTINCT kh.MaKH) as NewCustomers 
                     FROM KhachHang kh 
                     JOIN HoaDon hd ON kh.MaKH = hd.MaKH 
                     WHERE hd.NgayLap BETWEEN '$startDate' AND '$endDate'
                     AND hd.MaKH NOT IN (SELECT MaKH FROM HoaDon WHERE NgayLap < '$startDate')";
$resultNewCustomers = mysqli_query($conn, $queryNewCustomers);
$rowNewCustomers = mysqli_fetch_assoc($resultNewCustomers);
$newCustomers = $rowNewCustomers['NewCustomers'];

// Đếm số sản phẩm đã bán
$queryProductsSold = "SELECT SUM(cthd.SL) as ProductsSold 
                     FROM CTHD cthd 
                     JOIN HoaDon hd ON cthd.MaHD = hd.MaHD 
                     WHERE hd.NgayLap BETWEEN '$startDate' AND '$endDate'";
$resultProductsSold = mysqli_query($conn, $queryProductsSold);
$rowProductsSold = mysqli_fetch_assoc($resultProductsSold);
$productsSold = $rowProductsSold['ProductsSold'];

// Truy vấn dữ liệu biểu đồ doanh thu theo thời gian
$queryRevenueChart = "SELECT DATE_FORMAT(NgayLap, '%d/%m') as date, 
                     SUM(TongTien)/1000000 as value 
                     FROM HoaDon 
                     WHERE NgayLap BETWEEN '$startDate' AND '$endDate' 
                     GROUP BY DATE_FORMAT(NgayLap, '%d/%m') 
                     ORDER BY NgayLap";
$resultRevenueChart = mysqli_query($conn, $queryRevenueChart);

$revenueData = array();
$maxRevenue = 0;

// Xử lý dữ liệu cho biểu đồ
while ($row = mysqli_fetch_assoc($resultRevenueChart)) {
    $value = $row['value'];
    if ($value > $maxRevenue) {
        $maxRevenue = $value;
    }
    
    $revenueData[] = [
        'date' => $row['date'],
        'value' => number_format($value, 1) . 'tr',
        'rawValue' => $value
    ];
}

// Tính toán vị trí trên biểu đồ dựa trên giá trị
foreach ($revenueData as $key => $data) {
    // Tính toán vị trí y (270 là điểm gốc dưới cùng, giảm 200 đơn vị để có khoảng cách hiển thị)
    $position = 270 - ($data['rawValue'] / $maxRevenue * 200);
    $revenueData[$key]['position'] = $position;
}

// Truy vấn top sản phẩm bán chạy
$queryTopProducts = "SELECT tb.TenHH, SUM(cthd.SL) as Sold, SUM(cthd.SL * cthd.DGMua)/1000000 as Revenue
                    FROM CTHD cthd
                    JOIN ThietBi tb ON cthd.MaMH = tb.MaHH
                    JOIN HoaDon hd ON cthd.MaHD = hd.MaHD
                    WHERE hd.NgayLap BETWEEN '$startDate' AND '$endDate'
                    GROUP BY tb.MaHH
                    ORDER BY Sold DESC
                    LIMIT 5";
$resultTopProducts = mysqli_query($conn, $queryTopProducts);

$topProducts = array();
while ($row = mysqli_fetch_assoc($resultTopProducts)) {
    $revenue = $row['Revenue'];
    $revenueFormatted = ($revenue >= 1) ? number_format($revenue, 2) . 'tr' : number_format($revenue * 1000, 0) . ' ngàn';
    
    $topProducts[] = [
        'name' => $row['TenHH'],
        'sold' => $row['Sold'],
        'revenue' => $revenueFormatted
    ];
}

// Truy vấn danh mục bán chạy
$queryTopCategories = "SELECT ltb.TenLoai, SUM(cthd.SL) as Sold, SUM(cthd.SL * cthd.DGMua)/1000000 as Revenue
                      FROM CTHD cthd
                      JOIN ThietBi tb ON cthd.MaMH = tb.MaHH
                      JOIN LoaiThietBi ltb ON tb.MaLoai = ltb.MaLoai
                      JOIN HoaDon hd ON cthd.MaHD = hd.MaHD
                      WHERE hd.NgayLap BETWEEN '$startDate' AND '$endDate'
                      GROUP BY ltb.MaLoai
                      ORDER BY Sold DESC
                      LIMIT 5";
$resultTopCategories = mysqli_query($conn, $queryTopCategories);

$topCategories = array();
while ($row = mysqli_fetch_assoc($resultTopCategories)) {
    $revenue = $row['Revenue'];
    $revenueFormatted = ($revenue >= 1) ? number_format($revenue, 2) . 'tr' : number_format($revenue * 1000, 0) . ' ngàn';
    
    $topCategories[] = [
        'name' => $row['TenLoai'],
        'sold' => $row['Sold'],
        'revenue' => $revenueFormatted
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CellphoneS - Thống kê</title>
    <link rel="stylesheet" href="thongke.css" />
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
        <a href="../BanHang/banhang.php" class="menu-item">
          <i>🛒</i>
          <span>Bán hàng</span>
        </a>
        <a href="#" class="menu-item active">
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
        <h2 class="page-title">Thống kê doanh thu & kinh doanh</h2>
        <div class="action-buttons">
          <button class="btn btn-secondary">Xuất báo cáo</button>
        </div>
      </div>

      <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" class="date-filter">
        <label>
          Từ ngày:
          <input type="date" name="start_date" value="<?php echo $startDate; ?>" />
        </label>
        <label>
          Đến ngày:
          <input type="date" name="end_date" value="<?php echo $endDate; ?>" />
        </label>
        <button type="submit" class="filter-btn">Lọc</button>
      </form>

      <div class="stats-cards">
        <div class="stats-card">
          <div class="stats-card-icon">💰</div>
          <div class="stats-card-value"><?php echo $totalRevenue; ?></div>
          <div class="stats-card-label">Tổng doanh thu</div>
        </div>
        <div class="stats-card">
          <div class="stats-card-icon">📦</div>
          <div class="stats-card-value"><?php echo $totalOrders; ?></div>
          <div class="stats-card-label">Đơn hàng</div>
        </div>
        <div class="stats-card">
          <div class="stats-card-icon">👤</div>
          <div class="stats-card-value"><?php echo $newCustomers; ?></div>
          <div class="stats-card-label">Khách hàng mới</div>
        </div>
        <div class="stats-card">
          <div class="stats-card-icon">📱</div>
          <div class="stats-card-value"><?php echo $productsSold; ?></div>
          <div class="stats-card-label">Sản phẩm đã bán</div>
        </div>
      </div>

      <div class="stats-chart-container">
        <div class="chart-card">
          <h3>Doanh thu theo thời gian</h3>
          <div class="chart-container">
            <svg viewBox="0 0 800 300" xmlns="http://www.w3.org/2000/svg">
              <!-- Grid lines -->
              <line x1="50" y1="270" x2="750" y2="270" stroke="#e0e0e0" stroke-width="1"/>
              <line x1="50" y1="220" x2="750" y2="220" stroke="#e0e0e0" stroke-width="1"/>
              <line x1="50" y1="170" x2="750" y2="170" stroke="#e0e0e0" stroke-width="1"/>
              <line x1="50" y1="120" x2="750" y2="120" stroke="#e0e0e0" stroke-width="1"/>
              <line x1="50" y1="70" x2="750" y2="70" stroke="#e0e0e0" stroke-width="1"/>
              
              <!-- Y-axis -->
              <line x1="50" y1="30" x2="50" y2="270" stroke="#333" stroke-width="1"/>
              
              <!-- X-axis -->
              <line x1="50" y1="270" x2="750" y2="270" stroke="#333" stroke-width="1"/>
              
              <!-- Y-axis labels -->
              <text x="40" y="270" text-anchor="end" font-size="12">0</text>
              <text x="40" y="220" text-anchor="end" font-size="12"><?php echo number_format($maxRevenue * 0.25, 1); ?>tr</text>
              <text x="40" y="170" text-anchor="end" font-size="12"><?php echo number_format($maxRevenue * 0.5, 1); ?>tr</text>
              <text x="40" y="120" text-anchor="end" font-size="12"><?php echo number_format($maxRevenue * 0.75, 1); ?>tr</text>
              <text x="40" y="70" text-anchor="end" font-size="12"><?php echo number_format($maxRevenue, 1); ?>tr</text>
              
              <!-- X-axis labels and data points -->
              <?php
              $points = '';
              $path = '';
              $xPos = 100;
              $xStep = min(650 / count($revenueData), 50); // Adjust step size based on data points
              
              foreach ($revenueData as $index => $data) {
                  // X-axis labels
                  echo '<text x="' . $xPos . '" y="290" text-anchor="middle" font-size="12">' . $data['date'] . '</text>';
                  
                  // Build points for polyline
                  $points .= $xPos . ',' . $data['position'] . ' ';
                  
                  // Build path for filled area
                  if ($index === 0) {
                      $path .= 'M' . $xPos . ',' . $data['position'] . ' ';
                  } else {
                      $path .= 'L' . $xPos . ',' . $data['position'] . ' ';
                  }
                  
                  // Data points with values
                  echo '<circle cx="' . $xPos . '" cy="' . $data['position'] . '" r="4" fill="#d70018" />';
                  echo '<text x="' . $xPos . '" y="' . ($data['position'] - 10) . '" text-anchor="middle" font-size="11" fill="#333">' . $data['value'] . '</text>';
                  
                  $xPos += $xStep;
              }
              
              // Complete the path for filled area
              $path .= 'L' . ($xPos - $xStep) . ',270 L100,270 Z';
              ?>
              
              <!-- Data line -->
              <polyline
                fill="none"
                stroke="#d70018"
                stroke-width="2"
                points="<?php echo $points; ?>"
              />
              
              <!-- Data area -->
              <path
                fill="#d7001833"
                d="<?php echo $path; ?>"
              />
            </svg>
          </div>
        </div>
      </div>
      
      <div class="stats-tables">
        <div class="table-card">
          <h3>Sản phẩm bán chạy nhất</h3>
          <table class="data-table">
            <thead>
              <tr>
                <th>Sản phẩm</th>
                <th>Đã bán</th>
                <th>Doanh thu</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($topProducts as $product): ?>
              <tr>
                <td><?php echo $product['name']; ?></td>
                <td><?php echo $product['sold']; ?></td>
                <td><?php echo $product['revenue']; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        
        <div class="table-card">
          <h3>Danh mục bán chạy</h3>
          <table class="data-table">
            <thead>
              <tr>
                <th>Danh mục</th>
                <th>Đã bán</th>
                <th>Doanh thu</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($topCategories as $category): ?>
              <tr>
                <td><?php echo $category['name']; ?></td>
                <td><?php echo $category['sold']; ?></td>
                <td><?php echo $category['revenue']; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <script>
      // Toggle mobile menu
      document
        .querySelector(".mobile-menu-toggle")
        .addEventListener("click", function () {
          document.querySelector(".sidebar").classList.toggle("active");
        });
    </script>
  </body>
</html>