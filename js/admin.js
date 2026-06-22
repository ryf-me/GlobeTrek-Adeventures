/* ============================================
   Admin Panel JavaScript
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {

    /* --------------------------------------------
       Sidebar Toggle (Mobile)
       -------------------------------------------- */
    const sidebar = document.querySelector('.adm-sidebar');
    const overlay = document.querySelector('.adm-sidebar-overlay');
    const menuToggle = document.querySelector('.adm-menu-toggle');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('open');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    }

    /* --------------------------------------------
       Delete Confirmation
       -------------------------------------------- */
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            var msg = this.getAttribute('data-confirm') || 'Are you sure you want to delete this?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    /* --------------------------------------------
       Select All Checkbox
       -------------------------------------------- */
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.row-select');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
        });
    }

    /* --------------------------------------------
       Auto-hide Flash Messages
       -------------------------------------------- */
    document.querySelectorAll('.adm-alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 300);
        }, 4000);
    });

    /* --------------------------------------------
       Search Filter (client-side table search)
       -------------------------------------------- */
    var searchInput = document.getElementById('adminSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.toLowerCase();
            var tableId = this.getAttribute('data-table');
            var table = document.getElementById(tableId || 'adminTable');
            if (!table) return;
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(query) > -1 ? '' : 'none';
            });
        });
    }

    /* --------------------------------------------
       Status Change Confirmation
       -------------------------------------------- */
    document.querySelectorAll('select[data-auto-submit]').forEach(function(sel) {
        sel.addEventListener('change', function() {
            if (confirm('Update status?')) {
                this.form.submit();
            } else {
                var original = this.getAttribute('data-original');
                if (original) this.value = original;
            }
        });
    });

    /* --------------------------------------------
       Inquiries Thread Modal
       -------------------------------------------- */
    window.openAdminThread = function(id, filter) {
        var url = 'inquiries.php?thread=' + id;
        if (filter && filter !== 'all') url += '&filter=' + filter;
        window.location.href = url;
    };

    window.closeAdminThread = function(filter) {
        var url = 'inquiries.php';
        if (filter && filter !== 'all') url += '?filter=' + filter;
        window.location.href = url;
    };

    var threadModal = document.getElementById('threadModal');
    if (threadModal) {
        threadModal.addEventListener('click', function(e) {
            if (e.target === this) {
                var filter = this.getAttribute('data-filter') || 'all';
                closeAdminThread(filter);
            }
        });
    }
});
