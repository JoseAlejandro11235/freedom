<script>
    (function () {
        const FIX_VERSION = '3';

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
        }

        function applyFreedomAdminUiFix() {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            localStorage.setItem('isOpen', 'false');
            localStorage.setItem('isOpenDesktop', 'true');

            document.querySelectorAll('.fi-sidebar-close-overlay').forEach((el) => {
                el.style.setProperty('display', 'none', 'important');
                el.style.setProperty('pointer-events', 'none', 'important');
            });

            if (window.Alpine?.store('sidebar')) {
                window.Alpine.store('sidebar').isOpen = false;
            }

            document.querySelectorAll('.fi-modal').forEach((modal) => {
                if (! modal.classList.contains('fi-modal-open')) {
                    modal.querySelectorAll('.fi-modal-close-overlay').forEach((overlay) => {
                        overlay.style.setProperty('display', 'none', 'important');
                    });
                }
            });

            clearLivewireErrorModal();
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
            localStorage.setItem('isOpen', 'false');
            localStorage.setItem('isOpenDesktop', 'true');
            localStorage.setItem('theme', 'light');
        }

        applyFreedomAdminUiFix();

        document.addEventListener('alpine:init', applyFreedomAdminUiFix);
        document.addEventListener('alpine:initialized', applyFreedomAdminUiFix);
        document.addEventListener('livewire:init', registerLivewireSessionRecovery);
        document.addEventListener('livewire:navigated', applyFreedomAdminUiFix);

        let unblockTimer = null;
        const scheduleUnblock = () => {
            clearTimeout(unblockTimer);
            unblockTimer = setTimeout(applyFreedomAdminUiFix, 50);
        };

        const observer = new MutationObserver(scheduleUnblock);
        observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true });
    })();
</script>
