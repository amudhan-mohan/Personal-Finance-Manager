<footer class="bg-white border-t border-slate-200 mt-16">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-slate-500">
            <!-- Brand and Copyright -->
            <div class="flex flex-col sm:flex-row items-center sm:space-x-3 space-y-1.5 sm:space-y-0 text-center sm:text-left">
                <div class="flex items-center space-x-2">
                    <img src="../assets/img/logo.svg" alt="<?php echo SITE_NAME; ?>" class="w-6 h-6 object-contain shrink-0">
                    <span class="font-bold text-slate-900 text-sm tracking-tight whitespace-nowrap"><?php echo SITE_NAME; ?></span>
                </div>
                <span class="hidden sm:inline text-slate-300">|</span>
                <p class="text-xs text-slate-500 whitespace-nowrap">&copy; <?php echo date('Y'); ?> All rights reserved.</p>
            </div>

            <!-- System Status & Attribution -->
            <div class="flex flex-wrap items-center justify-center sm:justify-end gap-3 pt-2 sm:pt-0">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-mono text-[11px] border border-slate-200/80 whitespace-nowrap shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 shrink-0"></span>
                    Production Ready
                </span>
                <span class="text-xs text-slate-500 whitespace-nowrap">
                    Developed by <a href="https://www.amudhanmohan.in/" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-800 hover:text-slate-950 underline underline-offset-2 transition">Amudhan Mohan</a>
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Corporate Global Toast Notification Container -->
<div id="globalToastContainer" class="fixed top-4 right-4 sm:top-5 sm:right-5 z-[9999] flex flex-col space-y-3 pointer-events-none w-[calc(100vw-2rem)] sm:w-96 max-w-full"></div>

<script>
window.showToast = function(title, message, type = 'success') {
    const container = document.getElementById('globalToastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    const isSuccess = (type === 'success');
    toast.className = 'pointer-events-auto transform transition-all duration-300 ease-out translate-y-2 opacity-0 bg-white rounded-xl border p-4 shadow-xl flex items-start space-x-3 ' + 
        (isSuccess ? 'border-emerald-200' : 'border-rose-200');

    const iconHtml = isSuccess ? `
        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
    ` : `
        <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center shrink-0 border border-rose-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>
    `;

    toast.innerHTML = `
        ${iconHtml}
        <div class="flex-1 min-w-0 pt-0.5">
            <h4 class="text-xs font-bold text-slate-900">${title}</h4>
            <p class="text-xs text-slate-600 mt-0.5 leading-snug">${message}</p>
        </div>
        <button type="button" class="text-slate-400 hover:text-slate-600 p-1" onclick="this.closest('.pointer-events-auto').remove()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    `;

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', '-translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};

// Check for cross-page / reload flash toasts
(function() {
    try {
        const flash = sessionStorage.getItem('flash_toast');
        if (flash) {
            const data = JSON.parse(flash);
            sessionStorage.removeItem('flash_toast');
            setTimeout(() => {
                window.showToast(data.title || 'Successfully Completed', data.message || 'Action executed successfully.', data.type || 'success');
            }, 180);
        }
    } catch(e) {}
})();
<?php if (!empty($_SESSION['flash_toast'])): ?>
(function() {
    setTimeout(() => {
        if (window.showToast) {
            window.showToast(
                '<?php echo addslashes($_SESSION['flash_toast']['title'] ?? 'Successfully Completed'); ?>',
                '<?php echo addslashes($_SESSION['flash_toast']['message'] ?? ''); ?>',
                '<?php echo addslashes($_SESSION['flash_toast']['type'] ?? 'success'); ?>'
            );
        }
    }, 180);
})();
<?php unset($_SESSION['flash_toast']); ?>
<?php endif; ?>
</script>
