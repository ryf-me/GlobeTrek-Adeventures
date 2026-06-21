<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About GlobeTrek Adventures</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/about.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="about-page">
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <section class="about-hero" aria-labelledby="about-title">
            <div class="about-hero-copy">
                <p class="about-eyebrow">Born on island roads</p>
                <h1 id="about-title">We design journeys that leave Sri Lanka better than we found it.</h1>
                <p>
                    GlobeTrek began with a small circle of guides who believed travel should feel personal,
                    grounded, and generous to the communities that host it.
                </p>
                <div class="about-hero-actions">
                    <a href="packages.php">Explore Trips</a>
                    <a href="guides.php">Meet Our Guides</a>
                </div>
            </div>

            <div class="about-hero-media" aria-label="Lush green travel landscape">
                <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=1400&h=1100&auto=format&fit=crop" alt="Lush green landscape on a guided adventure route">
                <div class="hero-stat">
                    <span>14+</span>
                    <p>years shaping thoughtful island adventures</p>
                </div>
            </div>
        </section>

        <section class="about-intro" aria-labelledby="story-title">
            <div>
                <p class="about-eyebrow">Our story</p>
                <h2 id="story-title">From weekend escapes to carefully hosted expeditions.</h2>
            </div>
            <p>
                We started by helping friends find the quieter side of Sri Lanka: the family-run guesthouse
                above a tea valley, the dawn wildlife drive led by a patient tracker, the coastal village meal
                that turned into a lasting friendship. Today, GlobeTrek brings that same care to every route,
                guide partnership, and guest detail.
            </p>
        </section>

        <section class="values-section" aria-labelledby="values-title">
            <div class="section-heading">
                <p class="about-eyebrow">What guides us</p>
                <h2 id="values-title">Our Core Values</h2>
            </div>

            <div class="values-grid">
                <article class="value-card">
                    <div class="value-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48">
                            <path d="M39 9c-13.7.6-23.5 6.7-26.1 15.6-1.7 5.7.8 10.8 5.4 13.1 5.4 2.7 12.3.4 15.9-5.4C37.6 26.8 38.9 18.7 39 9z"></path>
                            <path d="M12 39c4.4-8.5 11.5-14.9 21.4-19.2"></path>
                        </svg>
                    </div>
                    <h3>Sustainability</h3>
                    <p>We choose local partners, lower-impact routes, and conservation-minded experiences that protect the places travelers come to admire.</p>
                </article>

                <article class="value-card">
                    <div class="value-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48">
                            <path d="M24 5l14 5v11c0 9.2-5.7 17.5-14 21-8.3-3.5-14-11.8-14-21V10l14-5z"></path>
                            <path d="M16.5 24l5 5 10.5-12"></path>
                        </svg>
                    </div>
                    <h3>Authenticity</h3>
                    <p>Our trips are built with people who know the land, language, food, rituals, and rhythms of each destination.</p>
                </article>

                <article class="value-card">
                    <div class="value-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48">
                            <circle cx="24" cy="24" r="18"></circle>
                            <path d="M30.5 17.5l-4.2 10.8-10.8 4.2 4.2-10.8 10.8-4.2z"></path>
                            <circle cx="24" cy="24" r="2"></circle>
                        </svg>
                    </div>
                    <h3>Adventure</h3>
                    <p>We make room for wonder, from misty mountain climbs to market walks, while keeping every journey carefully supported.</p>
                </article>
            </div>
        </section>

        <section class="journey-section" aria-labelledby="journey-title">
            <div class="section-heading">
                <p class="about-eyebrow">The journey</p>
                <h2 id="journey-title">How GlobeTrek grew</h2>
            </div>

            <div class="timeline">
                <article class="timeline-item">
                    <span>2010</span>
                    <h3>The first route notebook</h3>
                    <p>Our founders mapped small-group escapes through Sigiriya, Ella, Galle, and the southern coast with a guide-first philosophy.</p>
                </article>

                <article class="timeline-item">
                    <span>2015</span>
                    <h3>A wider field team</h3>
                    <p>We built a trusted network of naturalists, cultural hosts, drivers, photographers, and mountain guides across the island.</p>
                </article>

                <article class="timeline-item">
                    <span>2020</span>
                    <h3>Community commitments</h3>
                    <p>GlobeTrek formalized local sourcing, plastic-light operations, and direct community benefit standards for every itinerary.</p>
                </article>

                <article class="timeline-item">
                    <span>Today</span>
                    <h3>Thoughtful travel at scale</h3>
                    <p>We continue crafting Sri Lanka journeys that balance comfort, discovery, and respect for the landscapes that make them possible.</p>
                </article>
            </div>
        </section>

        <section class="team-section" aria-labelledby="team-title">
            <div class="section-heading">
                <p class="about-eyebrow">Field leadership</p>
                <h2 id="team-title">Meet the people behind the routes</h2>
            </div>

            <div class="team-grid">
                <article class="team-member">
                    <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=500&h=600&fit=crop&crop=face" alt="Sarah Jenkins smiling at the camera">
                    <h3>Sarah Jenkins</h3>
                    <p>Founder and Experience Director</p>
                </article>

                <article class="team-member">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&h=600&fit=crop&crop=face" alt="Alex Rivera outdoor expedition guide portrait">
                    <h3>Alex Rivera</h3>
                    <p>Mountain Expedition Lead</p>
                </article>

                <article class="team-member">
                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=500&h=600&fit=crop&crop=face" alt="Mei Lin cultural travel specialist portrait">
                    <h3>Mei Lin</h3>
                    <p>Cultural Journey Curator</p>
                </article>

                <article class="team-member">
                    <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=500&h=600&fit=crop&crop=face" alt="David Chen destination guide portrait">
                    <h3>David Chen</h3>
                    <p>Lead Destination Guide</p>
                </article>
            </div>
        </section>

        <section class="about-cta" aria-labelledby="cta-title">
            <p class="about-eyebrow">Work with us</p>
            <h2 id="cta-title">Bring your local knowledge to the next GlobeTrek route.</h2>
            <p>
                We collaborate with guides, hosts, makers, conservationists, and travel specialists who care
                deeply about Sri Lanka and the people moving through it.
            </p>
            <a href="guides.php">Join Our Guide Network</a>
        </section>
    </main>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
</body>
</html>
