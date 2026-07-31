<style>
    /* Filament hides main content until Alpine sets opacity — keep it visible. */
    html.fi .fi-main-ctn {
        display: flex !important;
        opacity: 1 !important;
    }

    /* Only show modal veil when a modal is actually open. */
    html.fi .fi-modal:not(.fi-modal-open) .fi-modal-close-overlay {
        display: none !important;
        pointer-events: none !important;
    }

    /*
     * Livewire can leave a native <dialog> backdrop open after a failed request.
     * That browser top-layer blocks all clicks even when the dialog content is hidden.
     */
    dialog#livewire-error,
    dialog#livewire-error::backdrop {
        display: none !important;
        pointer-events: none !important;
        background: transparent !important;
        opacity: 0 !important;
    }

    /* Force light panel (dark mode is disabled). */
    html.fi {
        color-scheme: light !important;
    }

    html.fi.dark,
    html.fi .fi-body {
        background-color: rgb(249 250 251) !important;
        color: rgb(3 7 18) !important;
    }

    /*
     * Keep RepeatableEntry table layout on mobile (Filament stacks cells by default).
     * Horizontal scroll handles narrow viewports.
     */
    .fi-in-table-repeatable.fi-force-table-layout {
        display: block !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    .fi-in-table-repeatable.fi-force-table-layout > table {
        display: table !important;
        width: 100%;
        min-width: 36rem;
        border-collapse: separate;
        border-spacing: 0;
    }

    .fi-in-table-repeatable.fi-force-table-layout > table > thead {
        display: table-header-group !important;
    }

    .fi-in-table-repeatable.fi-force-table-layout > table > tbody {
        display: table-row-group !important;
    }

    .fi-in-table-repeatable.fi-force-table-layout > table > tbody > tr {
        display: table-row !important;
        padding: 0 !important;
        gap: 0 !important;
    }

    .fi-in-table-repeatable.fi-force-table-layout > table > tbody > tr > td {
        display: table-cell !important;
        padding: 0.5rem 0.75rem !important;
        vertical-align: middle;
        white-space: nowrap;
    }

    .fi-in-table-repeatable.fi-force-table-layout > table > thead > tr > th {
        white-space: nowrap;
    }

    .fi-in-table-repeatable.fi-force-table-layout .fi-in-entry-label {
        display: none !important;
    }
</style>
