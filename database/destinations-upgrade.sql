USE globetrek;

-- Add new columns to destinations table
ALTER TABLE destinations
    ADD COLUMN region VARCHAR(100) DEFAULT NULL AFTER description,
    ADD COLUMN category VARCHAR(100) DEFAULT NULL AFTER region,
    ADD COLUMN rating DECIMAL(2,1) DEFAULT 0.0 AFTER category,
    ADD COLUMN review_count INT DEFAULT 0 AFTER rating;

-- Update existing destinations with region, category, rating, review_count
UPDATE destinations SET region = 'Central Province', category = 'Cultural', rating = 4.8, review_count = 1250 WHERE slug = 'sigiriya';
UPDATE destinations SET region = 'Southern Province', category = 'Heritage', rating = 4.8, review_count = 1050 WHERE slug = 'galle';
UPDATE destinations SET region = 'Uva Province', category = 'Adventure', rating = 4.8, review_count = 1150 WHERE slug = 'nine-arch';
UPDATE destinations SET region = 'North Central Province', category = 'Cultural', rating = 4.6, review_count = 780 WHERE slug = 'polonnaruwa';
UPDATE destinations SET region = 'Central Province', category = 'Mountain', rating = 4.7, review_count = 900 WHERE slug = 'nuwara-eliya';
UPDATE destinations SET region = 'Southern Province', category = 'Beach', rating = 4.9, review_count = 980 WHERE slug = 'mirissa';

-- Insert additional sample destinations
INSERT INTO destinations (name, slug, description, region, category, image, rating, review_count, is_featured, is_active) VALUES
('Yala National Park', 'yala-national-park', 'Home to leopards, elephants and diverse wildlife. One of the most visited national parks in Sri Lanka, offering incredible safari experiences.', 'Southern Province', 'Wildlife', 'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=600&h=400&fit=crop', 4.9, 1300, 1, 1),
('Unawatuna', 'unawatuna', 'Tropical beach with clear waters and vibrant life. One of the most popular beach destinations in Sri Lanka with excellent snorkeling.', 'Southern Province', 'Beach', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&h=400&fit=crop', 4.7, 870, 1, 1),
('Kandy', 'kandy', 'Cultural capital and home to the Sacred Tooth Relic. Nestled in the hills, Kandy is a UNESCO World Heritage site rich with history.', 'Central Province', 'Cultural', 'https://images.unsplash.com/photo-1586521995568-095a3c17fb89?w=600&h=400&fit=crop', 4.8, 1100, 1, 1),
('Ella', 'ella', 'Scenic landscapes, hiking trails and breathtaking views. A charming hill country village famous for the Nine Arch Bridge and Little Adam\'s Peak.', 'Uva Province', 'Adventure', 'https://images.unsplash.com/photo-1559038300-07cb5d6c3d27?w=600&h=400&fit=crop', 4.8, 1150, 1, 1),
('Trincomalee', 'trincomalee', 'Pristine beaches and natural harbor on the east coast. Famous for whale watching, Pigeon Island, and beautiful coral reefs.', 'Eastern Province', 'Beach', 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=600&h=400&fit=crop', 4.6, 720, 0, 1),
('Anuradhapura', 'anuradhapura', 'Ancient sacred city with well-preserved ruins of an entire civilization. One of the oldest continuously inhabited cities in the world.', 'North Central Province', 'Heritage', 'https://images.unsplash.com/photo-1588413949674-6c6e54ab6ea0?w=600&h=400&fit=crop', 4.7, 850, 0, 1);

-- Add destination_id support to wishlist table
ALTER TABLE wishlist
    ADD COLUMN destination_id INT DEFAULT NULL AFTER package_id,
    ADD FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE;

-- Create newsletter_subscribers table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
