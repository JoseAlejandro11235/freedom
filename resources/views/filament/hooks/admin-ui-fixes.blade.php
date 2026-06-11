<style>
    /* Filament hides main content until Alpine sets opacity — keep it visible. */
    html.fi .fi-main-ctn {
        display: flex !important;
        opacity: 1 !important;
    }

    /* Blocker: mobile sidebar veil (gray-950/50) can stick over the table. */
    html.fi .fi-sidebar-close-overlay {
        display: none !important;
        pointer-events: none !important;
        opacity: 0 !important;
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

    html.fi body,
    html.fi .fi-layout,
    html.fi .fi-main-ctn,
    html.fi .fi-main {
        pointer-events: auto !important;
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
</style>
