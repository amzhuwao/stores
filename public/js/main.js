// Main JavaScript File for Hotel Stores Management System

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize popovers
    initializePopovers();

    // Setup mobile navigation drawer
    setupMobileNavigation();
    
    // Setup AJAX for forms
    setupAjaxForms();
    
    // Setup delete confirmations
    setupDeleteConfirmations();
});

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize Bootstrap popovers
 */
function initializePopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Setup mobile navigation drawer
 */
function setupMobileNavigation() {
    const drawer = document.getElementById('mobileNavDrawer');
    const overlay = document.querySelector('.mobile-nav-overlay');
    const toggleButtons = document.querySelectorAll('[data-mobile-nav-toggle]');
    const closeButtons = document.querySelectorAll('[data-mobile-nav-close]');

    if (!drawer || toggleButtons.length === 0) {
        return;
    }

    const openDrawer = () => {
        drawer.classList.add('open');
        if (overlay) {
            overlay.classList.add('visible');
        }
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mobile-nav-open');
    };

    const closeDrawer = () => {
        drawer.classList.remove('open');
        if (overlay) {
            overlay.classList.remove('visible');
        }
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mobile-nav-open');
    };

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (drawer.classList.contains('open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });
    });

    closeButtons.forEach(button => {
        button.addEventListener('click', closeDrawer);
    });

    drawer.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });
}

/**
 * Setup AJAX form submissions
 */
function setupAjaxForms() {
    const forms = document.querySelectorAll('.ajax-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: this.method,
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    }
                    if (data.callback) {
                        eval(data.callback);
                    }
                } else {
                    showAlert(data.message || 'An error occurred', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            });
        });
    });
}

/**
 * Setup delete confirmations
 */
function setupDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.dataset.confirmDelete || 'Are you sure you want to delete this item?';
            
            if (confirm(message)) {
                window.location.href = this.href;
            }
        });
    });
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    const alertId = 'alert-' + Math.random().toString(36).substr(2, 9);
    const alertClass = 'alert-' + (type === 'error' ? 'danger' : type);
    
    const html = `
        <div id="${alertId}" class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const container = document.querySelector('.main-content');
    if (container) {
        const alertDiv = document.createElement('div');
        alertDiv.innerHTML = html;
        container.prepend(alertDiv.firstElementChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

/**
 * Format date
 */
function formatDate(date, format = 'YYYY-MM-DD') {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    
    return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day);
}

/**
 * API Request helper
 */
async function apiRequest(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(url, finalOptions);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'API Error');
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

/**
 * Validate form
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        const value = input.value.trim();
        
        if (!value) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Export table to CSV
 */
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    let rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        let csvRow = [];
        row.querySelectorAll('td, th').forEach(col => {
            csvRow.push(col.innerText);
        });
        csv.push(csvRow.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
}

/**
 * Download CSV file
 */
function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename || 'export.csv';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

/**
 * Print content
 */
function printContent(contentId) {
    const content = document.getElementById(contentId);
    if (!content) return;
    
    const printWindow = window.open('', '', 'width=900,height=600');
    printWindow.document.write('<html><head><title>Print</title>');
    printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">');
    printWindow.document.write('<style>body { padding: 20px; } .no-print { display: none; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(content.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    
    setTimeout(() => {
        printWindow.print();
    }, 250);
}

/**
 * Clone row in form (for line items)
 */
function cloneRow(buttonElement) {
    const row = buttonElement.closest('tr');
    if (!row) return;
    
    const clone = row.cloneNode(true);
    
    // Clear input values in cloned row
    clone.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.type !== 'hidden') {
            el.value = '';
        }
    });
    
    row.parentNode.insertBefore(clone, row.nextSibling);
}

/**
 * Remove row from form
 */
function removeRow(buttonElement) {
    const row = buttonElement.closest('tr');
    if (!row) return;
    
    const table = row.closest('table');
    const rows = table.querySelectorAll('tbody tr');
    
    if (rows.length > 1) {
        row.remove();
    } else {
        alert('Cannot remove the last row');
    }
}
