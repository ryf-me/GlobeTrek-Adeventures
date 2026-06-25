-- Homepage Redesign Migration
-- Adds category/rating/review_count to destinations, rating/languages/years_experience to guides

USE globetrek;

-- Add category, rating, review_count columns to destinations
ALTER TABLE destinations ADD COLUMN category VARCHAR(50) DEFAULT 'Cultural' AFTER description;
ALTER TABLE destinations ADD COLUMN rating DECIMAL(2,1) DEFAULT 4.5 AFTER category;
ALTER TABLE destinations ADD COLUMN review_count INT DEFAULT 100 AFTER rating;

-- Update existing destinations with categories
UPDATE destinations SET category = 'Cultural' WHERE slug = 'sigiriya';
UPDATE destinations SET category = 'Cultural' WHERE slug = 'galle';
UPDATE destinations SET category = 'Adventure' WHERE slug = 'nine-arch';
UPDATE destinations SET category = 'Cultural' WHERE slug = 'polonnaruwa';
UPDATE destinations SET category = 'Hill Country' WHERE slug = 'nuwara-eliya';
UPDATE destinations SET category = 'Beach' WHERE slug = 'mirissa';

-- Update existing destinations with ratings and review counts
UPDATE destinations SET rating = 4.8, review_count = 1250 WHERE slug = 'sigiriya';
UPDATE destinations SET rating = 4.7, review_count = 890 WHERE slug = 'galle';
UPDATE destinations SET rating = 4.9, review_count = 1150 WHERE slug = 'nine-arch';
UPDATE destinations SET rating = 4.6, review_count = 720 WHERE slug = 'polonnaruwa';
UPDATE destinations SET rating = 4.8, review_count = 980 WHERE slug = 'nuwara-eliya';
UPDATE destinations SET rating = 4.9, review_count = 1300 WHERE slug = 'mirissa';

-- Add new columns to guides
ALTER TABLE guides ADD COLUMN rating DECIMAL(2,1) DEFAULT 4.5 AFTER description;
ALTER TABLE guides ADD COLUMN languages VARCHAR(255) DEFAULT 'English, Sinhala' AFTER rating;
ALTER TABLE guides ADD COLUMN years_experience INT DEFAULT 5 AFTER languages;

-- Update existing guides with realistic data
UPDATE guides SET rating = 4.9, languages = 'English, Sinhala, Tamil', years_experience = 8 WHERE name = 'Kasun Bandara';
UPDATE guides SET rating = 4.8, languages = 'English, Sinhala', years_experience = 6 WHERE name = 'Nipuni Silva';
UPDATE guides SET rating = 4.9, languages = 'English, Sinhala, Tamil', years_experience = 10 WHERE name = 'Ravi Tennakoon';
UPDATE guides SET rating = 4.7, languages = 'English, Sinhala', years_experience = 5 WHERE name = 'Malsha Fernando';
UPDATE guides SET rating = 4.8, languages = 'English, Sinhala', years_experience = 7 WHERE name = 'Tharaka Perera';
UPDATE guides SET rating = 4.9, languages = 'English, Sinhala, Tamil', years_experience = 9 WHERE name = 'Dilini Jayasuriya';
