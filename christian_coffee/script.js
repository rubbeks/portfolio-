// Add to cart function with quantity support
function addToCart(productId) {
    const quantitySelect = document.getElementById(`qty_${productId}`);
    const quantity = quantitySelect ? quantitySelect.value : 1;
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Product added to cart!', 'success');
        } else {
            showAlert(data.message || 'Error adding product to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error adding product to cart', 'error');
    });
}

// Update quantity function
function updateQuantity(productId, quantity) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(() => {
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating cart', 'error');
    });
}

// Remove from cart function
function removeFromCart(productId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('product_id', productId);
        
        fetch('cart.php', {
            method: 'POST',
            body: formData
        })
        .then(() => {
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error removing item from cart', 'error');
        });
    }
}

// Show alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '80px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.maxWidth = '300px';
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 3000);
}

// Delete confirmation for admin
function confirmDelete(type, id, name) {
    if (confirm(`Are you sure you want to delete this ${type}: ${name}?`)) {
        if (type === 'user') {
            window.location.href = `manage-users.php?delete=${id}`;
        } else if (type === 'product') {
            window.location.href = `manage-products.php?delete=${id}`;
        }
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let hasErrors = false;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    hasErrors = true;
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                showAlert('Please fill in all required fields', 'error');
            }
        });
    });
    
    // Password confirmation validation
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (passwordField && confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            if (this.value !== passwordField.value) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#28a745';
            }
        });
    }
});

// Mobile menu toggle (if needed for responsive design)
function toggleMobileMenu() {
    const navMenu = document.querySelector('.nav-menu');
    navMenu.classList.toggle('active');
}