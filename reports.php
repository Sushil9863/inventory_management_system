<?php
require_once 'config.php';
requireLogin();

// Get report data
$lowStock = $pdo->query("
    SELECT p.name, p.sku, i.quantity, p.price, p.cost 
    FROM products p
    JOIN inventory i ON p.id = i.product_id
    WHERE i.quantity < 10
    ORDER BY i.quantity
")->fetchAll();

$outOfStock = $pdo->query("
    SELECT p.name, p.sku, p.price, p.cost
    FROM products p
    JOIN inventory i ON p.id = i.product_id
    WHERE i.quantity = 0
    ORDER BY p.name
")->fetchAll();

// Get financial data
$financialData = $pdo->query("
    SELECT 
        SUM(p.price * i.quantity) as total_value,
        SUM(p.cost * i.quantity) as total_cost,
        SUM((p.price - p.cost) * i.quantity) as total_profit
    FROM products p
    JOIN inventory i ON p.id = i.product_id
")->fetch(PDO::FETCH_ASSOC);

// Calculate percentages
$profitPercentage = $financialData['total_value'] > 0 
    ? ($financialData['total_profit'] / $financialData['total_value']) * 100 
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .financial-card {
            transition: transform 0.3s ease;
        }
        .financial-card:hover {
            transform: translateY(-5px);
        }
        .profit-badge {
            font-size: 1rem;
            padding: 0.5rem;
        }
    </style>
</head>
<body class="bg-dark text-light">
    <?php include 'sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom border-secondary">
            <h1 class="h2"><i class="bi bi-graph-up"></i> Financial Reports</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button class="btn btn-sm btn-outline-secondary" id="printBtn">
                    <i class="bi bi-printer"></i> Print
                </button>
                <button class="btn btn-sm btn-outline-secondary ms-2" id="exportBtn">
                    <i class="bi bi-download"></i> Export
                </button>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-dark-2 border-primary financial-card h-100">
                    <div class="card-header border-primary d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Total Value</h5>
                        <i class="bi bi-currency-dollar fs-4 text-primary"></i>
                    </div>
                    <div class="card-body">
                        <h2 class="text-center text-primary">$<?php echo number_format($financialData['total_value'], 2); ?></h2>
                        <p class="text-center text-muted mb-0">Inventory Value</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-dark-2 border-info financial-card h-100">
                    <div class="card-header border-info d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Total Cost</h5>
                        <i class="bi bi-currency-dollar fs-4 text-info"></i>
                    </div>
                    <div class="card-body">
                        <h2 class="text-center text-info">$<?php echo number_format($financialData['total_cost'], 2); ?></h2>
                        <p class="text-center text-muted mb-0">Inventory Cost</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-dark-2 border-success financial-card h-100">
                    <div class="card-header border-success d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Total Profit</h5>
                        <i class="bi bi-currency-dollar fs-4 text-success"></i>
                    </div>
                    <div class="card-body">
                        <h2 class="text-center text-success">$<?php echo number_format($financialData['total_profit'], 2); ?></h2>
                        <div class="text-center">
                            <span class="badge bg-success profit-badge">
                                <?php echo number_format($profitPercentage, 2); ?>% Margin
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-dark-2 border-warning financial-card h-100">
                    <div class="card-header border-warning d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-boxes"></i> Stock Status</h5>
                        <i class="bi bi-clipboard-data fs-4 text-warning"></i>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Low Stock:</span>
                            <span class="text-warning"><?php echo count($lowStock); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Out of Stock:</span>
                            <span class="text-danger"><?php echo count($outOfStock); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Reports -->
        <div class="row">
            <div class="col-md-6">
                <div class="card bg-dark-2 border-warning mb-4">
                    <div class="card-header border-warning d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Low Stock (Below 10)</h5>
                        <span class="badge bg-warning"><?php echo count($lowStock); ?> items</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Qty</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStock as $item): 
                                        $itemValue = $item['price'] * $item['quantity'];
                                        $itemCost = $item['cost'] * $item['quantity'];
                                        $itemProfit = $itemValue - $itemCost;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                            <td class="text-warning"><?php echo $item['quantity']; ?></td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <small>Value: $<?php echo number_format($itemValue, 2); ?></small>
                                                    <small>Profit: $<?php echo number_format($itemProfit, 2); ?></small>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card bg-dark-2 border-danger mb-4">
                    <div class="card-header border-danger d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-x-circle"></i> Out of Stock</h5>
                        <span class="badge bg-danger"><?php echo count($outOfStock); ?> items</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Price</th>
                                        <th>Profit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($outOfStock as $item): 
                                        $itemProfit = $item['price'] - $item['cost'];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            <td class="<?php echo $itemProfit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                $<?php echo number_format($itemProfit, 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>