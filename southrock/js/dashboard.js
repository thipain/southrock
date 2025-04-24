// Função para alternar o estado ativo dos filtros
function toggleActive(element) {
  const filters = document.querySelectorAll(".filter-tag");
  filters.forEach((filter) => {
    filter.classList.remove("active");
  });
  element.classList.add("active");

  // Aqui você pode adicionar lógica para filtrar os resultados
  // baseado no filtro selecionado
}
