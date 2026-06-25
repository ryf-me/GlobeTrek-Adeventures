-- Guide Reviews Migration
-- Run this SQL to add the guide_reviews table

USE globetrek;

-- Guide Reviews table (separate from testimonials)
CREATE TABLE IF NOT EXISTS guide_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    guide_id INT NOT NULL,
    reviewer_name VARCHAR(150) NOT NULL,
    reviewer_country VARCHAR(100) DEFAULT NULL,
    reviewer_avatar VARCHAR(500) DEFAULT NULL,
    rating TINYINT NOT NULL,
    title VARCHAR(200) DEFAULT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (guide_id) REFERENCES guides(id) ON DELETE CASCADE,
    INDEX idx_guide_reviews_guide (guide_id),
    INDEX idx_guide_reviews_status (status),
    INDEX idx_guide_reviews_user (user_id)
) ENGINE=InnoDB;

-- Seed sample guide reviews
INSERT INTO guide_reviews (user_id, guide_id, reviewer_name, reviewer_country, reviewer_avatar, rating, title, content, status) VALUES
(1, 1, 'Sarah Mitchell', 'United Kingdom', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face', 5, 'Kasun was amazing!', 'Kasun made our hill country trek absolutely unforgettable. His knowledge of tea plantations is incredible, and he shared stories that brought the landscape to life. Highly recommend him for any Nuwara Eliya adventure.', 'approved'),
(1, 3, 'Hans van der Berg', 'Netherlands', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face', 4, 'Great safari guide', 'Ravi is passionate about wildlife and it shows. He spotted animals we would have missed on our own. The only reason for 4 stars is that the vehicle could have been more comfortable, but that is not Ravis fault.', 'approved'),
(1, 2, 'Yuki Tanaka', 'Japan', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face', 5, 'Nipuni brought history to life', 'Our cultural triangle tour with Nipuni was exceptional. She has a deep understanding of archaeology and made every temple visit fascinating. Her enthusiasm is contagious.', 'approved'),
(1, 4, 'Emma Chen', 'Australia', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop&crop=face', 3, 'Decent culinary tour', 'Malsha knew the best street food spots, which was great. However, some of the kitchen visits felt rushed and we did not get enough hands-on cooking time as expected.', 'approved'),
(1, 6, 'Marco Rossi', 'Italy', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop&crop=face', 2, 'Disappointing dive experience', 'Dilini was knowledgeable about marine life, but the dive equipment was outdated and the boat trip was uncomfortable. We also waited over an hour for the boat to depart. Not what I expected for the price.', 'pending');
