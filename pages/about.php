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
            <img class="about-hero-bg" src="https://images.unsplash.com/photo-1654561773591-57b9413c45c0?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="">
            <div class="about-hero-content">
                <h1 id="about-title">Our Story</h1>
                <p class="about-hero-subtitle">
                    Born on the island of Sri Lanka, GlobeTrek Adventures was founded with a deep love for our homeland's
                    breathtaking landscapes, rich heritage, and warm hospitality. We believe travel is more than just visiting places
                    — it's about connecting with the world and discovering yourself along the way.
                </p>
            </div>
        </section>

        <section class="values-section" aria-labelledby="values-title">
            <div class="values-inner">
                <h2 id="values-title">Our Core Values</h2>
                <div class="values-grid">
                    <article class="value-card">
                        <div class="value-icon" aria-hidden="true">
                            <svg viewBox="0 0 48 48">
                                <path d="M39 9c-13.7.6-23.5 6.7-26.1 15.6-1.7 5.7.8 10.8 5.4 13.1 5.4 2.7 12.3.4 15.9-5.4C37.6 26.8 38.9 18.7 39 9z"></path>
                                <path d="M12 39c4.4-8.5 11.5-14.9 21.4-19.2"></path>
                            </svg>
                        </div>
                        <h3>Sustainability</h3>
                        <p>We are committed to preserving the natural beauty and cultural heritage of the destinations we visit for future generations.</p>
                    </article>

                    <article class="value-card">
                        <div class="value-icon" aria-hidden="true">
                            <svg viewBox="0 0 48 48">
                                <path d="M24 5l14 5v11c0 9.2-5.7 17.5-14 21-8.3-3.5-14-11.8-14-21V10l14-5z"></path>
                                <path d="M16.5 24l5 5 10.5-12"></path>
                            </svg>
                        </div>
                        <h3>Authenticity</h3>
                        <p>We strive to provide genuine, immersive experiences that go beyond the typical tourist traps, connecting you with local life.</p>
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
                        <p>We embrace the unknown and encourage a spirit of exploration, pushing boundaries to create unforgettable journeys.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="journey-section" aria-labelledby="journey-title">
            <div class="journey-inner">
                <h2 id="journey-title">The Journey</h2>
                <div class="timeline">
                    <article class="timeline-item">
                        <span>2010</span>
                        <h3>The Inception</h3>
                        <p>GlobeTrek was founded in Negombo with a simple idea: to showcase the extraordinary beauty of Sri Lanka through authentic, locally-driven travel experiences.</p>
                    </article>

                    <article class="timeline-item">
                        <span>2015</span>
                        <h3>Island-Wide Expansion</h3>
                        <p>Expanded operations across all nine provinces of Sri Lanka, partnering with local communities, guides, and eco-lodges to offer comprehensive island tours.</p>
                    </article>

                    <article class="timeline-item">
                        <span>2020</span>
                        <h3>Sustainable Focus</h3>
                        <p>Launched our comprehensive sustainability initiative, ensuring all tours support local communities, protect wildlife habitats, and preserve Sri Lanka's natural heritage.</p>
                    </article>

                    <article class="timeline-item">
                        <span>Today</span>
                        <h3>Connecting Thousands</h3>
                        <p>Continuing to innovate and share the magic of Sri Lanka with passionate travelers from around the globe.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="team-section" aria-labelledby="team-title">
            <div class="team-inner">
                <h2 id="team-title">Meet The Team</h2>
                <p class="team-intro">The passionate people behind GlobeTrek Adventures — dedicated to sharing the magic of Sri Lanka with the world.</p>
                <div class="team-grid">
                    <article class="team-member">
                        <div class="team-photo">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop&crop=face" alt="Kavinda Perera portrait">
                        </div>
                        <h3>Kavinda Perera</h3>
                        <p class="team-role">Founder &amp; CEO</p>
                        <p class="team-bio">Born and raised in Negombo, Kavinda's lifelong passion for Sri Lanka's hidden gems led him to create GlobeTrek. With over 15 years in the tourism industry, his vision is to put authentic Sri Lankan experiences on the global map.</p>
                        <div class="team-socials" aria-label="Kavinda Perera social links">
                            <a href="#" aria-label="Kavinda Perera on LinkedIn">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.7 10.7l6.6-3.4M8.7 13.3l6.6 3.4"/></svg>
                            </a>
                            <a href="#" aria-label="Kavinda Perera on Twitter">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
                            </a>
                        </div>
                    </article>

                    <article class="team-member">
                        <div class="team-photo">
                            <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&h=400&fit=crop&crop=face" alt="Dinesh Fernando portrait">
                        </div>
                        <h3>Dinesh Fernando</h3>
                        <p class="team-role">Head of Expeditions</p>
                        <p class="team-bio">A seasoned mountain guide from Nuwara Eliya, Dinesh has led over 200 expeditions across Sri Lanka's hill country, from Horton Plains to Adam's Peak. His expertise ensures every trek is safe, enriching, and unforgettable.</p>
                        <div class="team-socials" aria-label="Dinesh Fernando social links">
                            <a href="#" aria-label="Dinesh Fernando on LinkedIn">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.7 10.7l6.6-3.4M8.7 13.3l6.6 3.4"/></svg>
                            </a>
                            <a href="#" aria-label="Dinesh Fernando on Twitter">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
                            </a>
                        </div>
                    </article>

                    <article class="team-member">
                        <div class="team-photo">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&h=400&fit=crop&crop=face" alt="Amara Wickramasinghe portrait">
                        </div>
                        <h3>Amara Wickramasinghe</h3>
                        <p class="team-role">Chief Sustainability Officer</p>
                        <p class="team-bio">Amara leads our eco-initiatives, working closely with rural communities and wildlife conservation projects across Sri Lanka to ensure every trip leaves a positive impact on the island and its people.</p>
                        <div class="team-socials" aria-label="Amara Wickramasinghe social links">
                            <a href="#" aria-label="Amara Wickramasinghe on LinkedIn">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.7 10.7l6.6-3.4M8.7 13.3l6.6 3.4"/></svg>
                            </a>
                            <a href="#" aria-label="Amara Wickramasinghe on Twitter">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
                            </a>
                        </div>
                    </article>

                    <article class="team-member">
                        <div class="team-photo">
                            <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop&crop=face" alt="Nuwan Jayawardena portrait">
                        </div>
                        <h3>Nuwan Jayawardena</h3>
                        <p class="team-role">Lead Destination Guide</p>
                        <p class="team-bio">Fluent in Sinhala, Tamil, and English, Nuwan is a cultural enthusiast from Kandy who curates the immersive heritage experiences GlobeTrek is known for. His deep local knowledge brings Sri Lanka's history to life.</p>
                        <div class="team-socials" aria-label="Nuwan Jayawardena social links">
                            <a href="#" aria-label="Nuwan Jayawardena on LinkedIn">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.7 10.7l6.6-3.4M8.7 13.3l6.6 3.4"/></svg>
                            </a>
                            <a href="#" aria-label="Nuwan Jayawardena on Twitter">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <br>

        <section class="about-cta" aria-labelledby="cta-title">
            <h2 id="cta-title">Work With Us</h2>
            <p>Join our passionate team and help us share the beauty of Sri Lanka with travelers from around the world.</p>
            <a href="guides.php">View Careers</a>
        </section>
    </main>

    <br>

    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
</body>
</html>
