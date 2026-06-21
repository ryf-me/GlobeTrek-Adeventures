<?php
// Destination data
$destinations = [
    [
        'name'        => 'Sigiriya Rock Fortress, Matale',
        'description' => 'A dramatic, UNESCO-protected ancient palace complex perched atop a massive 180-meter-high granite rock column. Built by King Kashyapa in the 5th century, it is famous for its colorful frescoes, graffiti-mirror wall, and monumental lion\'s paw gateway.',
        'image'       => 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=600&h=400&fit=crop',
        'slug'        => 'sigiriya'
    ],
    [
        'name'        => 'Galle Fort, Galle',
        'description' => 'A living UNESCO World Heritage monument originally built by the Portuguese in 1588 and heavily fortified by the Dutch. Today, its atmospheric cobblestone streets are lined with beautifully preserved colonial villas, boutique cafes, and old churches, bounded by historic seaside ramparts.',
        'image'       => 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=600&h=400&fit=crop&crop=center', // placeholder, ideally replace
        'slug'        => 'galle'
    ],
    [
        'name'        => 'Nine Arch Bridge (Elta), Badulla',
        'description' => 'An iconic, colonial-era railway bridge built completely out of brick, rock, and cement without using a single piece of steel. It stands hidden amid lush green tea plantations and misty mountains, drawing travelers who come to watch trains slowly pass over its line and admire arches.',
        'image'       => 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=600&h=400&fit=crop&crop=right',
        'slug'        => 'nine-arch'
    ],
    [
        'name'        => 'Ancient City of Polonnaruwa, Polonnaruwa',
        'description' => 'Sri Lanka\'s second ancient royal capital, active from the 10th to the 13th centuries. The vast, park-like archaeological site features marvelous preserved ruins, including the grand Royal Palace, massive stone stupas, and the famous Gal Vihara rock-cut Buddha statues.',
        'image'       => 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=600&h=400&fit=crop&crop=top',
        'slug'        => 'polonnaruwa'
    ],
    [
        'name'        => 'Nuwara Eliya',
        'description' => 'Famous dubbed “Little England,” this high-altitude mountain station was favored by British colonizers for its cool climate. It is the premier destination for exploring manicured green tea estates, sprawling colonial-era bungalows, and dramatic waterfalls.',
        'image'       => 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=600&h=400&fit=crop&crop=bottom',
        'slug'        => 'nuwara-eliya'
    ],
    [
        'name'        => 'Mirissa, Matara',
        'description' => 'A laid-back coastal paradise renowned as one of the best locations in the world for blue whale watching safaris. It is also widely visited for its crescent-shaped sandy beaches, vibrant beachside restaurants, and the iconic Coconut Turtle Hill viewpoint.',
        'image'       => 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=600&h=400&fit=crop&crop=center',
        'slug'        => 'mirissa'
    ]
];

// Trending data
$trending = [
    'Yala National Park, Yala National Park',
    'Dambulla Cave Temple, Matale'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlobeTrek – Discover Your Next Adventure</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/destinations.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="destinations-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <!-- Hero -->
    <section class="hero container">
        <h2>Discover Your Next Adventure</h2>
        <p>Explore our curated list of destinations across the globe. From pristine beaches to historic cityscapes, find the perfect backdrop for your journey.</p>
    </section>

    <!-- All Destinations -->
    <div class="container">
        <h2 class="section-title">All Destinations</h2>

        <div class="destinations-grid">
            <?php foreach ($destinations as $dest): ?>
                <div class="dest-card">
                    <img src="<?= htmlspecialchars($dest['image']) ?>" alt="<?= htmlspecialchars($dest['name']) ?>">
                    <div class="card-body">
                        <h3><?= htmlspecialchars($dest['name']) ?></h3>
                        <p><?= htmlspecialchars($dest['description']) ?></p>
                        <a href="#" class="explore-link">Explore →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Trending Now -->
        <div class="trending-section">
            <div class="trending-header">
                <h3>Trending Now</h3>
                <a href="#" class="view-all">View All</a>
            </div>
            <ul class="trending-list">
                <?php foreach ($trending as $item): ?>
                    <li><?= htmlspecialchars($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

<script src="../js/script.js"></script>
</body>
</html>
