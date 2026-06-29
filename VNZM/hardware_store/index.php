<?php
require 'auth.php';
require 'db.php';

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Fetch products (with category name)
$products = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY c.name, p.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Builder — Hardware Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>

<?php include 'partials/navbar.php'; ?>

<div class="container-fluid py-4 px-4">
    <div class="row g-4">

        <!-- LEFT: Product Selector -->
        <div class="col-lg-8">
            <!-- Category Filter -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="badge-category active" data-cat="all">All</span>
                <?php foreach ($categories as $cat): ?>
                    <span class="badge-category" data-cat="<?= $cat['id'] ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <!-- Search -->
            <div class="mb-4">
                <input type="text" id="searchInput" class="form-control" placeholder="Search products...">
            </div>

            <!-- Product Grid -->
            <div class="row g-3" id="productGrid">
                <?php foreach ($products as $p): ?>
                    <div class="col-6 col-md-4 col-xl-3 product-item" data-cat="<?= $p['category_id'] ?>">
                        <div class="product-card" onclick="openQtyModal(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= $p['price'] ?>, '<?= htmlspecialchars($p['unit']) ?>')">
                            <?php if ($p['image']): ?>
                                <img src="uploads/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                            <?php else: ?>
                                <div style="width:100px;height:100px;background:#1e3a2a;border-radius:8px;margin:0 auto 0.5rem;display:flex;align-items:center;justify-content:center;font-size:2.2rem;">🔧</div>
                            <?php endif; ?>
                            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="product-price">₱<?= number_format($p['price'], 2) ?></div>
                            <div class="product-unit">per <?= htmlspecialchars($p['unit']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RIGHT: Receipt Panel -->
        <div class="col-lg-4">
            <div class="receipt-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">🧾 Receipt</h5>
                    <button class="btn btn-outline-light btn-sm" onclick="clearReceipt()">Clear</button>
                </div>

                <div class="mb-3">
                    <input type="text" id="customerName" class="form-control" placeholder="Customer name (optional)">
                </div>
                <div class="mb-3">
                    <input type="text" id="receiptNotes" class="form-control" placeholder="Notes (optional, e.g. delivery, layaway)">
                </div>

                <div id="receiptItems">
                    <p class="text-muted" style="font-size:0.85rem; color:#4a7a5a !important;" id="emptyMsg">No items added yet.</p>
                </div>

                <div class="receipt-total d-flex justify-content-between mt-2">
                    <span>Total</span>
                    <span id="receiptTotal">₱0.00</span>
                </div>

                <button class="btn btn-primary w-100 mt-3" onclick="submitReceipt()">
                    Submit Receipt
                </button>

                <div id="receiptAlert" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Quantity Modal -->
<div class="modal fade" id="qtyModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="background:#111a14; border:1px solid #1e3a2a; border-radius:12px;">
            <div class="modal-header" style="border-color:#1e3a2a;">
                <h6 class="modal-title" id="modalProductName" style="color:#00c896;"></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Quantity (<span id="modalUnit"></span>)</label>
                <input type="number" id="modalQty" class="form-control" value="1" min="0.001" step="0.001">
            </div>
            <div class="modal-footer" style="border-color:#1e3a2a;">
                <button class="btn btn-primary w-100" onclick="addToReceipt()">Add to Receipt</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let cart = [];
    let currentProduct = null;
    const qtyModal = new bootstrap.Modal(document.getElementById('qtyModal'));

    function openQtyModal(id, name, price, unit) {
        currentProduct = { id, name, price, unit };
        document.getElementById('modalProductName').textContent = name;
        document.getElementById('modalUnit').textContent = unit;
        document.getElementById('modalQty').value = 1;
        qtyModal.show();
        setTimeout(() => document.getElementById('modalQty').select(), 300);
    }

    function addToReceipt() {
        const qty = parseFloat(document.getElementById('modalQty').value);
        if (!qty || qty <= 0) return;

        const existing = cart.find(i => i.id === currentProduct.id);
        if (existing) {
            existing.qty += qty;
            existing.subtotal = existing.qty * existing.price;
        } else {
            cart.push({
                id: currentProduct.id,
                name: currentProduct.name,
                price: currentProduct.price,
                unit: currentProduct.unit,
                qty: qty,
                subtotal: qty * currentProduct.price
            });
        }

        qtyModal.hide();
        renderReceipt();
    }

    function renderReceipt() {
        const container = document.getElementById('receiptItems');
        const emptyMsg = document.getElementById('emptyMsg');

        if (cart.length === 0) {
            container.innerHTML = '<p class="text-muted" style="font-size:0.85rem; color:#4a7a5a !important;">No items added yet.</p>';
            document.getElementById('receiptTotal').textContent = '₱0.00';
            return;
        }

        let html = '';
        let total = 0;

        cart.forEach((item, index) => {
            total += item.subtotal;
            html += `
                <div class="receipt-item">
                    <div>
                        <div style="font-weight:600;">${item.name}</div>
                        <div style="color:#4a7a5a; font-size:0.78rem;">${item.qty} ${item.unit} × ₱${item.price.toFixed(2)}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="color:#00c896;">₱${item.subtotal.toFixed(2)}</span>
                        <button class="btn btn-danger btn-sm py-0 px-1" onclick="removeItem(${index})" style="font-size:0.7rem;">✕</button>
                    </div>
                </div>`;
        });

        container.innerHTML = html;
        document.getElementById('receiptTotal').textContent = '₱' + total.toFixed(2);
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderReceipt();
    }

    function clearReceipt() {
        cart = [];
        document.getElementById('customerName').value = '';
        renderReceipt();
    }

    function submitReceipt() {
        if (cart.length === 0) {
            showAlert('Add at least one item.', 'danger');
            return;
        }

        const customerName = document.getElementById('customerName').value.trim() || 'Walk-in';
        const notes = document.getElementById('receiptNotes').value.trim();

        fetch('api/submit_receipt.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ customer_name: customerName, notes: notes, items: cart })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlert('Receipt ' + data.receipt_number + ' saved!', 'success');
                clearReceipt();
                document.getElementById('receiptNotes').value = '';
            } else {
                showAlert(data.error || 'Something went wrong.', 'danger');
            }
        })
        .catch(() => showAlert('Request failed.', 'danger'));
    }

    function showAlert(msg, type) {
        const el = document.getElementById('receiptAlert');
        el.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
        setTimeout(() => el.innerHTML = '', 3000);
    }

    // Category filter
    document.querySelectorAll('.badge-category').forEach(badge => {
        badge.addEventListener('click', () => {
            document.querySelectorAll('.badge-category').forEach(b => b.classList.remove('active'));
            badge.classList.add('active');
            const cat = badge.dataset.cat;
            document.querySelectorAll('.product-item').forEach(item => {
                item.style.display = (cat === 'all' || item.dataset.cat === cat) ? '' : 'none';
            });
        });
    });

    // Search filter
    document.getElementById('searchInput').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.product-item').forEach(item => {
            const name = item.querySelector('.product-name').textContent.toLowerCase();
            item.style.display = name.includes(q) ? '' : 'none';
        });
    });
</script>
<script src="assets/progress.js"></script>
</body>
</html>
