/**
 * File: js/admin.js
 * Purpose: Admin panel JavaScript — handles sidebar toggle, delete confirmations,
 *          select-all checkboxes, auto-hiding flash messages, client-side table
 *          search filtering, status change confirmation, and inquiry thread modal.
 * Dependencies: None (vanilla JS only)
 * Used By: All admin panel pages (admin layout includes this file)
 */

document.addEventListener('DOMContentLoaded', function() {

    // === SIDEBAR TOGGLE (MOBILE) ===
    // On small screens, the sidebar is hidden by default.
    // The hamburger button toggles the sidebar and a dark overlay
    // to give a mobile-friendly navigation experience.
    const sidebar = document.querySelector('.adm-sidebar');
    const overlay = document.querySelector('.adm-sidebar-overlay');
    const menuToggle = document.querySelector('.adm-menu-toggle');

    // Toggle sidebar open/close on hamburger button click
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('open');
        });
    }

    // Close sidebar when the dark overlay is clicked
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    }

    // === DELETE CONFIRMATION ===
    // Any element with a data-confirm attribute gets a confirmation dialog
    // before its default action proceeds (e.g., following a delete link).
    // The data-confirm value is used as the confirmation message.
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            var msg = this.getAttribute('data-confirm') || 'Are you sure you want to delete this?';
            if (!confirm(msg)) {
                e.preventDefault(); // Cancel the action if user declines
            }
        });
    });

    // === SELECT ALL CHECKBOX ===
    // The "selectAll" checkbox toggles all .row-select checkboxes in the table.
    // Used for bulk operations (e.g., bulk delete, bulk status change).
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.row-select');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
        });
    }

    // === AUTO-HIDE FLASH MESSAGES ===
    // Success/error alert messages fade out after 4 seconds and are
    // removed from the DOM to avoid cluttering the interface.
    document.querySelectorAll('.adm-alert').forEach(function(alert) {
        setTimeout(function() {
            // Fade out over 0.3 seconds
            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';
            // Remove from DOM after fade completes
            setTimeout(function() { alert.remove(); }, 300);
        }, 4000); // 4 second display time
    });

    // === SEARCH FILTER (CLIENT-SIDE TABLE SEARCH) ===
    // Filters table rows in real-time based on a search input.
    // The input's data-table attribute specifies which table to filter.
    // Rows are shown/hidden by checking if their text content contains
    // the search query (case-insensitive).
    var searchInput = document.getElementById('adminSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.toLowerCase();
            // Get the target table ID from the data-table attribute, fallback to 'adminTable'
            var tableId = this.getAttribute('data-table');
            var table = document.getElementById(tableId || 'adminTable');
            if (!table) return;
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                // Show row if query is found anywhere in its text content
                row.style.display = text.indexOf(query) > -1 ? '' : 'none';
            });
        });
    }

    // === STATUS CHANGE CONFIRMATION ===
    // Auto-submitting <select> elements (data-auto-submit) prompt for
    // confirmation before submitting the form. If the user cancels,
    // the select reverts to its original value stored in data-original.
    document.querySelectorAll('select[data-auto-submit]').forEach(function(sel) {
        sel.addEventListener('change', function() {
            if (confirm('Update status?')) {
                // Submit the parent form immediately
                this.form.submit();
            } else {
                // Revert to the previous value if cancelled
                var original = this.getAttribute('data-original');
                if (original) this.value = original;
            }
        });
    });

    // === INQUIRIES THREAD MODAL ===
    // Navigation helpers for the inquiry thread modal.
    // These redirect to the inquiries page with the appropriate
    // thread ID and filter parameters in the URL.

    /**
     * Opens an inquiry thread by navigating to the inquiries page
     * with the thread ID (and optional filter) as URL parameters.
     *
     * @param {number} id     - The thread ID to open
     * @param {string} filter - Optional filter value (e.g., 'open', 'closed')
     */
    window.openAdminThread = function(id, filter) {
        var url = 'inquiries.php?thread=' + id;
        if (filter && filter !== 'all') url += '&filter=' + filter;
        window.location.href = url;
    };

    /**
     * Closes the thread modal by navigating back to the inquiries page.
     * Preserves the current filter if one is set.
     *
     * @param {string} filter - Optional filter to preserve in the URL
     */
    window.closeAdminThread = function(filter) {
        var url = 'inquiries.php';
        if (filter && filter !== 'all') url += '?filter=' + filter;
        window.location.href = url;
    };

    // Close thread modal when clicking on the overlay background
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
