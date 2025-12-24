CREATE TABLE IF NOT EXISTS line_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    line_account_id INT NOT NULL COMMENT 'Bot ID',
    group_id VARCHAR(50) NOT NULL COMMENT 'LINE Group/Room ID',
    group_type ENUM('group', 'room') DEFAULT 'group' COMMENT 'Type: group or room',
    group_name VARCHAR(255) COMMENT 'Group name',
    picture_url TEXT COMMENT 'Group picture URL',
    member_count INT DEFAULT 0 COMMENT 'Number of members',
    invited_by VARCHAR(50) COMMENT 'User ID who invited bot',
    invited_by_name VARCHAR(255) COMMENT 'Name of user who invited',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=bot in group, 0=bot left/kicked',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When bot joined',
    left_at TIMESTAMP NULL COMMENT 'When bot left',
    last_activity_at TIMESTAMP NULL COMMENT 'Last message in group',
    total_messages INT DEFAULT 0 COMMENT 'Total messages in group',
    settings JSON COMMENT 'Group-specific settings',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_account_group (line_account_id, group_id),
    INDEX idx_groups_account (line_account_id),
    INDEX idx_groups_active (is_active),
    INDEX idx_groups_type (group_type)
);

CREATE TABLE IF NOT EXISTS line_group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL COMMENT 'FK to line_groups',
    line_user_id VARCHAR(50) NOT NULL COMMENT 'LINE User ID',
    user_id INT DEFAULT NULL COMMENT 'FK to users table',
    display_name VARCHAR(255) COMMENT 'Display name in group',
    picture_url TEXT COMMENT 'Picture URL',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=in group, 0=left',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    total_messages INT DEFAULT 0 COMMENT 'Messages sent in group',
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_group_member (group_id, line_user_id),
    INDEX idx_members_group (group_id),
    INDEX idx_members_user (line_user_id),
    INDEX idx_members_active (is_active),
    FOREIGN KEY (group_id) REFERENCES line_groups(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS line_group_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL COMMENT 'FK to line_groups',
    line_user_id VARCHAR(50) COMMENT 'Sender LINE User ID',
    message_type VARCHAR(50) DEFAULT 'text',
    content TEXT,
    message_id VARCHAR(50) COMMENT 'LINE Message ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gmsg_group (group_id),
    INDEX idx_gmsg_user (line_user_id),
    INDEX idx_gmsg_created (created_at),
    FOREIGN KEY (group_id) REFERENCES line_groups(id) ON DELETE CASCADE
);
