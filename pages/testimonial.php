<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$allTestimonials = $db->query("SELECT * FROM testimonials WHERE status = 'approved' ORDER BY is_featured DESC, id ASC")->fetchAll();

$trustedCompanies = ['Aitken Spence', 'Ceylon Roots', 'Jetwing', 'Resplendent Ceylon', 'Walkers Tours', 'Blue Lanka Tours', 'TourRadar', 'Ceylon Expeditions'];
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials - GlobeTrek Adventures</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/testimonial.css">
    <link rel="stylesheet" href="../css/inquiries.css">
    <link rel="stylesheet" href="../css/review-modal.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="testimonial-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <section class="testimonial-hero" aria-labelledby="testimonial-title">
            <img class="testimonial-hero-bg" src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?q=80&w=1173&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="">
            <div class="testimonial-hero-content">
                <h1 id="testimonial-title">What Our Travelers Say</h1>
                <p class="testimonial-hero-subtitle">
                    Don't just take our word for it. Hear from adventurers who have explored the beauty of Sri Lanka with GlobeTrek Adventures.
                </p>
            </div>
        </section>

        <section class="testimonials-section" aria-labelledby="testimonials-section-title">
            <div class="testimonials-inner">
                <div class="testimonials-left">
                    <div class="testimonials-badge">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span>Trusted by travelers</span>
                    </div>
                    <h2 id="testimonials-section-title">Loved by the Community</h2>
                    <p class="testimonials-subtitle">See what travelers from around the world have to say about their Sri Lanka experience with GlobeTrek Adventures.</p>
                    <div class="testimonials-dots">
                        <?php foreach ($allTestimonials as $i => $testimonial): ?>
                            <button class="testimonials-dot <?= $i === 0 ? 'active' : '' ?>" aria-label="View testimonial <?= $i + 1 ?>" data-index="<?= $i ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="testimonials-right">
                    <?php foreach ($allTestimonials as $i => $testimonial): ?>
                        <div class="testimonial-card <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
                            <div class="testimonial-stars">
                                <?php for ($s = 0; $s < $testimonial['rating']; $s++): ?>
                                    <span class="star">&#9733;</span>
                                <?php endfor; ?>
                                <?php for ($s = $testimonial['rating']; $s < 5; $s++): ?>
                                    <span class="star star-empty">&#9733;</span>
                                <?php endfor; ?>
                            </div>
                            <div class="testimonial-quote">
                                <svg class="testimonial-quote-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>
                                <p class="testimonial-content">"<?= htmlspecialchars($testimonial['content']) ?>"</p>
                            </div>
                            <div class="testimonial-author">
                                <img src="<?= htmlspecialchars($testimonial['reviewer_avatar']) ?>" alt="<?= htmlspecialchars($testimonial['reviewer_name']) ?>" class="testimonial-avatar" />
                                <div class="testimonial-info">
                                    <span class="testimonial-name"><?= htmlspecialchars($testimonial['reviewer_name']) ?></span>
                                    <span class="testimonial-country"><?= htmlspecialchars($testimonial['reviewer_country']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="testimonial-decor-bottom"></div>
                    <div class="testimonial-decor-top"></div>
                </div>
            </div>
        </section>

        <section class="trusted-partners" aria-label="Trusted Partners">
            <h2>Trusted Partners</h2>
            <div class="marquee-container">
                <div class="marquee-track">
                    <?php foreach (array_merge($trustedCompanies, $trustedCompanies) as $company): ?>
                        <div class="partner-text"><?= htmlspecialchars($company) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="testimonial-cta">
            <div class="testimonial-cta-content">
                <h2>Ready to Start Your Adventure?</h2>
                <p>Join thousands of travelers who have explored the beauty of Sri Lanka with GlobeTrek Adventures.</p>
                <a href="packages.php" class="testimonial-cta-btn">View Our Packages</a>
            </div>
        </section>

        <?php if (isset($_SESSION['user_id'])): ?>
        <section style="max-width:none;background:#f5f7fa;text-align:center;padding:2rem;">
            <button onclick="openReviewModal(0)" class="testimonial-cta-btn" style="display:inline-flex;align-items:center;gap:0.4rem;">
                <span class="material-symbols-outlined" style="font-size:1.2rem;">rate_review</span>
                Write a Review
            </button>
        </section>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/review-modal.php'; ?>
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    <script src="../js/review-modal.js"></script>
    <script>
    (function() {
        var cards = document.querySelectorAll('.testimonial-card');
        var dots = document.querySelectorAll('.testimonials-dot');
        var currentIndex = 0;
        var interval = null;

        function showTestimonial(index) {
            cards.forEach(function(card) { card.classList.remove('active'); });
            dots.forEach(function(dot) { dot.classList.remove('active'); });

            cards[index].classList.add('active');
            dots[index].classList.add('active');
            currentIndex = index;
        }

        function nextTestimonial() {
            var next = (currentIndex + 1) % cards.length;
            showTestimonial(next);
        }

        function startAutoRotate() {
            if (interval) clearInterval(interval);
            interval = setInterval(nextTestimonial, 6000);
        }

        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                var index = parseInt(this.getAttribute('data-index'));
                showTestimonial(index);
                startAutoRotate();
            });
        });

        startAutoRotate();
    })();
    </script>
</body>
</html>
