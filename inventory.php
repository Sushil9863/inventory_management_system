<?php
require_once 'config.php';
requireLogin();

// Handle inventory adjustments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        die("CSRF token validation failed");
    }

    if (isset($_POST['adjust_inventory'])) {
        $productId = $_POST['product_id'] ?? 0;
        $action = $_POST['action'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $notes = $_POST['notes'] ?? '';

        try {
            // Get current quantity
            $stmt = $pdo->prepare("SELECT quantity FROM inventory WHERE product_id = ?");
            $stmt->execute([$productId]);
            $current = $stmt->fetchColumn();

            // Calculate new quantity
            $newQuantity = $current;
            switch ($action) {
                case 'add':
                    $newQuantity = $current + $quantity;
                    break;
                case 'remove':
                    $newQuantity = max(0, $current - $quantity);
                    break;
                case 'set':
                    $newQuantity = $quantity;
                    break;
            }

            // Update inventory
            $stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE product_id = ?");
            $stmt->execute([$newQuantity, $productId]);

            // Log the change
            $stmt = $pdo->prepare("
                INSERT INTO inventory_log 
                (product_id, user_id, action, old_quantity, new_quantity, notes) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $productId,
                $_SESSION['user_id'],
                $action,
                $current,
                $newQuantity,
                $notes
            ]);

            $_SESSION['message'] = "Inventory updated successfully!";
            header("Location: inventory.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error updating inventory: " . $e->getMessage();
        }
    }
}

// Get all products with inventory
$inventory = $pdo->query("
    SELECT p.id, p.name, p.sku, p.price, i.quantity, c.name as category_name
    FROM products p
    JOIN inventory i ON p.id = i.product_id
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.name
")->fetchAll();

// Get low stock items (less than 10)
$lowStock = $pdo->query("
    SELECT p.id, p.name, p.sku, i.quantity
    FROM products p
    JOIN inventory i ON p.id = i.product_id
    WHERE i.quantity < 10 AND i.quantity > 0
    ORDER BY i.quantity
    LIMIT 5
")->fetchAll();

// Get out of stock items
$outOfStock = $pdo->query("
    SELECT p.id, p.name, p.sku
    FROM products p
    JOIN inventory i ON p.id = i.product_id
    WHERE i.quantity = 0
    ORDER BY p.name
    LIMIT 5
")->fetchAll();

// Check for messages
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
    <?php include 'sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <div
            class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom border-secondary">
            <h1 class="h2"><i class="bi bi-clipboard-data"></i> Inventory</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustInventoryModal">
                    <i class="bi bi-pencil-square"></i> Adjust Inventory
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <!-- Low Stock -->
            <div class="col-md-6">
                <div class="card bg-dark-2 border-warning h-100">
                    <div class="card-header border-warning">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Low Stock</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($lowStock): ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-sm">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lowStock as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                                <td class="text-warning"><?php echo $item['quantity']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No low stock items</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Out of Stock -->
            <div class="col-md-6">
                <div class="card bg-dark-2 border-danger h-100">
                    <div class="card-header border-danger">
                        <h5 class="mb-0"><i class="bi bi-x-circle"></i> Out of Stock</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($outOfStock): ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-sm">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($outOfStock as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                                <td class="text-danger">Out of Stock</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No out of stock items</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Inventory -->
        <div class="card bg-dark-2 border-secondary">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> All Inventory</h5>
                <div class="col-md-4">
                    <input type="text" class="form-control bg-dark text-light" placeholder="Search..."
                        id="inventorySearch">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-hover" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <?php if ($item['quantity'] == 0): ?>
                                            <span class="badge bg-danger"><?php echo $item['quantity']; ?></span>
                                        <?php elseif ($item['quantity'] < 10): ?>
                                            <span class="badge bg-warning"><?php echo $item['quantity']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?php echo $item['quantity']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['quantity'] == 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php elseif ($item['quantity'] < 10): ?>
                                            <span class="badge bg-warning">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Adjust Inventory Modal -->
    <div class="modal fade" id="adjustInventoryModal" tabindex="-1" aria-labelledby="adjustInventoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark-2 text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="adjustInventoryModalLabel">Adjust Inventory</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="categorySelect" class="form-label">Category</label>
                                <select class="form-select bg-dark text-light" id="categorySelect" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
                                    foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="productSelect" class="form-label">Product</label>
                                <select class="form-select bg-dark text-light" id="productSelect" name="product_id"
                                    required disabled>
                                    <option value="">Select a category first</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="action" class="form-label">Action</label>
                            <select class="form-select bg-dark text-light" id="action" name="action" required>
                                <option value="">Select Action</option>
                                <option value="add">Add Stock</option>
                                <option value="remove">Remove Stock</option>
                                <option value="set">Set Quantity</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control bg-dark text-light" id="quantity" name="quantity"
                                min="0" required>
                            <div class="invalid-feedback">
                                Please enter a valid quantity (minimum 0)
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control bg-dark text-light" id="notes" name="notes" rows="2"></textarea>
                            <div class="invalid-feedback">
                                Notes must be meaningful (not just special characters)
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="adjust_inventory" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        // Category and product selection in the adjust inventory modal
        const categorySelect = document.getElementById('categorySelect');
        const productSelectModal = document.getElementById('productSelect'); // Changed variable name to avoid conflict

        categorySelect.addEventListener('change', function () {
            const categoryId = this.value;

            if (!categoryId) {
                productSelectModal.innerHTML = '<option value="">Select a category first</option>';
                productSelectModal.disabled = true;
                return;
            }

            // Fetch products for selected category
            fetch(`api/products.php?action=get_products_by_category&category_id=${categoryId}`)
                .then(response => response.json())
                .then(products => {
                    if (products.error) {
                        alert(products.error);
                        return;
                    }

                    productSelectModal.innerHTML = '<option value="">Select Product</option>';

                    if (products.length > 0) {
                        products.forEach(product => {
                            const option = document.createElement('option');
                            option.value = product.id;
                            option.textContent = `${product.name} (${product.sku}) - Stock: ${product.quantity}`;
                            productSelectModal.appendChild(option);
                        });
                        productSelectModal.disabled = false;
                    } else {
                        productSelectModal.innerHTML = '<option value="">No products in this category</option>';
                        productSelectModal.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load products');
                });
        });

        // Inventory table search
        document.getElementById('inventorySearch').addEventListener('input', function () {
            const term = this.value.toLowerCase();
            const rows = document.querySelectorAll('#inventoryTable tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    </script>
</body>

</html>