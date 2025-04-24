function confirmDelete(id) {
  Swal.fire({
    title: "Tem certeza?",
    text: "Você não poderá reverter esta ação!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Sim, excluir!",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = "?delete=" + id;
    }
  });
}
