<?php
// southrock/includes/status_helper.php

if (!function_exists('getStatusLabel')) {
    /**
     * Retorna um rótulo amigável para um código de status de pedido.
     * @param string $statusKey O código do status do banco de dados.
     * @return string O rótulo formatado para exibição.
     */
    function getStatusLabel($statusKey) {
        $statusLabels = [
            'novo'                                  => 'Novo',
            'processo'                              => 'Em Processo',
            'finalizado'                            => 'Finalizado',
            'aprovado'                              => 'Aprovado',
            'rejeitado'                             => 'Rejeitado',
            'cancelado'                             => 'Cancelado',
            'novo_troca_pendente_aceite_parceiro'   => 'Aguardando Filial',
            'troca_aceita_parceiro_pendente_matriz' => 'Aceito (Aguard. Matriz)'
            // Adicione outros status e seus rótulos aqui conforme necessário
        ];
        return $statusLabels[$statusKey] ?? ucfirst(str_replace('_', ' ', htmlspecialchars($statusKey)));
    }
}

if (!function_exists('getStatusBadgeClass')) {
    /**
     * Retorna as classes CSS do Bootstrap para o badge de um status de pedido.
     * Prioriza classes do Bootstrap 4 (ex: badge badge-primary).
     * @param string $statusKey O código do status do banco de dados.
     * @return string As classes CSS para o badge.
     */
    function getStatusBadgeClass($statusKey) {
        // Usando classes do Bootstrap 4 para badges por padrão, pois parece ser o mais usado no projeto.
        // Se estiver migrando para Bootstrap 5 para badges, use 'bg-primary text-white', etc.
        $statusBadgeClasses = [
            'novo'                                  => 'badge badge-primary',
            'processo'                              => 'badge badge-warning text-dark',
            'finalizado'                            => 'badge badge-success',
            'aprovado'                              => 'badge badge-info text-dark', // BS4 'badge-info' é claro, então text-dark
            'rejeitado'                             => 'badge badge-danger',
            'cancelado'                             => 'badge badge-secondary',
            'novo_troca_pendente_aceite_parceiro'   => 'badge badge-warning text-dark',
            'troca_aceita_parceiro_pendente_matriz' => 'badge badge-info text-dark'
        ];
        return $statusBadgeClasses[$statusKey] ?? 'badge badge-light text-dark'; // Fallback
    }
}

if (!function_exists('getStatusIconClass')) {
    /**
     * Retorna a classe do ícone Font Awesome para um status de pedido.
     * @param string $statusKey O código do status do banco de dados.
     * @return string A classe do ícone Font Awesome.
     */
    function getStatusIconClass($statusKey) {
        $statusIcons = [
            'novo'                                  => 'fa-file-circle-plus',
            'processo'                              => 'fa-spinner fa-spin',
            'finalizado'                            => 'fa-circle-check',
            'aprovado'                              => 'fa-thumbs-up',
            'rejeitado'                             => 'fa-times-circle',
            'cancelado'                             => 'fa-ban',
            'novo_troca_pendente_aceite_parceiro'   => 'fa-hourglass-start',
            'troca_aceita_parceiro_pendente_matriz' => 'fa-handshake'
        ];
        return $statusIcons[$statusKey] ?? 'fa-question-circle'; // Ícone padrão
    }
}

// Você pode adicionar outras funções auxiliares relacionadas a status aqui no futuro,
// como cores hexadecimais específicas se precisar delas diretamente no PHP.
// Ex:
// if (!function_exists('getStatusTextColorHex')) {
//     function getStatusTextColorHex($statusKey) {
//         $map = [
//             'novo' => '#1565C0', /* ... etc ... */
//             'novo_troca_pendente_aceite_parceiro'   => '#664D03',
//             'troca_aceita_parceiro_pendente_matriz' => '#055160'
//         ];
//         return $map[$statusKey] ?? '#212529'; // Fallback
//     }
// }
?>