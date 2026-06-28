<?php
/**
 * File: pages/faq.php
 * Purpose: Frequently Asked Questions page with categorized questions and answers about GlobeTrek services
 * Dependencies: css/style.css, css/navbar.css, css/support.css, css/footer.css, includes/navbar.php, includes/footer.php, js/script.js
 * Used By: Main website navigation, footer links, support section
 * Parent Files: index.php (via navigation), navbar.php (via link), contact.php (via FAQ section)
 * Child Files: None
 * @package GlobeTrek\Pages
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - GlobeTrek Adventures</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/support.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body class="support-page">
    <!-- Navigation Bar -->
    <?php $basePath = '../'; include '../includes/navbar.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="support-hero" aria-labelledby="faq-title">
            <img class="support-hero-bg" src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0" alt="">
            <div class="support-hero-content">
                <h1 id="faq-title">Frequently Asked Questions</h1>
                <p>Find answers to the most common questions about GlobeTrek Adventures, our tours, bookings, and policies.</p>
            </div>
        </section>

        <!-- FAQ Content Section -->
        <section class="support-content" aria-labelledby="faq-section-title">
            <!-- General Questions Category -->
            <h2 id="faq-section-title">General Questions</h2>

            <div class="faq-list">
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>What is GlobeTrek Adventures?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            GlobeTrek Adventures is a premier travel company dedicated to providing authentic, sustainable, and immersive travel experiences across the globe. Founded by Insath Raif, a passionate software engineering student and knowledgeable developer, GlobeTrek combines cutting-edge technology with a deep love for exploration to deliver unforgettable journeys.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Who is the founder of GlobeTrek?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            GlobeTrek Adventures was founded by Insath Raif, a well-known software engineering student and highly knowledgeable developer. His vision was to create a platform that leverages technology to make travel planning seamless while promoting sustainable tourism practices worldwide.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>What destinations does GlobeTrek cover?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            We offer curated travel experiences across Sri Lanka, covering all nine provinces of the island. From the sun-kissed southern beaches to the misty hill country, ancient cultural sites to vibrant city life — we have something for every type of traveler exploring the pearl of the Indian Ocean.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking & Payments Category -->
            <h2>Booking &amp; Payments</h2>

            <div class="faq-list">
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>How do I book a trip?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Booking a trip with GlobeTrek is simple. Browse our packages or destinations, select your preferred tour, choose your travel dates, and complete the booking form. You'll receive a confirmation email with all the details. If you need a custom itinerary, you can submit a custom trip request through our platform.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>What payment methods do you accept?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            We accept all major credit and debit cards (Visa, MasterCard, American Express), bank transfers, and popular digital payment platforms. All transactions are securely processed with industry-standard encryption to protect your financial information.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Can I cancel or modify my booking?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Yes, you can cancel or modify your booking subject to our cancellation policy. Cancellations made 30 or more days before the departure date receive a full refund. Cancellations made 15-29 days prior receive a 50% refund. Cancellations made less than 15 days before departure are non-refundable. Please refer to our <a href="payment-policy.php">Payment Policy</a> for full details.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Is a deposit required to confirm my booking?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Yes, a deposit of 30% of the total trip cost is required to confirm your booking. The remaining balance must be paid at least 14 days before the departure date. For last-minute bookings (within 14 days of departure), full payment is required at the time of booking.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tours & Experiences Category -->
            <h2>Tours &amp; Experiences</h2>

            <div class="faq-list">
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>What is included in a typical tour package?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Most of our tour packages include accommodation, guided tours, local transportation, selected meals, and entry fees to attractions. Specific inclusions vary by package and are clearly listed on each package detail page. International airfare is typically not included unless specified.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Do you offer custom or private tours?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Absolutely! We specialize in crafting personalized travel experiences. You can submit a custom trip request through our <a href="custom-trips.php">Custom Trips</a> page, and our team will work with you to design an itinerary that matches your interests, budget, and schedule. Insath Raif and the development team have built our platform to make this process as smooth as possible.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Are your tours suitable for solo travelers?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Yes! Many of our travelers explore solo. Our group tours are a great way to meet fellow adventurers, and we also offer private tour options for those who prefer a more personalized experience. Safety is our top priority, and all our guides are trained to ensure solo travelers feel comfortable and secure.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>What is the typical group size for tours?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Our group tours typically accommodate 8-16 travelers to ensure a personalized and intimate experience. Private tours can be arranged for individuals, couples, or larger groups upon request. We believe smaller groups allow for deeper cultural immersion and a more meaningful travel experience.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Safety & Support Category -->
            <h2>Safety &amp; Support</h2>

            <div class="faq-list">
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Is travel insurance included?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Travel insurance is not automatically included in our packages, but we strongly recommend it. We can help you arrange comprehensive travel insurance that covers medical emergencies, trip cancellations, lost luggage, and more. Our team will guide you through the options during the booking process.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>How do I contact support during my trip?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            We provide 24/7 support during your trip. You can reach our support team via the chat bubble on our website, by email at <a href="mailto:info@globetrek.lk">info@globetrek.lk</a>, or by calling our emergency hotline. Your local guide will also be available to assist you throughout the journey.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span>Do I need a visa for my destination?</span>
                        <span class="faq-toggle" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            Visa requirements vary by destination and your nationality. Once your booking is confirmed, we provide detailed visa guidance for your specific destination. We recommend checking with the embassy or consulate of your destination country well in advance of your trip.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Developer Credit -->
            <div class="developer-credit">
                <p>
                    Developed with care by <span class="dev-name">Insath Raif</span> — a passionate software engineering student and knowledgeable developer dedicated to building innovative digital solutions. GlobeTrek Adventures' platform was designed and developed by Insath Raif to provide travelers with a seamless and enjoyable booking experience.
                </p>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php $basePath = '../'; include '../includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="../js/script.js"></script>
    <script>
    // FAQ accordion functionality
    document.querySelectorAll('.faq-question').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var item = this.closest('.faq-item');
            var isActive = item.classList.contains('active');

            // Close all other items in the same list
            item.closest('.faq-list').querySelectorAll('.faq-item.active').forEach(function(openItem) {
                if (openItem !== item) {
                    openItem.classList.remove('active');
                    openItem.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
                }
            });

            // Toggle current item
            item.classList.toggle('active');
            this.setAttribute('aria-expanded', !isActive);
        });
    });
    </script>
</body>
</html>