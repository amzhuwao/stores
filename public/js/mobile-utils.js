/**
 * Mobile Navigation & UX Utilities
 * Handles responsive menu, touch interactions, and mobile-specific features
 */

document.addEventListener('DOMContentLoaded', function() {
    initMobileNavigation();
    initTableResponsiveness();
    initFormHandling();
    initTouchTargets();
});

/**
 * Initialize mobile navigation drawer
 */
function initMobileNavigation() {
    const toggle = document.querySelector('.mobile-nav-toggle');
    const drawer = document.querySelector('.mobile-nav-drawer');
    const overlay = document.querySelector('.mobile-nav-overlay');
    const closeBtn = document.querySelector('.mobile-nav-close');
    const body = document.body;
    
    if (!toggle || !drawer || !overlay) return;
    
    // Open drawer
    toggle.addEventListener('click', function() {
        drawer.classList.add('open');
        overlay.classList.add('visible');
        body.classList.add('mobile-nav-open');
    });
    
    // Close drawer
    const closeDrawer = () => {
        drawer.classList.remove('open');
        overlay.classList.remove('visible');
        body.classList.remove('mobile-nav-open');
    };
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeDrawer);
    }
    
    overlay.addEventListener('click', closeDrawer);
    
    // Close when clicking nav links
    const navLinks = drawer.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', closeDrawer);
    });
}

/**
 * Add data-label attributes to table cells for mobile display
 */
function initTableResponsiveness() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent.trim());
        
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (headers[index]) {
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });
    });
}

/**
 * Improve form handling on mobile
 */
function initFormHandling() {
    // Auto-focus next field on mobile after entering data
    const inputs = document.querySelectorAll('input[type="number"], input[type="text"]');
    
    inputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const form = this.closest('form');
                if (form) {
                    const nextInput = form.querySelector('input:not([value=""]):disabled ~ input:not([value=""]), input:disabled ~ input');
                    if (nextInput) {
                        nextInput.focus();
                    }
                }
            }
        });
    });
    
    // Ensure proper keyboard type for mobile
    const quantityInputs = document.querySelectorAll('input[type="number"]');
    quantityInputs.forEach(input => {
        input.inputMode = 'numeric';
    });
}

/**
 * Ensure touch targets meet minimum size (44x44px)
 */
function initTouchTargets() {
    if (window.innerWidth <= 768) {
        const buttons = document.querySelectorAll('button, a.btn, [role="button"]');
        buttons.forEach(btn => {
            const rect = btn.getBoundingClientRect();
            if (rect.height < 44 || rect.width < 44) {
                btn.style.minHeight = '44px';
                btn.style.minWidth = '44px';
                btn.style.display = 'inline-flex';
                btn.style.alignItems = 'center';
                btn.style.justifyContent = 'center';
            }
        });
    }
}

/**
 * Utility: Close all open dropdowns on mobile when clicking outside
 */
function initDropdownHandling() {
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            const dropdowns = document.querySelectorAll('.dropdown-menu.show');
            dropdowns.forEach(dropdown => {
                if (!dropdown.contains(e.target) && !e.target.classList.contains('dropdown-toggle')) {
                    dropdown.classList.remove('show');
                }
            });
        }
    });
}

/**
 * Make tables horizontally scrollable on mobile
 */
function makeTableScrollable() {
    const tables = document.querySelectorAll('table');
    
    if (window.innerWidth <= 768) {
        tables.forEach(table => {
            if (!table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-scroll-wrapper';
                wrapper.style.overflowX = 'auto';
                wrapper.style.webkitOverflowScrolling = 'touch';
                wrapper.style.marginBottom = '16px';
                
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }
}

/**
 * Handle viewport changes
 */
window.addEventListener('resize', function() {
    initTouchTargets();
    makeTableScrollable();
});

/**
 * Export CSV from table (mobile-friendly)
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        cols.forEach(col => {
            const text = col.innerText.replace(/"/g, '""');
            csvRow.push('"' + text + '"');
        });
        csv.push(csvRow.join(','));
    });
    
    const csvContent = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv.join('\n'));
    const link = document.createElement('a');
    link.setAttribute('href', csvContent);
    link.setAttribute('download', filename + '-' + new Date().getTime() + '.csv');
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Prevent horizontal scroll on mobile
 */
function disableHorizontalScroll() {
    if (window.innerWidth <= 768) {
        document.body.style.overflowX = 'hidden';
        document.documentElement.style.overflowX = 'hidden';
    }
}

disableHorizontalScroll();
window.addEventListener('resize', disableHorizontalScroll);

/**
 * Smooth scrolling for anchor links
 */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
