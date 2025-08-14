<?php
require_once 'config.php';
requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        die("CSRF token validation failed");
    }

    if (isset($_POST['add_product'])) {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $category_id = $_POST['category_id'] ?? null;
        $price = $_POST['price'] ?? 0;
        $cost = $_POST['cost'] ?? 0;
        $barcode = $_POST['barcode'] ?? '';
        $sku = $_POST['sku'] ?? generateSku($name, $pdo);

        try {
            // Check for duplicate SKU
            $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
            $stmt->execute([$sku]);
            if ($stmt->fetch()) {
                throw new PDOException("SKU already exists. Please enter a unique SKU.");
            }

            $stmt = $pdo->prepare("INSERT INTO products (name, description, category_id, price, cost, sku, barcode) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $category_id, $price, $cost, $sku, $barcode]);
            
            $productId = $pdo->lastInsertId();
            
            // Initialize inventory with 0 quantity
            $stmt = $pdo->prepare("INSERT INTO inventory (product_id, quantity) VALUES (?, 0)");
            $stmt->execute([$productId]);
            
            $_SESSION['message'] = "Product added successfully!";
            header("Location: products.php");
            exit();
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['update_product'])) {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $category_id = $_POST['category_id'] ?? null;
        $price = $_POST['price'] ?? 0;
        $cost = $_POST['cost'] ?? 0;
        $sku = $_POST['sku'] ?? '';
        $barcode = $_POST['barcode'] ?? '';

        try {
            // Check for duplicate SKU (excluding current product)
            $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
            $stmt->execute([$sku, $id]);
            if ($stmt->fetch()) {
                throw new PDOException("SKU already exists. Please enter a unique SKU.");
            }

            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, category_id = ?, price = ?, cost = ?, sku = ?, barcode = ? WHERE id = ?");
            $stmt->execute([$name, $description, $category_id, $price, $cost, $sku, $barcode, $id]);
            
            $_SESSION['message'] = "Product updated successfully!";
            header("Location: products.php");
            exit();
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['delete_product'])) {
        $id = $_POST['id'] ?? 0;

        try {
            // First delete inventory record
            $stmt = $pdo->prepare("DELETE FROM inventory WHERE product_id = ?");
            $stmt->execute([$id]);
            
            // Then delete the product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['message'] = "Product deleted successfully!";
            header("Location: products.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error deleting product: " . $e->getMessage();
        }
    }
}

// Auto-SKU generation function
function generateSku($productName, $pdo) {
    $prefix = '';
    $words = explode(' ', strtoupper($productName));
    foreach ($words as $w) {
        $prefix .= substr($w, 0, 3);
    }
    $prefix = substr($prefix, 0, 6);
    
    do {
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $sku = $prefix . '-' . $random;
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
    } while ($stmt->fetch());
    
    return $sku;
}

// Get all products with category names
$products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.name
")->fetchAll();

// Get all categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Check for messages
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <?php include 'sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom border-secondary">
            <h1 class="h2"><i class="bi bi-box-seam"></i> Products</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus-circle"></i> Add Product
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

        <div class="card bg-dark-2 border-secondary">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-hover" id="productsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Cost</th>
                                <th>SKU</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td>$<?php echo number_format($product['cost'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info view-product" data-id="<?php echo $product['id']; ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning edit-product" data-id="<?php echo $product['id']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-product" data-id="<?php echo $product['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark-2 text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addProductForm">    
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Product Name*</label>
                                <input type="text" class="form-control bg-dark text-light" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select bg-dark text-light" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control bg-dark text-light" id="description" name="description" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label">Price*</label>
                                <input type="number" step="0.01" class="form-control bg-dark text-light" id="price" name="price" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cost" class="form-label">Cost*</label>
                                <input type="number" step="0.01" class="form-control bg-dark text-light" id="cost" name="cost" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="sku" class="form-label">SKU*</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-dark text-light" id="sku" name="sku" required>
                                    <button class="btn btn-outline-secondary" type="button" id="generateSkuBtn">
                                        <i class="bi bi-arrow-repeat"></i> Generate
                                    </button>
                                </div>
                                <small class="text-muted">Leave empty to auto-generate</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="barcode" class="form-label">Barcode</label>
                            <input type="text" class="form-control bg-dark text-light" id="barcode" name="barcode">
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Product Modal -->
    <div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark-2 text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="viewProductModalLabel">Product Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h5 id="viewProductName"></h5>
                        <p class="text-muted" id="viewProductCategory"></p>
                    </div>
                    <div class="mb-3">
                        <p id="viewProductDescription"></p>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <p><strong>Price:</strong> <span id="viewProductPrice"></span></p>
                        </div>
                        <div class="col-6">
                            <p><strong>Cost:</strong> <span id="viewProductCost"></span></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <p><strong>SKU:</strong> <span id="viewProductSku"></span></p>
                        </div>
                        <div class="col-6">
                            <p><strong>Barcode:</strong> <span id="viewProductBarcode"></span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark-2 text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editProductForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="id" id="editProductId">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editName" class="form-label">Product Name*</label>
                                <input type="text" class="form-control bg-dark text-light" id="editName" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editCategoryId" class="form-label">Category</label>
                                <select class="form-select bg-dark text-light" id="editCategoryId" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control bg-dark text-light" id="editDescription" name="description" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="editPrice" class="form-label">Price*</label>
                                <input type="number" step="0.01" class="form-control bg-dark text-light" id="editPrice" name="price" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="editCost" class="form-label">Cost*</label>
                                <input type="number" step="0.01" class="form-control bg-dark text-light" id="editCost" name="cost" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="editSku" class="form-label">SKU*</label>
                                <input type="text" class="form-control bg-dark text-light" id="editSku" name="sku" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editBarcode" class="form-label">Barcode</label>
                            <input type="text" class="form-control bg-dark text-light" id="editBarcode" name="barcode">
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark-2 text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="deleteProductModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="id" id="deleteProductId">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this product? This will also remove all inventory records.</p>
                        <p class="fw-bold" id="deleteProductName"></p>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        // Auto-generate SKU function
        function generateSKU(name) {
            let prefix = '';
            const words = name.split(' ');
            words.forEach(word => {
                prefix += word.substring(0, 3);
            });
            prefix = prefix.substring(0, 6).toUpperCase();
            const random = Math.floor(1000 + Math.random() * 9000);
            return prefix + '-' + random;
        }

        // Generate SKU when name changes (Add Product)
        document.getElementById('name').addEventListener('blur', function() {
            if (!document.getElementById('sku').value) {
                const name = this.value.trim();
                if (name) {
                    document.getElementById('sku').value = generateSKU(name);
                }
            }
        });

        // Manual SKU generation button (Add Product)
        document.getElementById('generateSkuBtn').addEventListener('click', function() {
            const name = document.getElementById('name').value.trim();
            if (name) {
                document.getElementById('sku').value = generateSKU(name);
            } else {
                alert('Please enter a product name first');
            }
        });

        // View Product
        document.querySelectorAll('.view-product').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                fetch(`api/products.php?action=get_product&id=${productId}`)
                    .then(response => response.json())
                    .then(product => {
                        document.getElementById('viewProductName').textContent = product.name;
                        document.getElementById('viewProductCategory').textContent = product.category_name || 'Uncategorized';
                        document.getElementById('viewProductDescription').textContent = product.description || 'No description';
                        document.getElementById('viewProductPrice').textContent = '$' + parseFloat(product.price).toFixed(2);
                        document.getElementById('viewProductCost').textContent = '$' + parseFloat(product.cost).toFixed(2);
                        document.getElementById('viewProductSku').textContent = product.sku || 'N/A';
                        document.getElementById('viewProductBarcode').textContent = product.barcode || 'N/A';
                        
                        const modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
                        modal.show();
                    });
            });
        });

        // Edit Product
        document.querySelectorAll('.edit-product').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                fetch(`api/products.php?action=get_product&id=${productId}`)
                    .then(response => response.json())
                    .then(product => {
                        document.getElementById('editProductId').value = product.id;
                        document.getElementById('editName').value = product.name;
                        document.getElementById('editDescription').value = product.description || '';
                        document.getElementById('editCategoryId').value = product.category_id || '';
                        document.getElementById('editPrice').value = product.price;
                        document.getElementById('editCost').value = product.cost;
                        document.getElementById('editSku').value = product.sku || '';
                        document.getElementById('editBarcode').value = product.barcode || '';
                        
                        const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
                        modal.show();
                    });
            });
        });

        // Delete Product
        document.querySelectorAll('.delete-product').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const productName = this.closest('tr').querySelector('td:first-child').textContent;
                
                document.getElementById('deleteProductId').value = productId;
                document.getElementById('deleteProductName').textContent = productName;
                
                const modal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
                modal.show();
            });
        });

        // SKU duplicate check before form submission (Add Product)
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            const sku = document.getElementById('sku').value.trim();
            if (!sku) {
                e.preventDefault();
                alert('Please enter or generate a SKU');
                return false;
            }
        });

        // SKU duplicate check before form submission (Edit Product)
        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            const sku = document.getElementById('editSku').value.trim();
            if (!sku) {
                e.preventDefault();
                alert('Please enter a SKU');
                return false;
            }
        });
    </script>
</body>
</html>