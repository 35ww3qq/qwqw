-- Create activity_log table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_action (user_id, action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create triggers for logging
DELIMITER //

CREATE TRIGGER after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (user_id, action, details, ip_address)
    VALUES (NEW.id, 'register', CONCAT('New user registered: ', NEW.username), '127.0.0.1');
END//

CREATE TRIGGER after_link_insert
AFTER INSERT ON links
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (user_id, action, details, ip_address)
    SELECT s.user_id, 'add_link', CONCAT('New link added to site ID: ', NEW.site_id), '127.0.0.1'
    FROM sites s WHERE s.id = NEW.site_id;
END//

DELIMITER ;