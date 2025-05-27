document.addEventListener('DOMContentLoaded', function() {
  const toggleSidebarBtn = document.getElementById('toggle-sidebar');
  const sidebar = document.querySelector('.sidebar');
  const body = document.body;
  const mobileOverlay = document.querySelector('.mobile-overlay');
  
  const isMobile = () => window.innerWidth <= 576;
  
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
  
  if (toggleSidebarBtn) {
      toggleSidebarBtn.addEventListener('click', toggleSidebar);
  }
  
  if (mobileOverlay) {
      mobileOverlay.addEventListener('click', function() {
          sidebar.classList.remove('mobile-open');
          mobileOverlay.classList.remove('active');
      });
  }
  
  const currentPath = window.location.pathname;
  const navLinks = document.querySelectorAll('.sidebar a');
  
  navLinks.forEach(link => {
      const href = link.getAttribute('href');
      if (currentPath.includes(href) && href !== '#') {
          link.classList.add('active');
      }
  });
  
  const filterTags = document.querySelectorAll('.filter-tag');
  
  filterTags.forEach(tag => {
      tag.addEventListener('click', function() {
          if (this.classList.contains('active')) {
              return;
          }
          
          filterTags.forEach(t => t.classList.remove('active'));
          
          this.classList.add('active');
          
          const filterValue = this.getAttribute('data-filter');
          filterItems(filterValue);
      });
  });
  
  const searchInput = document.querySelector('.search-input');
  if (searchInput) {
      searchInput.addEventListener('input', function() {
          const searchTerm = this.value.toLowerCase();
          searchItems(searchTerm);
      });
  }

  if (typeof initializeCharts === 'function') {
      initializeCharts();
  }
});

function filterItems(filterValue) {
  const pedidoCards = document.querySelectorAll('.pedido-card');
  
  if (pedidoCards.length === 0) return;
  
  pedidoCards.forEach(card => {
      if (filterValue === 'all') {
          card.style.display = 'block';
      } else {
          const status = card.getAttribute('data-status');
          card.style.display = status === filterValue ? 'block' : 'none';
      }
  });
}

function searchItems(searchTerm) {
  const searchableItems = document.querySelectorAll('.searchable-item');
  
  if (searchableItems.length === 0) return;
  
  searchableItems.forEach(item => {
      const searchText = item.getAttribute('data-search').toLowerCase();
      item.style.display = searchText.includes(searchTerm) ? 'block' : 'none';
  });
}

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

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content');

    function adjustLayout() {
        if (sidebar && content) {
            const sidebarWidth = sidebar.offsetWidth;
            content.style.marginLeft = sidebarWidth + 'px';
            content.style.width = `calc(100% - ${sidebarWidth}px)`;
        }
    }

    setTimeout(adjustLayout, 50); 

    window.addEventListener('resize', adjustLayout);

    const sidebarToggle = document.querySelector('.sidebar-header i.fa-bars'); 
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed'); 
            setTimeout(adjustLayout, 300); 
        });
    }
});

function initializeCharts() {
  if (typeof Chart === 'undefined') return;
  
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