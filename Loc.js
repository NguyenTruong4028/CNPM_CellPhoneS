// product-filter.js
document.addEventListener("DOMContentLoaded", function () {
  // Cache DOM elements
  const searchInput = document.querySelector(".search-box input");
  const categoryFilter = document.querySelector('select[name="MaLoai"]');
  const brandFilter = document.querySelector('select[name="MaTH"]');
  const productRows = document.querySelectorAll("tbody tr");

  // Add price range filter elements
  const filterOptions = document.querySelector(".filter-options");

  // Create price range filter
  const priceFilterHtml = `
        <div class="price-range">
            <select class="filter-select" name="priceRange">
                <option value="">Tất cả mức giá</option>
                <option value="0-5000000">Dưới 5 triệu</option>
                <option value="5000000-10000000">5 - 10 triệu</option>
                <option value="10000000-20000000">10 - 20 triệu</option>
                <option value="20000000+">Trên 20 triệu</option>
            </select>
        </div>
    `;

  filterOptions.insertAdjacentHTML("beforeend", priceFilterHtml);

  // Get the added filters
  const priceFilter = document.querySelector('select[name="priceRange"]');
  const dateFilter = document.querySelector('select[name="dateRange"]');

  // Add event listeners for all filters
  searchInput.addEventListener("input", applyFilters);
  categoryFilter.addEventListener("change", applyFilters);
  brandFilter.addEventListener("change", applyFilters);
  priceFilter.addEventListener("change", applyFilters);


  // Filter function
  function applyFilters() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedCategory = categoryFilter.value;
    const selectedBrand = brandFilter.value;
    const selectedPriceRange = priceFilter.value;

    productRows.forEach((row) => {
      // Get relevant data from each row
      const productId = row.cells[0].textContent.toLowerCase();
      const productName = row.cells[1].textContent.toLowerCase();
      const productCategory = row.cells[2].textContent;
      const productBrand = row.cells[3].textContent;
      const productPrice = parseFloat(
        row.cells[4].textContent.replace(/[^\d]/g, "")
      );

      // Check if the product matches the search term (ID or name)
      const matchesSearch =
        productId.includes(searchTerm) || productName.includes(searchTerm);

      // Check if the product matches the selected category
      const matchesCategory =
        !selectedCategory ||
     productCategory===
          selectedCategory;

      // Check if the product matches the selected brand
      const matchesBrand =
        !selectedBrand ||
       productBrand === selectedBrand;

      // Check if the product matches the selected price range
      let matchesPrice = true;
      if (selectedPriceRange) {
        const [minPrice, maxPrice] = selectedPriceRange.split("-");
        if (maxPrice === "+") {
          matchesPrice = productPrice >= parseFloat(minPrice);
        } else {
          matchesPrice =
            productPrice >= parseFloat(minPrice) &&
            productPrice <= parseFloat(maxPrice);
        }
      }

      // Check if the product matches the selected date range
      // Note: For this to work properly, you'll need to add data-date attributes to your rows
      let matchesDate = true;

      // Lấy giá trị ngày được chọn từ input


   

      // Show or hide the row based on all filters
      if (
        matchesSearch &&
        matchesCategory &&
        matchesBrand &&
        matchesPrice &&
        matchesDate
      ) {
        row.style.display = "";
      } else {
        row.style.display = "none";
      }
    });
  }

  // Initialize filtering once on page load
  applyFilters();

  // Function to add data attributes to rows (should be called after fetching data)
  function updateRowDataAttributes() {
    productRows.forEach((row) => {
      const categorySelect = document.querySelector(
        `select[name="MaLoai"] option[value="${row.cells[3].textContent}"]`
      );
      if (categorySelect) {
        row.setAttribute("data-category", categorySelect.value);
      }

      const brandSelect = document.querySelector(
        `select[name="MaTH"] option[value="${row.cells[4].textContent}"]`
      );
      if (brandSelect) {
        row.setAttribute("data-brand", brandSelect.value);
      }

      // You'll need to add data-date attributes based on your actual date data
      // row.setAttribute('data-date', '2023-XX-XX');
    });
  }

  // Optional: You can enhance the script to add visual indicators for active filters
  function updateFilterIndicators() {
    const filterSelects = document.querySelectorAll(".filter-select");
    filterSelects.forEach((select) => {
      if (select.value) {
        select.classList.add("active-filter");
      } else {
        select.classList.remove("active-filter");
      }
    });
  }

  // Add event listeners for filter indicators
  const allFilters = document.querySelectorAll(".filter-select");
  allFilters.forEach((filter) => {
    filter.addEventListener("change", updateFilterIndicators);
  });
});

// Add this function to handle export to CSV (can be called from a button)
function exportToCSV() {
  const table = document.querySelector("table");
  const rows = Array.from(table.querySelectorAll("tbody tr")).filter(
    (row) => row.style.display !== "none"
  );

  // Create CSV content
  let csvContent = "ID,Tên sản phẩm,Danh mục,Thương hiệu,Giá bán,Tồn kho\n";

  rows.forEach((row) => {
    const id = row.cells[0].textContent.trim();
    const name = row.cells[2].textContent.trim();
    const category = row.cells[3].textContent.trim();
    const brand = row.cells[4].textContent.trim();
    const price = row.cells[5].textContent.trim();
    const stock = row.cells[6].textContent.trim();

    csvContent += `"${id}","${name}","${category}","${brand}","${price}","${stock}"\n`;
  });

  // Create download link
  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.setAttribute("href", url);
  link.setAttribute("download", "san-pham-cellphones.csv");
  link.style.visibility = "hidden";
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
