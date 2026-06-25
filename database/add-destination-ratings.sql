USE globetrek;

-- Add rating and review_count to destinations (if not already present)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='globetrek' AND TABLE_NAME='destinations' AND COLUMN_NAME='rating');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE destinations ADD COLUMN rating DECIMAL(2,1) DEFAULT 4.5 AFTER category', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='globetrek' AND TABLE_NAME='destinations' AND COLUMN_NAME='review_count');
SET @sql2 = IF(@col_exists2 = 0, 'ALTER TABLE destinations ADD COLUMN review_count INT DEFAULT 100 AFTER rating', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- Populate with realistic data
UPDATE destinations SET rating = 4.8, review_count = 1250 WHERE slug = 'sigiriya';
UPDATE destinations SET rating = 4.7, review_count = 890 WHERE slug = 'galle';
UPDATE destinations SET rating = 4.9, review_count = 1150 WHERE slug = 'nine-arch';
UPDATE destinations SET rating = 4.6, review_count = 720 WHERE slug = 'polonnaruwa';
UPDATE destinations SET rating = 4.8, review_count = 980 WHERE slug = 'nuwara-eliya';
UPDATE destinations SET rating = 4.9, review_count = 1300 WHERE slug = 'mirissa';
