let cartItems = [];
let cartCount = 0;
let searchTimeout = null;

const cartBtn = document.getElementById("cart-btn");
const cartSidebar = document.getElementById("cart-sidebar");
const closeCartBtn = document.getElementById("close-cart");
const overlay = document.getElementById("overlay");
const cartItemsContainer = document.getElementById("cart-items");
const emptyCartMessage = document.getElementById("empty-cart-message");
const cartCountElement = document.getElementById("cart-count");
const checkoutBtn = document.getElementById("checkout-btn");

const searchInput = document.getElementById("search-input");
const searchLoading = document.getElementById("search-loading");
const initialMessage = document.getElementById("initial-message");
const productsTableContainer = document.getElementById(
  "products-table-container"
);
const productsTableBody = document.getElementById("products-table-body");
const noResults = document.getElementById("no-results");
const searchTermDisplay = document.getElementById("search-term-display");
const searchIndicator = document.getElementById("search-indicator");

function performSearch(searchTerm) {
  searchLoading.style.display = "block";
  searchIndicator.innerHTML =
    '<i class="bi bi-arrow-repeat me-2"></i>Buscando...';

  searchTermDisplay.textContent = searchTerm;

  fetch(`?ajax=1&term=${encodeURIComponent(searchTerm)}`)
    .then((response) => response.json())
    .then((data) => {
      searchLoading.style.display = "none";

      if (data.success) {
        if (data.products.length > 0) {
          searchIndicator.innerHTML = `<i class="bi bi-check-circle me-2"></i>${data.products.length} produto(s) encontrado(s)`;
        } else {
          searchIndicator.innerHTML =
            '<i class="bi bi-exclamation-circle me-2"></i>Nenhum produto encontrado';
        }

        productsTableBody.innerHTML = "";

        if (data.products.length > 0) {
          data.products.forEach((product) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                                        <td>${product.sku}</td>
                                        <td>${product.produto}</td>
                                        <td>${product.grupo}</td>
                                        <td class="text-center">
                                            <button class="add-to-cart-btn" 
                                                data-id="${product.sku}" 
                                                data-title="${product.produto}">
                                                <i class="bi bi-cart-plus me-1"></i>Adicionar
                                            </button>
                                        </td>
                                    `;
            productsTableBody.appendChild(row);
          });

          initialMessage.style.display = "none";
          productsTableContainer.style.display = "block";
          noResults.style.display = "none";

          document.querySelectorAll(".add-to-cart-btn").forEach((button) => {
            button.addEventListener("click", function () {
              addToCart(this);
            });
          });
        } else {
          initialMessage.style.display = "none";
          productsTableContainer.style.display = "none";
          noResults.style.display = "block";
        }
      } else {
        searchIndicator.innerHTML =
          '<i class="bi bi-exclamation-triangle me-2"></i>Erro ao realizar a pesquisa';
        console.error("Erro na pesquisa:", data.error);
      }
    })
    .catch((error) => {
      searchLoading.style.display = "none";
      searchIndicator.innerHTML =
        '<i class="bi bi-exclamation-triangle me-2"></i>Erro ao conectar com o servidor';
      console.error("Erro na requisição:", error);
    });
}

searchInput.addEventListener("input", function () {
  const searchTerm = this.value.trim();

  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  if (searchTerm === "") {
    searchIndicator.innerHTML =
      '<i class="bi bi-info-circle me-2"></i>Digite para começar a pesquisar';
    initialMessage.style.display = "block";
    productsTableContainer.style.display = "none";
    noResults.style.display = "none";
    searchLoading.style.display = "none";
    return;
  } else if (searchTerm.length < 2) {
    searchIndicator.innerHTML =
      '<i class="bi bi-info-circle me-2"></i>Digite pelo menos 2 caracteres';
    return;
  } else {
    searchIndicator.innerHTML =
      '<i class="bi bi-keyboard me-2"></i>Digitando...';
  }

  searchTimeout = setTimeout(() => {
    performSearch(searchTerm);
  }, 300);
});

function openCart() {
  cartSidebar.classList.add("open");
  overlay.style.display = "block";
  document.body.style.overflow = "hidden"; 
}

function closeCart() {
  cartSidebar.classList.remove("open");
  overlay.style.display = "none";
  document.body.style.overflow = "auto"; 
}

function addToCart(button) {
  const id = button.getAttribute("data-id");
  const title = button.getAttribute("data-title");

  const existingItemIndex = cartItems.findIndex((item) => item.id === id);

  if (existingItemIndex !== -1) {
    cartItems[existingItemIndex].quantity += 1;
  } else {
    cartItems.push({
      id: id,
      title: title,
      quantity: 1,
    });
  }

  updateCart();

  Swal.fire({
    position: "top-end",
    icon: "success",
    title: "Produto adicionado!",
    showConfirmButton: false,
    timer: 1000,
  });
}

function removeItem(id) {
  cartItems = cartItems.filter((item) => item.id !== id);
  updateCart();
}

function updateQuantity(id, newQuantity) {
  if (newQuantity < 1) return;

  const itemIndex = cartItems.findIndex((item) => item.id === id);
  if (itemIndex !== -1) {
    cartItems[itemIndex].quantity = newQuantity;
    updateCart();
  }
}

function updateCart() {
  cartCount = cartItems.reduce((total, item) => total + item.quantity, 0);
  cartCountElement.textContent = cartCount;

  renderCartItems();

  localStorage.setItem("cartItems", JSON.stringify(cartItems));
}

function renderCartItems() {
  const children = [...cartItemsContainer.children];
  children.forEach((child) => {
    if (child !== emptyCartMessage) {
      cartItemsContainer.removeChild(child);
    }
  });

  if (cartItems.length === 0) {
    emptyCartMessage.style.display = "block";
    return;
  } else {
    emptyCartMessage.style.display = "none";
  }

  cartItems.forEach((item) => {
    const cartItemElement = document.createElement("div");
    cartItemElement.className = "cart-item";
    cartItemElement.innerHTML = `
                        <div class="cart-item-details">
                            <div class="cart-item-title">
                                <i class="bi bi-box me-2"></i>${item.title}
                            </div>
                            <div class="cart-item-actions">
                                <div class="quantity-control">
                                    <button class="quantity-btn minus-btn" data-id="${item.id}">-</button>
                                    <input type="text" class="quantity-input" value="${item.quantity}" data-id="${item.id}">
                                    <button class="quantity-btn plus-btn" data-id="${item.id}">+</button>
                                </div>
                                <button class="remove-item" data-id="${item.id}">Remover</button>
                            </div>
                        </div>
                    `;

    cartItemsContainer.insertBefore(cartItemElement, emptyCartMessage);
  });

  document.querySelectorAll(".minus-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const id = this.getAttribute("data-id");
      const item = cartItems.find((item) => item.id === id);
      if (item) updateQuantity(id, item.quantity - 1);
    });
  });

  document.querySelectorAll(".plus-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const id = this.getAttribute("data-id");
      const item = cartItems.find((item) => item.id === id);
      if (item) updateQuantity(id, item.quantity + 1);
    });
  });

  document.querySelectorAll(".quantity-input").forEach((input) => {
    input.addEventListener("change", function () {
      const id = this.getAttribute("data-id");
      const newValue = parseInt(this.value) || 1;
      updateQuantity(id, newValue);
    });
  });

  document.querySelectorAll(".remove-item").forEach((btn) => {
    btn.addEventListener("click", function () {
      const id = this.getAttribute("data-id");
      removeItem(id);
    });
  });
}

function checkout() {
  if (cartItems.length === 0) {
    Swal.fire({
      icon: "error",
      title: "Carrinho vazio",
      text: "Adicione produtos ao carrinho antes de finalizar a requisição.",
    });
    return;
  }

  const requisicaoData = {
    items: cartItems.map((item) => ({
      sku: item.id,
      quantidade: item.quantity,
    })),
  };

  Swal.fire({
    icon: "success",
    title: "Requisição concluída!",
    text: "Sua requisição foi registrada com sucesso.",
    confirmButtonText: "OK",
  }).then(() => {
    cartItems = [];
    updateCart();
    closeCart();
  });
}

document.addEventListener("DOMContentLoaded", function () {
  const savedCart = localStorage.getItem("cartItems");
  if (savedCart) {
    try {
      cartItems = JSON.parse(savedCart);
      updateCart();
    } catch (e) {
      console.error("Erro ao carregar carrinho:", e);
      localStorage.removeItem("cartItems");
    }
  }

  searchInput.focus();

  cartBtn.addEventListener("click", openCart);

  closeCartBtn.addEventListener("click", closeCart);
  overlay.addEventListener("click", closeCart);

  checkoutBtn.addEventListener("click", checkout);
});