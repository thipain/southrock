// Dashboard Navigation and UI Controls
document.addEventListener('DOMContentLoaded', function() {
  // Toggle sidebar
  const toggleSidebarBtn = document.getElementById('toggle-sidebar');
  const sidebar = document.querySelector('.sidebar');
  const body = document.body;
  const mobileOverlay = document.querySelector('.mobile-overlay');
  
  // Function to check if we're on mobile
  const isMobile = () => window.innerWidth <= 576;
  
  // Toggle sidebar function
  function toggleSidebar() {
      if (isMobile()) {
          sidebar.classList.toggle('mobile-open');
          if (mobileOverlay) {
              mobileOverlay.classList.toggle('active');
          }
      } else {
          sidebar.classList.toggle('expanded');
          body.classList.toggle('body-expanded');
      }
  }
  
  // Add click event to sidebar toggle button
  if (toggleSidebarBtn) {
      toggleSidebarBtn.addEventListener('click', toggleSidebar);
  }
  
  // Close sidebar when clicking on overlay (mobile only)
  if (mobileOverlay) {
      mobileOverlay.addEventListener('click', function() {
          sidebar.classList.remove('mobile-open');
          mobileOverlay.classList.remove('active');
      });
  }
  
  // Set active navigation item based on current page
  const currentPath = window.location.pathname;
  const navLinks = document.querySelectorAll('.sidebar a');
  
  navLinks.forEach(link => {
      const href = link.getAttribute('href');
      if (currentPath.includes(href) && href !== '#') {
          link.classList.add('active');
      }
  });
  
  // Filter tag functionality
  const filterTags = document.querySelectorAll('.filter-tag');
  
  filterTags.forEach(tag => {
      tag.addEventListener('click', function() {
          if (this.classList.contains('active')) {
              // If already active, don't do anything (optional)
              return;
          }
          
          // Remove active class from all tags
          filterTags.forEach(t => t.classList.remove('active'));
          
          // Add active class to clicked tag
          this.classList.add('active');
          
          // You could add filter functionality here
          const filterValue = this.getAttribute('data-filter');
          filterItems(filterValue);
      });
  });
  
  // Search functionality
  const searchInput = document.querySelector('.search-input');
  if (searchInput) {
      searchInput.addEventListener('input', function() {
          const searchTerm = this.value.toLowerCase();
          searchItems(searchTerm);
      });
  }

  // Initialize charts if they exist on the page
  if (typeof initializeCharts === 'function') {
      initializeCharts();
  }
});

// Filter items function (customize based on what you're filtering)
function filterItems(filterValue) {
  // Example for filtering pedidos
  const pedidoCards = document.querySelectorAll('.pedido-card');
  
  if (pedidoCards.length === 0) return;
  
  pedidoCards.forEach(card => {
      if (filterValue === 'all') {
          card.style.display = 'block';
      } else {
          // Replace with your actual status attribute
          const status = card.getAttribute('data-status');
          card.style.display = status === filterValue ? 'block' : 'none';
      }
  });
}

// Search items function (customize based on what you're searching)
function searchItems(searchTerm) {
  // Example for searching through pedidos, produtos or usuarios
  const searchableItems = document.querySelectorAll('.searchable-item');
  
  if (searchableItems.length === 0) return;
  
  searchableItems.forEach(item => {
      const searchText = item.getAttribute('data-search').toLowerCase();
      item.style.display = searchText.includes(searchTerm) ? 'block' : 'none';
  });
}

// Function to confirm delete actions
function confirmDelete(id, type = 'item') {
  Swal.fire({
      title: `Confirmar exclusão?`,
      text: `Esta ação não pode ser desfeita!`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sim, excluir',
      cancelButtonText: 'Cancelar'
  }).then((result) => {
      if (result.isConfirmed) {
          // Determine which delete action to take based on type
          if (type === 'produto') {
              window.location.href = `produtos.php?delete=${id}`;
          } else if (type === 'usuario') {
              window.location.href = `usuarios.php?delete=${id}`;
          } else if (type === 'pedido') {
              window.location.href = `pedidos.php?delete=${id}`;
          }
      }
  });
}

// Charts initialization function - Only runs if charts exist on the page
function initializeCharts() {
  // Check if Chart.js is loaded and canvas elements exist
  if (typeof Chart === 'undefined') return;
  
  // Pedidos por status chart
  const statusChartEl = document.getElementById('statusChart');
  if (statusChartEl) {
      const statusChart = new Chart(statusChartEl, {
          type: 'doughnut',
          data: {
              labels: ['Novos', 'Em Processo', 'Finalizados'],
              datasets: [{
                  data: [12, 19, 8],
                  backgroundColor: [
                      '#00254e',
                      '#3c6fb1',
                      '#7cbbed'
                  ],
                  borderWidth: 0
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: {
                      position: 'bottom'
                  }
              }
          }
      });
  }
  
  // Pedidos por mês chart
  const pedidosChartEl = document.getElementById('pedidosChart');
  if (pedidosChartEl) {
      const pedidosChart = new Chart(pedidosChartEl, {
          type: 'line',
          data: {
              labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
              datasets: [{
                  label: 'Pedidos',
                  data: [65, 59, 80, 81, 56, 55],
                  borderColor: '#3c6fb1',
                  backgroundColor: 'rgba(124, 187, 235, 0.2)',
                  tension: 0.4,
                  fill: true
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                  y: {
                      beginAtZero: true
                  }
              }
          }
      });
  }
  
  // Produtos por categoria chart
  const produtosChartEl = document.getElementById('produtosChart');
  if (produtosChartEl) {
      const produtosChart = new Chart(produtosChartEl, {
          type: 'bar',
          data: {
              labels: ['Alimentos', 'Bebidas', 'Limpeza', 'Escritório', 'Outros'],
              datasets: [{
                  label: 'Quantidade',
                  data: [12, 19, 3, 5, 2],
                  backgroundColor: '#3c6fb1'
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                  y: {
                      beginAtZero: true
                  }
              }
          }
      });
  }
}