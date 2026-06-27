<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/currency.php';
$db = getDB();

$userId = $_SESSION['user_id'] ?? null;

$destinationsList = [
    'Southern & Western Beaches',
    'Cultural Triangle & Temples',
    'Hill Country & Tea Country',
    'National Parks & Wildlife',
    'Colombo & City Experiences'
];

$priceRanges = [
    CURRENCY_SYMBOL . '0 - ' . CURRENCY_SYMBOL . '9,999',
    CURRENCY_SYMBOL . '10,000 - ' . CURRENCY_SYMBOL . '29,999',
    CURRENCY_SYMBOL . '30,000 - ' . CURRENCY_SYMBOL . '49,999',
    CURRENCY_SYMBOL . '50,000+'
];

$tripDurations = [
    '1-3 Days',
    '4-7 Days',
    '8-14 Days',
    '15+ Days'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Packages</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/packages.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        .dataTables_wrapper .dataTables_filter input {
            padding: 8px 12px;
            border: 1px solid var(--login-line, #d1d5db);
            border-radius: 8px;
            font-size: 0.9rem;
            width: 250px;
        }
        .dataTables_wrapper .dataTables_length select {
            padding: 6px 8px;
            border: 1px solid var(--login-line, #d1d5db);
            border-radius: 6px;
        }
        table.dataTable thead th {
            background: #f8fafc;
            font-weight: 700;
            color: #264653;
            border-bottom: 2px solid #e2e8f0;
        }
        table.dataTable tbody tr:hover {
            background: #f8fafc;
        }
        .pkg-table-img {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
        }
        .pkg-table-title {
            font-weight: 600;
            color: #264653;
        }
        .pkg-table-price {
            font-weight: 700;
            color: #e76f51;
        }
        .pkg-table-link {
            color: #264653;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .pkg-table-link:hover {
            color: #e76f51;
            text-decoration: underline;
        }
        .wishlist-active { color: #e76f51; }
        .wishlist-btn-table {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        .wishlist-btn-table .material-symbols-outlined {
            font-size: 22px;
            color: #94a3b8;
            transition: color 0.2s;
        }
        .wishlist-btn-table:hover .material-symbols-outlined {
            color: #ef4444;
        }
        .wishlist-btn-table.active .material-symbols-outlined {
            color: #ef4444;
            font-variation-settings: 'FILL' 1;
        }
        .packages-layout { display: flex; gap: 2rem; }
        .filters-sidebar { flex: 0 0 260px; }
        .packages-main { flex: 1; min-width: 0; }
        .filter-group { margin-bottom: 1.2rem; }
        .filter-group h3 { font-size: 0.95rem; margin-bottom: 0.5rem; color: #264653; }
        .filter-group label { display: block; padding: 3px 0; font-size: 0.88rem; color: #444; cursor: pointer; }
        .filter-group input[type="checkbox"] { margin-right: 6px; }
        .filter-btn, .reset-btn {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        .filter-btn { background: #e76f51; color: #fff; }
        .filter-btn:hover { background: #d4603f; }
        .reset-btn { background: #f1f5f9; color: #475569; margin-top: 8px; }
        .reset-btn:hover { background: #e2e8f0; }
        @media (max-width: 768px) {
            .packages-layout { flex-direction: column; }
            .filters-sidebar { flex: none; }
        }
    </style>
</head>
<body class="packages-page">

    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <div class="page-container">
        <h1>Tour Packages</h1>

        <a href="custom-trips.php" class="customize-trip-banner">
            <div class="customize-trip-text">
                <h2>Can't find the perfect trip?</h2>
                <p>Let our travel experts design a bespoke itinerary tailored to your preferences.</p>
            </div>
            <span class="customize-trip-btn">Customize Trip</span>
        </a>

        <div class="packages-layout">
            <!-- Sidebar Filters -->
            <aside class="filters-sidebar">
                <h2 style="margin-bottom:1rem;">Filters</h2>

                <div class="filter-group">
                    <h3>Destination</h3>
                    <?php foreach ($destinationsList as $dest): ?>
                        <label>
                            <input type="checkbox" class="pkg-filter" name="destination[]" value="<?= htmlspecialchars($dest) ?>">
                            <?= htmlspecialchars($dest) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h3>Price Range</h3>
                    <?php foreach ($priceRanges as $range): ?>
                        <label>
                            <input type="checkbox" class="pkg-filter" name="price[]" value="<?= htmlspecialchars($range) ?>">
                            <?= htmlspecialchars($range) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h3>Trip Duration</h3>
                    <?php foreach ($tripDurations as $dur): ?>
                        <label>
                            <input type="checkbox" class="pkg-filter" name="duration[]" value="<?= htmlspecialchars($dur) ?>">
                            <?= htmlspecialchars($dur) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="filter-btn" id="applyFilters">Apply Filters</button>
                <button type="button" class="reset-btn" id="resetFilters">Reset</button>
            </aside>

            <!-- Package DataTable -->
            <main class="packages-main">
                <table id="packagesTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Package</th>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Destination</th>
                            <th>Wishlist</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </main>
        </div>
    </div>
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="../js/script.js"></script>
<script>

// Initialize DataTable
var table = $('#packagesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: 'ajax-packages.php',
        data: function(d) {
            // Append filter values to the request
            d.destination = [];
            d.price = [];
            d.duration = [];
            $('input[name="destination[]"]:checked').each(function() { d.destination.push(this.value); });
            $('input[name="price[]"]:checked').each(function() { d.price.push(this.value); });
            $('input[name="duration[]"]:checked').each(function() { d.duration.push(this.value); });
        }
    },
    columns: [
        {
            data: 'title',
            render: function(data, type, row) {
                if (type === 'display') {
                    return '<div style="display:flex;align-items:center;gap:10px;">' +
                        '<img src="' + row.image + '" alt="" class="pkg-table-img">' +
                        '<div><div class="pkg-table-title">' + data + '</div>' +
                        '<small style="color:#888;">' + row.destination_category + '</small></div></div>';
                }
                return data;
            }
        },
        { data: 'duration' },
        {
            data: 'price',
            render: function(data, type, row) {
                if (type === 'display') return '<span class="pkg-table-price">' + data + '</span>';
                return row.price_raw;
            }
        },
        { data: 'destination_category' },
        {
            data: 'wishlist',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                var activeClass = data ? ' active' : '';
                return '<button class="wishlist-btn-table' + activeClass + '" data-id="' + row.id + '" title="Add to Wishlist" onclick="event.preventDefault();event.stopPropagation();toggleWishlist(this);">' +
                    '<span class="material-symbols-outlined">favorite</span></button>';
            }
        },
        {
            data: 'detail_url',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                return '<a href="' + data + '" class="pkg-table-link">View Details</a>';
            }
        }
    ],
    order: [[2, 'asc']],
    pageLength: 10,
    lengthMenu: [10, 25, 50],
    language: {
        emptyTable: 'No packages match your filters',
        search: '',
        searchPlaceholder: 'Search packages...'
    }
});

// Apply filters button
$('#applyFilters').on('click', function() {
    table.ajax.reload();
});

// Reset filters button
$('#resetFilters').on('click', function() {
    $('.pkg-filter').prop('checked', false);
    table.ajax.reload();
});

// Wishlist toggle
function toggleWishlist(btn) {
    var pkgId = btn.getAttribute('data-id');
    var formData = new FormData();
    formData.append('package_id', pkgId);
    formData.append('csrf_token', csrfToken);

    fetch('wishlist-toggle.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status === 'added') {
            btn.classList.add('active');
        } else if (data.status === 'removed') {
            btn.classList.remove('active');
        } else if (data.status === 'error') {
            alert(data.message || 'Please log in to use the wishlist.');
        }
    })
    .catch(function() {
        alert('An error occurred. Please try again.');
    });
}
</script>
</body>
</html>
