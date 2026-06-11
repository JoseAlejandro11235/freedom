<script>
    document.documentElement.classList.remove('dark');
    try {
        localStorage.setItem('theme', 'light');
        localStorage.setItem('isOpen', 'false');
    } catch (e) {}
</script>
