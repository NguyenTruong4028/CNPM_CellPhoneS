// Updated showInvoice function for banhang.js
function showInvoice(invoice) {
  try {
    let itemsHTML = '';
    if (!invoice.items || !Array.isArray(invoice.items)) {
      throw new Error('Danh sách sản phẩm không hợp lệ');
    }

    // Add invoice items with improved formatting
    invoice.items.forEach((item, index) => {
      itemsHTML += `
        <tr>
          <td>${index + 1}</td>
          <td>${item.name || 'Không xác định'}</td>
          <td class="text-center">${item.quantity || 0}</td>
          <td class="text-right">${formatCurrency(item.price || 0)}</td>
          <td class="text-right">${formatCurrency((item.price || 0) * (item.quantity || 0))}</td>
        </tr>
      `;
    });

    // Format current date and time
    const now = new Date();
    const formattedDateTime = now.toLocaleDateString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });

    // Generate invoice content with more details
    invoiceContent.innerHTML = `
      <div class="invoice-header">
        <div class="company-info">
          <img src="../Them+TraCuu/imgs/LogoCPS.jpg" alt="CellphoneS Logo" class="invoice-logo">
          <h1>CellphoneS</h1>
        </div>
        <h2>HÓA ĐƠN BÁN HÀNG</h2>
        <div class="invoice-details">
          <table class="invoice-info-table">
            <tr>
              <td><strong>Mã hóa đơn:</strong></td>
              <td>${invoice.id || 'N/A'}</td>
            </tr>
            <tr>
              <td><strong>Ngày lập:</strong></td>
              <td>${invoice.date || formattedDateTime}</td>
            </tr>
            <tr>
              <td><strong>Khách hàng:</strong></td>
              <td>${invoice.customer || 'Khách lẻ'}</td>
            </tr>
          </table>
        </div>
      </div>
      <table class="invoice-table">
        <thead>
          <tr>
            <th style="width: 5%">STT</th>
            <th style="width: 45%">Tên sản phẩm</th>
            <th style="width: 10%" class="text-center">Số lượng</th>
            <th style="width: 20%" class="text-right">Đơn giá</th>
            <th style="width: 20%" class="text-right">Thành tiền</th>
          </tr>
        </thead>
        <tbody>
          ${itemsHTML}
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" class="text-right"><strong>Tổng tiền:</strong></td>
            <td class="text-right total-amount">${formatCurrency(invoice.total || 0)}</td>
          </tr>
        </tfoot>
      </table>
      <div class="invoice-footer">
        <div class="signature-section">
          <div class="signature-box">
            <p>Người bán hàng</p>
            <div class="signature-line"></div>
          </div>
          <div class="signature-box">
            <p>Khách hàng</p>
            <div class="signature-line"></div>
          </div>
        </div>
        <div class="thank-you-message">
          <p>Cảm ơn quý khách đã mua hàng tại CellphoneS!</p>
          <p>Hotline: 1800 2097 | Website: cellphones.com.vn</p>
        </div>
      </div>
    `;

    // Display the invoice modal
    if (invoiceModal) {
      invoiceModal.style.display = 'block';
      console.log('Invoice modal displayed');
    } else {
      console.error('Không tìm thấy invoiceModal');
    }
  } catch (error) {
    console.error('Error in showInvoice:', error);
    alert('Lỗi khi hiển thị hóa đơn: ' + error.message);
  }
}

// Enhanced print function
function printInvoice() {
  const printContent = invoiceContent.innerHTML;
  const printWindow = window.open('', '_blank');
  printWindow.document.write(`
    <html>
      <head>
        <title>In hóa đơn - CellphoneS</title>
        <meta charset="UTF-8">
        <style>
          @page {
            size: A4;
            margin: 15mm;
          }
          body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 210mm;
            margin: 0 auto;
            line-height: 1.5;
            color: #333;
          }
          .company-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
          }
          .invoice-logo {
            max-height: 60px;
            margin-right: 15px;
          }
          .company-info h1 {
            margin: 0;
            font-size: 24px;
            color: #d70018;
          }
          .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
          }
          .invoice-header h2 {
            margin: 10px 0;
            font-size: 22px;
            text-transform: uppercase;
            color: #333;
          }
          .invoice-info-table {
            width: 100%;
            margin-bottom: 15px;
          }
          .invoice-info-table td {
            padding: 5px;
            vertical-align: top;
          }
          .invoice-info-table td:first-child {
            width: 150px;
          }
          .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
          }
          .invoice-table th, .invoice-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
            font-size: 14px;
          }
          .invoice-table th {
            background-color: #f2f2f2;
            font-weight: bold;
          }
          .text-right {
            text-align: right;
          }
          .text-center {
            text-align: center;
          }
          .total-amount {
            font-weight: bold;
            font-size: 16px;
          }
          .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            margin-bottom: 30px;
          }
          .signature-box {
            width: 45%;
            text-align: center;
          }
          .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
          }
          .thank-you-message {
            text-align: center;
            margin-top: 30px;
            font-style: italic;
          }
          .thank-you-message p {
            margin: 5px 0;
          }
          tfoot {
            font-weight: bold;
          }
          @media print {
            .no-print {
              display: none;
            }
          }
        </style>
      </head>
      <body>
        ${printContent}
        <div class="no-print">
          <button onclick="window.print();" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 20px auto; display: block;">
            In hóa đơn
          </button>
        </div>
        <script>
          window.onload = function() {
            // Auto print after small delay to ensure everything is loaded
            setTimeout(() => {
              window.print();
              // Don't auto close window so user can see if there were any issues
            }, 500);
          }
        </script>
      </body>
    </html>
  `);
  printWindow.document.close();
  console.log('Print window opened with enhanced styling');
}

// Update the event listener for printing
document.addEventListener("DOMContentLoaded", function() {
  const printInvoiceBtn = document.getElementById("printInvoice");
  
  if (printInvoiceBtn) {
    printInvoiceBtn.removeEventListener("click", window.printInvoice); // Remove any existing handlers
    printInvoiceBtn.addEventListener("click", printInvoice);
  } else {
    console.error('Print button not found');
  }
});

// Add a function to convert invoice data to CSV for export
function exportInvoiceToCSV(invoice) {
  try {
    // Create CSV header
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Mã hóa đơn,Ngày lập,Khách hàng,Tổng tiền\n";
    csvContent += `${invoice.id},${invoice.date},${invoice.customer},${invoice.total}\n\n`;
    
    // Add item details
    csvContent += "STT,Tên Sản Phẩm,Số Lượng,Đơn Giá,Thành Tiền\n";
    
    invoice.items.forEach((item, index) => {
      const rowData = [
        index + 1,
        item.name || 'Không xác định',
        item.quantity || 0,
        item.price || 0,
        (item.price || 0) * (item.quantity || 0)
      ];
      csvContent += rowData.join(',') + '\n';
    });
    
    // Create download link
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `hoadon_${invoice.id}_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link); // Required for Firefox
    link.click();
    document.body.removeChild(link);
    
  } catch (error) {
    console.error('Error exporting invoice to CSV:', error);
    alert('Lỗi khi xuất hóa đơn sang CSV: ' + error.message);
  }
}

// Add a function to save invoice data to local storage for backup
function saveInvoiceToLocalStorage(invoice) {
  try {
    const savedInvoices = JSON.parse(localStorage.getItem('recentInvoices') || '[]');
    // Add current invoice to the beginning
    savedInvoices.unshift({
      id: invoice.id,
      date: invoice.date,
      customer: invoice.customer,
      total: invoice.total,
      timestamp: new Date().toISOString()
    });
    
    // Keep only the last 20 invoices
    if (savedInvoices.length > 20) {
      savedInvoices.length = 20;
    }
    
    localStorage.setItem('recentInvoices', JSON.stringify(savedInvoices));
    console.log('Invoice saved to local storage');
  } catch (error) {
    console.error('Error saving invoice to local storage:', error);
  }
}