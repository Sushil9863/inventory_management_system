<?php
require_once 'config.php';
requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        die("CSRF token validation failed");
    }

    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name'] ?? '');
        $description = $_POST['description'] ?? '';

        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);

            $_SESSION['message'] = "Category added successfully!";
            header("Location: categories.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error adding category: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_category'])) {
        $id = $_POST['id'] ?? 0;
        $name = trim($_POST['name'] ?? '');
        $description = $_POST['description'] ?? '';

        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);

            $_SESSION['message'] = "Category updated successfully!";
            header("Location: categories.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error updating category: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = $_POST['id'] ?? 0;

        try {
            // First set products in this category to NULL
            $stmt = $pdo->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?");
            $stmt->execute([$id]);

            // Then delete the category
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['message'] = "Category deleted successfully!";
            header("Location: categories.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error deleting category: " . $e->getMessage();
        }
    }
}

// Get all categories
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
    <title>Inventory System - Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
    <?php include 'sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <div
            class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom border-secondary">
            <h1 class="h2"><i class="bi bi-tags"></i> Categories</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus-circle"></i> Add Category
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
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['description']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning edit-category"
                                            data-id="<?php echo $category['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                            data-description="<?php echo htmlspecialchars($category['description']); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-category"
                                            data-id="<?php echo $category['id']; ?>">
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

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark-2 text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control bg-dark text-light" id="name" name="name" required>
                            <div class="invalid-feedback">
                                Name must be at least 3 characters and contain only letters and numbers
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control bg-dark text-light" id="description" name="description"
                                rows="3" required></textarea>
                            <div class="invalid-feedback">
                                Please provide a meaningful description (at least 3 characters)
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark-2 text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="id" id="editCategoryId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control bg-dark text-light" id="name" name="name" required
                                pattern="[a-zA-Z0-9\s]{3,}">
                            <div class="invalid-feedback">
                                Name must be at least 3 characters and contain only letters and numbers
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control bg-dark text-light" id="description" name="description"
                                rows="3" required></textarea>
                            <div class="invalid-feedback">
                                Please provide a meaningful description (at least 3 characters)
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark-2 text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="id" id="deleteCategoryId">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this category? Products in this category will become
                            uncategorized.</p>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_category" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>   
    <script>
        // Edit Category
        document.querySelectorAll('.edit-category').forEach(button => {
            button.addEventListener('click', function () {
                document.getElementById('editCategoryId').value = this.getAttribute('data-id');
                document.getElementById('editName').value = this.getAttribute('data-name');
                document.getElementById('editDescription').value = this.getAttribute('data-description');

                const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
                modal.show();
            });
        });

        // Delete Category
        document.querySelectorAll('.delete-category').forEach(button => {
            button.addEventListener('click', function () {
                document.getElementById('deleteCategoryId').value = this.getAttribute('data-id');
                const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
                modal.show();
            });
        });
    </script>
</body>

</html>