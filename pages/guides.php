<?php
/**
 * File: pages/guides.php
 * Purpose: Displays a filterable list of all active tour guides with specialty/region filters, search, and a "Guide of the Month" featured section.
 * Dependencies: config/database.php, includes/navbar.php, includes/footer.php, css/guides.css, js/script.js
 * Used By: index.php, guide-details.php (linked from navigation)
 * Parent Files: index.php, navbar.php
 * Child Files: guide-details.php (linked from guide cards)
 * @package GlobeTrek\Pages
 */
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// === FILTER OPTIONS ===
// Hardcoded specialty and region lists for the filter dropdowns.
// These should match the values stored in the guides table.
$specialties = [
    'All Specialties',
    'Hill Country & Tea Plantations',
    'Cultural Heritage & Temples',
    'Wildlife & Safari',
    'Culinary Tours',
    'Urban Exploration',
    'Marine & Diving'
];

$regions = [
    'All Regions',
    'Central Highlands',
    'Southern Coast',
    'Cultural Triangle',
    'Eastern Province',
    'Northern Province',
    'Western Province'
];

// === RETRIEVE FILTER PARAMETERS ===
$searchQuery = trim($_GET['q'] ?? '');
$selectedSpecialty = $_GET['specialty'] ?? 'All Specialties';
$selectedRegion = $_GET['region'] ?? 'All Regions';

// === WHITELIST VALIDATION ===
// Ensure filter values are from the allowed list to prevent injection of unexpected values.
if (!in_array($selectedSpecialty, $specialties, true)) {
    $selectedSpecialty = 'All Specialties';
}
if (!in_array($selectedRegion, $regions, true)) {
    $selectedRegion = 'All Regions';
}

// === BUILD DYNAMIC QUERY ===
// Start with base WHERE clause and append optional search/filter conditions.
$sql = "SELECT * FROM guides WHERE is_active = 1";
$params = [];

if ($searchQuery !== '') {
    $sql .= " AND (name LIKE :search OR specialty LIKE :search OR description LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}
if ($selectedSpecialty !== 'All Specialties') {
    $sql .= " AND specialty = :specialty";
    $params[':specialty'] = $selectedSpecialty;
}
if ($selectedRegion !== 'All Regions') {
    $sql .= " AND region = :region";
    $params[':region'] = $selectedRegion;
}

// Featured guides first, then alphabetical
$sql .= " ORDER BY is_featured DESC, id ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$filteredGuides = $stmt->fetchAll();

// === GUIDE OF THE MONTH ===
// Fetch one featured guide for the spotlight section.
$guideOfMonth = null;
$stmtGom = $db->query("SELECT * FROM guides WHERE is_featured = 1 LIMIT 1");
$guideOfMonth = $stmtGom->fetch();

// === HELPER: HTML ESCAPE ===
// Convenience wrapper for htmlspecialchars with common flags.
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Expert Guides - GlobeTrek</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/guides.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="guides-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main class="guides-shell">
        <!-- === HERO SECTION === -->
        <section class="guides-hero" aria-labelledby="guides-title">
            <p class="eyebrow">Local knowledge, island-wide reach</p>
            <h1 id="guides-title">Our Expert Guides</h1>
            <p>Discover the passionate Sri Lankan individuals who make our adventures unforgettable. Search by specialty or region to find your perfect local expert.</p>
        </section>

        <!-- === SEARCH & FILTER PANEL === -->
        <section class="guide-search-panel" aria-label="Find a guide">
            <form class="guide-filter-form" method="get" action="guides.php">
                <label class="search-field" for="guide-search">
                    <span>Search</span>
                    <input id="guide-search" name="q" type="search" value="<?= e($searchQuery) ?>" placeholder="Search guides by name or keyword...">
                </label>

                <label for="guide-specialty">
                    <span>Specialty</span>
                    <select id="guide-specialty" name="specialty">
                        <?php foreach ($specialties as $specialty): ?>
                            <option value="<?= e($specialty) ?>" <?= $selectedSpecialty === $specialty ? 'selected' : '' ?>>
                                <?= e($specialty) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label for="guide-region">
                    <span>Region</span>
                    <select id="guide-region" name="region">
                        <?php foreach ($regions as $region): ?>
                            <option value="<?= e($region) ?>" <?= $selectedRegion === $region ? 'selected' : '' ?>>
                                <?= e($region) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="filter-actions">
                    <button type="submit">Find Guides</button>
                    <a href="guides.php">Reset</a>
                </div>
            </form>
        </section>

        <!-- === GUIDE CARDS GRID === -->
        <section class="guide-grid-section" aria-labelledby="guide-list-title">
            <div class="section-kicker">
                <h2 id="guide-list-title">Meet the field team</h2>
                <p><?= count($filteredGuides) ?> guide<?= count($filteredGuides) === 1 ? '' : 's' ?> found</p>
            </div>

            <?php if (count($filteredGuides) > 0): ?>
                <div class="guides-grid">
                    <?php foreach ($filteredGuides as $guide): ?>
                        <article class="guide-card">
                            <img src="<?= e($basePath . $guide['image']) ?>" alt="<?= e($guide['name']) ?>">
                            <div class="guide-card-body">
                                <p class="guide-region"><?= e($guide['region']) ?></p>
                                <h3><?= e($guide['name']) ?></h3>
                                <p class="specialty"><?= e($guide['specialty']) ?></p>
                                <p class="guide-description"><?= e($guide['description']) ?></p>
                                <!-- Cast id to int for URL safety -->
                                <a href="guide-details.php?id=<?= (int)$guide['id'] ?>" class="profile-btn">View Profile</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty state when no guides match filters -->
                <div class="empty-state">
                    <h3>No guides matched your search</h3>
                    <p>Try a broader keyword, choose all specialties, or reset the region filter.</p>
                    <a href="guides.php">Show all guides</a>
                </div>
            <?php endif; ?>
        </section>

        <!-- === GUIDE OF THE MONTH (FEATURED) === -->
        <?php if ($guideOfMonth): ?>
        <section class="featured-guide" aria-labelledby="featured-guide-title">
            <img src="<?= e($basePath . $guideOfMonth['image']) ?>" alt="<?= e($guideOfMonth['name']) ?>">
            <div class="featured-content">
                <p class="badge">Guide of the Month</p>
                <h2 id="featured-guide-title">Meet <?= e($guideOfMonth['name']) ?></h2>
                <p class="featured-specialty"><?= e($guideOfMonth['specialty']) ?></p>
                <p><?= e($guideOfMonth['description']) ?></p>
                <a href="#">Read Full Interview</a>
            </div>
        </section>
        <?php endif; ?>

        <!-- === JOIN CTA === -->
        <section class="join-guides" aria-labelledby="join-title">
            <p class="eyebrow">Join the network</p>
            <h2 id="join-title">Are you an expert explorer?</h2>
            <p>We are always looking for passionate, knowledgeable, and safety-conscious Sri Lankan guides to join our team. Share your love for this island with the world.</p>
            <a href="#">Join Our Team</a>
        </section>
    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
</body>
</html>
