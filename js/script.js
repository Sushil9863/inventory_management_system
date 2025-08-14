document.addEventListener('DOMContentLoaded', function () {
    // Initialize Bootstrap components
    initBootstrapComponents();

    // Setup UI enhancements
    setupUIEnhancements();

    // Setup table functionality
    setupTableFeatures();

    // Setup form validation
    setupFormValidation();

    // Setup page-specific buttons (Share, Export, Print)
    setupPageButtons();
});

// Initialize Bootstrap tooltips and popovers
function initBootstrapComponents() {
    // Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.forEach(function (popoverTriggerEl) {
        new bootstrap.Popover(popoverTriggerEl);
    });
}

// Add UI enhancements like hover effects and alerts
function setupUIEnhancements() {
    // Button hover effects
    document.querySelectorAll('.btn').forEach(btn => {
        btn.classList.add('btn-hover-grow');
    });

    // Alert animations and auto-dismiss
    document.querySelectorAll('.alert').forEach(alert => {
        alert.classList.add('fade-in');

        setTimeout(() => {
            alert.classList.add('fade');
            alert.addEventListener('transitionend', () => {
                alert.remove();
            });
        }, 5000);
    });
}

// Setup table features (search and sorting)
function setupTableFeatures() {
    // Table search functionality
    document.querySelectorAll('.table-search').forEach(input => {
        input.addEventListener('input', function () {
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            const searchTerm = this.value.toLowerCase();

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    });

    // Sortable tables
    document.querySelectorAll('.sortable th[data-sortable]').forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function () {
            const table = this.closest('table');
            const headerIndex = Array.prototype.indexOf.call(this.parentElement.children, this);
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const isAscending = this.classList.contains('asc');

            // Remove previous sort indicators
            table.querySelectorAll('th').forEach(th => {
                th.classList.remove('asc', 'desc');
            });

            // Sort rows
            rows.sort((a, b) => {
                const aText = a.children[headerIndex].textContent.trim();
                const bText = b.children[headerIndex].textContent.trim();

                // Try to compare as numbers if possible
                const aNum = parseFloat(aText.replace(/[^0-9.-]/g, ''));
                const bNum = parseFloat(bText.replace(/[^0-9.-]/g, ''));

                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAscending ? aNum - bNum : bNum - aNum;
                } else {
                    return isAscending ? aText.localeCompare(bText) : bText.localeCompare(aText);
                }
            });

            // Toggle sort direction
            this.classList.toggle('asc', !isAscending);
            this.classList.toggle('desc', isAscending);

            // Reappend rows in new order
            const tbody = table.querySelector('tbody');
            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));
        });
    });
}

// Form validation setup
function setupFormValidation() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                const firstInvalid = this.querySelector('.is-invalid');
                if (firstInvalid) firstInvalid.focus();
            }
        });
    });
}
// Toast notifications
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');

    toast.className = `toast show align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '11';
    document.body.appendChild(container);
    return container;
}

// Helper function to copy to clipboard
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
    document.body.removeChild(textarea);
}




// Enhanced form validation with real-time feedback
function setupFormValidation() {
    document.querySelectorAll('form').forEach(form => {
        // Create error message containers for each field
        form.querySelectorAll('input, textarea, select').forEach(field => {
            if (field.type !== 'hidden') {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                field.parentNode.insertBefore(errorDiv, field.nextSibling);
            }
        });

        // Real-time validation on input
        form.querySelectorAll('input, textarea').forEach(field => {
            field.addEventListener('input', function() {
                validateField(this);
            });
            
            // Also validate on blur (when field loses focus)
            field.addEventListener('blur', function() {
                validateField(this);
            });
        });

        // Final validation on form submit
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const errorMessages = [];
            
            // Validate all fields
            this.querySelectorAll('input, textarea, select').forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Focus on first invalid field
                const firstInvalid = this.querySelector('.is-invalid');
                if (firstInvalid) firstInvalid.focus();
            }
        });
    });
}

// Field validation rules
const validationRules = {
    name: {
        pattern: /^[a-zA-Z0-9\s]{3,}$/,
        message: 'Must be at least 3 characters (letters, numbers only)'
    },
    description: {
        pattern: /^(?!.*(\.{3,}|\*{3,}|\-{3,}|\_{3,})).{3,}$/,
        message: 'Must be meaningful (min 3 chars, no repeated special chars)'
    },
    notes: {
        pattern: /^(?!.*(\.{3,}|\*{3,}|\-{3,}|\_{3,})).{3,}$/,
        message: 'Must be meaningful (min 3 chars, no repeated special chars)'
    },
    sku: {
        pattern: /^[a-zA-Z0-9\-_]{3,}$/,
        message: 'Must be 3+ chars (letters, numbers, dashes or underscores)'
    },
    barcode: {
        pattern: /^[a-zA-Z0-9]*$/,
        message: 'Must contain only letters and numbers'
    },
    quantity: {
        pattern: /^\d+$/,
        message: 'Must be a positive number'
    },
    price: {
        pattern: /^\d+(\.\d{1,2})?$/,
        message: 'Must be a valid price (e.g. 10 or 10.99)'
    },
    cost: {
        pattern: /^\d+(\.\d{1,2})?$/,
        message: 'Must be a valid cost (e.g. 10 or 10.99)'
    },
    email: {
        pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        message: 'Must be a valid email address'
    }
};

// Validate a single field
function validateField(field) {
    if (field.type === 'hidden' || field.disabled) return true;
    
    const value = field.value.trim();
    const fieldName = field.name;
    const isRequired = field.required;
    const errorDiv = field.nextElementSibling;
    
    // Clear previous validation state
    field.classList.remove('is-invalid', 'is-valid');
    
    // Check required fields
    if (isRequired && !value) {
        field.classList.add('is-invalid');
        if (errorDiv) errorDiv.textContent = 'This field is required';
        return false;
    }
    
    // Skip validation if field is optional and empty
    if (!isRequired && !value) {
        field.classList.add('is-valid');
        if (errorDiv) errorDiv.textContent = '';
        return true;
    }
    
    // Get validation rule (try name first, then type)
    let rule = validationRules[fieldName] || 
               validationRules[field.type] || 
               { pattern: /.+/, message: 'Invalid value' };
    
    // Special case for confirm password fields
    if (fieldName.toLowerCase().includes('confirm')) {
        const originalField = field.form.querySelector(`[name="${fieldName.replace('confirm', '')}"]`);
        if (originalField && value !== originalField.value) {
            field.classList.add('is-invalid');
            if (errorDiv) errorDiv.textContent = 'Does not match the original';
            return false;
        }
    }
    
    // Apply validation pattern
    if (!rule.pattern.test(value)) {
        field.classList.add('is-invalid');
        if (errorDiv) errorDiv.textContent = rule.message;
        return false;
    }
    
    // If we got here, field is valid
    field.classList.add('is-valid');
    if (errorDiv) errorDiv.textContent = '';
    return true;
}

// Initialize validation when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setupFormValidation();
});