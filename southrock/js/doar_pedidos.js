// Variáveis globais para o carrinho
let cartItems = [];
let cartCount = 0;
let searchTimeout = null;

// Elementos DOM
const cartBtn = document.getElementById("cart-btn");
const cartSidebar = document.getElementById("cart-sidebar");
const closeCartBtn = document.getElementById("close-cart");
const overlay = document.getElementById("overlay");
const cartItemsContainer = document.getElementById("cart-items");
const emptyCartMessage = document.getElementById("empty-cart-message");
const cartCountElement = document.getElementById("cart-count");
const checkoutBtn = document.getElementById("checkout-btn");

// Elementos de pesquisa
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

// Função para realizar pesquisa em tempo real
function performSearch(searchTerm) {
  // Mostra o indicador de carregamento
  searchLoading.style.display = "block";
  searchIndicator.innerHTML =
    '<i class="bi bi-arrow-repeat me-2"></i>Buscando...';

  // Atualiza o texto de exibição do termo pesquisado
  searchTermDisplay.textContent = searchTerm;

  // Faz a requisição AJAX
  fetch(`?ajax=1&term=${encodeURIComponent(searchTerm)}`)
    .then((response) => response.json())
    .then((data) => {
      // Esconde o indicador de carregamento
      searchLoading.style.display = "none";

      // Processa os resultados
      if (data.success) {
        // Atualiza o indicador de pesquisa
        if (data.products.length > 0) {
          searchIndicator.innerHTML = `<i class="bi bi-check-circle me-2"></i>${data.products.length} produto(s) encontrado(s)`;
        } else {
          searchIndicator.innerHTML =
            '<i class="bi bi-exclamation-circle me-2"></i>Nenhum produto encontrado';
        }

        // Limpa a tabela de resultados
        productsTableBody.innerHTML = "";

        if (data.products.length > 0) {
          // Preenche a tabela com os resultados
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

          // Mostra a tabela e esconde outros elementos
          initialMessage.style.display = "none";
          productsTableContainer.style.display = "block";
          noResults.style.display = "none";

          // Adiciona event listeners aos botões de adicionar ao carrinho
          document.querySelectorAll(".add-to-cart-btn").forEach((button) => {
            button.addEventListener("click", function () {
              addToCart(this);
            });
          });
        } else {
          // Não há resultados
          initialMessage.style.display = "none";
          productsTableContainer.style.display = "none";
          noResults.style.display = "block";
        }
      } else {
        // Erro na pesquisa
        searchIndicator.innerHTML =
          '<i class="bi bi-exclamation-triangle me-2"></i>Erro ao realizar a pesquisa';
        console.error("Erro na pesquisa:", data.error);
      }
    })
    .catch((error) => {
      // Esconde o indicador de carregamento
      searchLoading.style.display = "none";
      searchIndicator.innerHTML =
        '<i class="bi bi-exclamation-triangle me-2"></i>Erro ao conectar com o servidor';
      console.error("Erro na requisição:", error);
    });
}

// Event listener para o campo de pesquisa (com debounce)
searchInput.addEventListener("input", function () {
  const searchTerm = this.value.trim();

  // Limpa o timeout anterior
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  // Atualiza o indicador de pesquisa
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

  // Define um novo timeout (300ms de delay para evitar muitas requisições)
  searchTimeout = setTimeout(() => {
    performSearch(searchTerm);
  }, 300);
});

// Funções para manipular o carrinho

// Abrir o carrinho
function openCart() {
  cartSidebar.classList.add("open");
  overlay.style.display = "block";
  document.body.style.overflow = "hidden"; // Impedir rolagem da página
}

// Fechar o carrinho
function closeCart() {
  cartSidebar.classList.remove("open");
  overlay.style.display = "none";
  document.body.style.overflow = "auto"; // Permitir rolagem da página
}

// Adicionar item ao carrinho
function addToCart(button) {
  // Obter dados do botão usando data attributes
  const id = button.getAttribute("data-id");
  const title = button.getAttribute("data-title");

  // Verificar se o item já está no carrinho
  const existingItemIndex = cartItems.findIndex((item) => item.id === id);

  if (existingItemIndex !== -1) {
    // Aumentar quantidade
    cartItems[existingItemIndex].quantity += 1;
  } else {
    // Adicionar novo item
    cartItems.push({
      id: id,
      title: title,
      quantity: 1,
    });
  }

  updateCart();

  // Feedback visual
  Swal.fire({
    position: "top-end",
    icon: "success",
    title: "Produto adicionado!",
    showConfirmButton: false,
    timer: 1000,
  });
}

// Remover item do carrinho
function removeItem(id) {
  cartItems = cartItems.filter((item) => item.id !== id);
  updateCart();
}

// Atualizar quantidade de um item
function updateQuantity(id, newQuantity) {
  if (newQuantity < 1) return;

  const itemIndex = cartItems.findIndex((item) => item.id === id);
  if (itemIndex !== -1) {
    cartItems[itemIndex].quantity = newQuantity;
    updateCart();
  }
}

// Atualizar o carrinho na interface
function updateCart() {
  // Atualizar contador do carrinho
  cartCount = cartItems.reduce((total, item) => total + item.quantity, 0);
  cartCountElement.textContent = cartCount;

  // Atualizar itens no carrinho
  renderCartItems();

  // Salvar no localStorage para persistência
  localStorage.setItem("cartItems", JSON.stringify(cartItems));
}

// Renderizar itens do carrinho
function renderCartItems() {
  // Limpar container exceto a mensagem de carrinho vazio
  const children = [...cartItemsContainer.children];
  children.forEach((child) => {
    if (child !== emptyCartMessage) {
      cartItemsContainer.removeChild(child);
    }
  });

  // Mostrar mensagem se o carrinho estiver vazio
  if (cartItems.length === 0) {
    emptyCartMessage.style.display = "block";
    return;
  } else {
    emptyCartMessage.style.display = "none";
  }

  // Adicionar cada item ao container
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

  // Adicionar event listeners aos botões de quantidade e remoção
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

// Finalizar requisição
function checkout() {
  if (cartItems.length === 0) {
    Swal.fire({
      icon: "error",
      title: "Carrinho vazio",
      text: "Adicione produtos ao carrinho antes de finalizar a requisição.",
    });
    return;
  }

  // Preparar dados para envio
  const requisicaoData = {
    items: cartItems.map((item) => ({
      sku: item.id,
      quantidade: item.quantity,
    })),
  };

  // Aqui você pode implementar o código para enviar a requisição ao servidor
  // Por exemplo, usando fetch API para enviar os dados por AJAX

  // Simulação de requisição bem-sucedida
  Swal.fire({
    icon: "success",
    title: "Requisição concluída!",
    text: "Sua requisição foi registrada com sucesso.",
    confirmButtonText: "OK",
  }).then(() => {
    // Limpar carrinho após finalizar
    cartItems = [];
    updateCart();
    closeCart();
  });
}

// Event Listeners
document.addEventListener("DOMContentLoaded", function () {
  // Carregar carrinho do localStorage
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

  // Focar no campo de pesquisa ao carregar a página
  searchInput.focus();

  // Abrir carrinho
  cartBtn.addEventListener("click", openCart);

  // Fechar carrinho
  closeCartBtn.addEventListener("click", closeCart);
  overlay.addEventListener("click", closeCart);

  // Finalizar compra
  checkoutBtn.addEventListener("click", checkout);
});
