-- Tags Migration
-- Creates the tags system for packages, destinations, and guides

CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tag_name (name),
    UNIQUE KEY unique_tag_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS package_tags (
    package_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (package_id, tag_id),
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS destination_tags (
    destination_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (destination_id, tag_id),
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS guide_tags (
    guide_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (guide_id, tag_id),
    FOREIGN KEY (guide_id) REFERENCES guides(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;
