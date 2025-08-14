<?php
require_once 'config.php';
requireLogin();

// Get counts for dashboard
$productsCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$categoriesCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity < 10")->fetchColumn();
$outOfStockCount = $pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity = 0")->fetchColumn();

// Get recent activities
$activities = $pdo->query("
    SELECT il.*, p.name as product_name, u.name as user_name 
    FROM inventory_log il
    JOIN products p ON il.product_id = p.id
    JOIN users u ON il.user_id = u.id
    ORDER BY il.created_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body class="bg-dark text-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom border-secondary">
                    <h1 class="h2"><i class="bi bi-speedometer2"></i> Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-calendar"></i> <?php echo date('F j, Y'); ?>
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-dark-2 border-primary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Products</h6>
                                        <h3 class="mb-0 text-primary"><?php echo $productsCount; ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-box-seam fs-4 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-dark-2 border-success h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Categories</h6>
                                        <h3 class="mb-0 text-success"><?php echo $categoriesCount; ?></h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-tags fs-4 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-dark-2 border-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Low Stock</h6>
                                        <h3 class="mb-0 text-warning"><?php echo $lowStockCount; ?></h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-exclamation-triangle fs-4 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-dark-2 border-danger h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Out of Stock</h6>
                                        <h3 class="mb-0 text-danger"><?php echo $outOfStockCount; ?></h3>
                                    </div>
                                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-x-circle fs-4 text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="card bg-dark-2 border-secondary mb-4">
                    <div class="card-header border-secondary">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i> Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Action</th>
                                        <th>User</th>
                                        <th>Quantity</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($activity['product_name']); ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = '';
                                                $actionText = '';
                                                switch ($activity['action']) {
                                                    case 'add':
                                                        $badgeClass = 'success';
                                                        $actionText = 'Added';
                                                        break;
                                                    case 'remove':
                                                        $badgeClass = 'danger';
                                                        $actionText = 'Removed';
                                                        break;
                                                    case 'adjust':
                                                        $badgeClass = 'warning';
                                                        $actionText = 'Adjusted';
                                                        break;
                                                    case 'set':
                                                        $badgeClass = 'info';
                                                        $actionText = 'Set';
                                                        break;
                                                }
                                                ?>
                                                <span
                                                    class="badge bg-<?php echo $badgeClass; ?>"><?php echo $actionText; ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['user_name']); ?></td>
                                            <td>
                                                <?php if ($activity['action'] === 'set'): ?>
                                                    Set to <?php echo $activity['new_quantity']; ?>
                                                <?php else: ?>
                                                    <?php echo abs($activity['new_quantity'] - $activity['old_quantity']); ?>
                                                    (<?php echo $activity['old_quantity']; ?> →
                                                    <?php echo $activity['new_quantity']; ?>)
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, H:i', strtotime($activity['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>

</html>