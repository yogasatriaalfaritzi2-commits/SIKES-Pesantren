/* SIKES Pondok Pesantren - JS global */
document.addEventListener('DOMContentLoaded', function () {
    const toggle   = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (toggle) {
        toggle.addEventListener('click', function () {
            if (window.innerWidth < 992) {
                document.body.classList.toggle('sidebar-open');
            } else {
                document.body.classList.toggle('sidebar-collapsed');
            }
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            document.body.classList.remove('sidebar-open');
        });
    }

    // ===== Modal konfirmasi hapus global =====
    // Semua <form class="form-delete" data-name="..."> akan membuka modal ini dulu.
    let pendingDeleteForm = null;

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.matches('form.form-delete')) return;
        e.preventDefault();
        pendingDeleteForm = form;
        const label = document.getElementById('deleteEntityName');
        if (label) label.textContent = form.dataset.name || 'data ini';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmDeleteModal')).show();
    });

    const confirmBtn = document.getElementById('deleteConfirmBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (pendingDeleteForm) pendingDeleteForm.submit();
        });
    }
});