<script>
    (function () {
        const FIX_VERSION = '4';

        function clearLivewireErrorModal() {
            const modal = document.getElementById('livewire-error');

            if (! modal) {
                return;
            }

            try {
                if (modal.open) {
                    modal.close();
                }
            } catch (e) {}

            modal.remove();
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('pointer-events');
        }

        function forceLightTheme() {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }

        function clearStuckModalOverlays() {
            document.querySelectorAll('.fi-modal').forEach((modal) => {
                if (! modal.classList.contains('fi-modal-open')) {
                    modal.querySelectorAll('.fi-modal-close-overlay').forEach((overlay) => {
                        overlay.style.setProperty('display', 'none', 'important');
                    });
                }
            });
        }

        function registerLivewireSessionRecovery() {
            if (! window.Livewire || window.freedomLivewireSessionRecoveryRegistered) {
                return;
            }

            window.freedomLivewireSessionRecoveryRegistered = true;

            window.Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status !== 419) {
                        return;
                    }

                    preventDefault();
                    clearLivewireErrorModal();
                    window.location.reload();
                });
            });
        }

        // One-time migration of old admin UI fix state. Do not keep forcing the
        // sidebar closed — that made the mobile menu open and instantly close.
        if (localStorage.getItem('freedom-admin-ui-fix') !== FIX_VERSION) {
            Object.keys(localStorage).forEach((key) => {
                if (
                    key === 'theme' ||
                    key === 'isOpen' ||
                    key === 'isOpenDesktop' ||
                    key === 'collapsedGroups' ||
                    key.includes('sidebar')
                ) {
                    localStorage.removeItem(key);
                }
            });

            localStorage.setItem('freedom-admin-ui-fix', FIX_VERSION);
            localStorage.setItem('theme', 'light');
            // Mobile should start closed; desktop Filament manages its own state.
            localStorage.setItem('isOpen', 'false');
            localStorage.setItem('isOpenDesktop', 'true');
        }

        forceLightTheme();
        clearStuckModalOverlays();
        clearLivewireErrorModal();

        document.addEventListener('alpine:init', forceLightTheme);
        document.addEventListener('livewire:init', registerLivewireSessionRecovery);
        document.addEventListener('livewire:navigated', () => {
            forceLightTheme();
            clearStuckModalOverlays();
            clearLivewireErrorModal();
        });
    })();
</script>
