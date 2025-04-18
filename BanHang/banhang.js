document.addEventListener("DOMContentLoaded", function () {
  const productGrid = document.getElementById("productGrid");
  const cartItems = document.getElementById("cartItems");
  const totalAmount = document.getElementById("totalAmount");
  const checkoutBtn = document.getElementById("checkoutBtn");
  const searchInput = document.getElementById("searchProduct");
  const filterCategory = document.getElementById("filterCategory");
  const filterBrand = document.getElementById("filterBrand");

  let cart = [];

  // Toggle mobile menu
  document
    .querySelector(".mobile-menu-toggle")
    .addEventListener("click", function () {
      document.querySelector(".sidebar").classList.toggle("active");
    });

  // Thêm sản phẩm vào giỏ hàng
  productGrid.addEventListener("click", function (e) {
    const productCard = e.target.closest(".product-card");
    if (!productCard) return;

    const productId = productCard.dataset.id;
    const productName = productCard.dataset.name;
    const productPrice = parseFloat(productCard.dataset.price);
    const productStock = parseInt(productCard.dataset.stock);
    const productImage = productCard.dataset.image;

    // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
    const existingItemIndex = cart.findIndex((item) => item.id === productId);

    if (existingItemIndex !== -1) {
      // Nếu đã có, tăng số lượng nếu còn hàng
      if (cart[existingItemIndex].quantity < productStock) {
        cart[existingItemIndex].quantity++;
      } else {
        alert("Số lượng sản phẩm đã đạt tối đa!");
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
        quantity: 1,
      });
    }

    updateCart();
  });

  // Cập nhật hiển thị giỏ hàng
  function updateCart() {
    if (cart.length === 0) {
      cartItems.innerHTML =
        '<div class="cart-empty">Chưa có sản phẩm nào trong giỏ hàng</div>';
      checkoutBtn.disabled = true;
    } else {
      checkoutBtn.disabled = false;

      let cartHTML = "";
      let total = 0;

      cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        cartHTML += `
          <div class="cart-item">
            <img src="../Them+TraCuu/${item.image}" alt="${
          item.name
        }" class="cart-item-image">
            <div class="cart-item-details">
              <div class="cart-item-name">${item.name}</div>
              <div class="cart-item-price">${formatCurrency(item.price)}</div>
            </div>
            <div class="cart-item-actions">
              <div class="quantity-control">
                <button type="button" class="quantity-btn" onclick="updateQuantity(${index}, -1)">-</button>
                <input type="text" class="quantity-input" value="${
                  item.quantity
                }" readOnly>
                <input type="hidden" name="products[]" value="${item.id}">
                <input type="hidden" name="quantities[]" value="${
                  item.quantity
                }">
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
    return (
      new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" })
        .format(amount)
        .replace("₫", "") + "₫"
    );
  }

  // Cập nhật số lượng sản phẩm
  window.updateQuantity = function (index, change) {
    const newQuantity = cart[index].quantity + change;

    if (newQuantity < 1) {
      removeItem(index);
      return;
    }

    if (newQuantity > cart[index].stock) {
      alert("Số lượng sản phẩm không đủ!");
      return;
    }

    cart[index].quantity = newQuantity;
    updateCart();
  };

  // Xóa sản phẩm khỏi giỏ hàng
  window.removeItem = function (index) {
    cart.splice(index, 1);
    updateCart();
  };

  // Lọc sản phẩm
  function filterProducts() {
    const searchText = searchInput.value.toLowerCase();
    const categoryFilter = filterCategory.value;
    const brandFilter = filterBrand.value;

    const productCards = document.querySelectorAll(".product-card");

    productCards.forEach((card) => {
      const productName = card.dataset.name.toLowerCase();
      const productCategory = card.dataset.category;
      const productBrand = card.dataset.brand;

      const matchesSearch = productName.includes(searchText);
      const matchesCategory =
        categoryFilter === "" || productCategory === categoryFilter;
      const matchesBrand = brandFilter === "" || productBrand === brandFilter;

      if (matchesSearch && matchesCategory && matchesBrand) {
        card.style.display = "block";
      } else {
        card.style.display = "none";
      }
    });
  }

  // Thiết lập các sự kiện lọc
  searchInput.addEventListener("input", filterProducts);
  filterCategory.addEventListener("change", filterProducts);
  filterBrand.addEventListener("change", filterProducts);
});
