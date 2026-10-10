<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "crushify";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  $conn = new mysqli($host, $user, $pass);
  $conn->set_charset("utf8mb4");

  $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $conn->select_db($dbname);

  $conn->query("
    CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      full_name VARCHAR(120) NOT NULL,
      email VARCHAR(190) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      gender VARCHAR(30) DEFAULT NULL,
      looking_for VARCHAR(30) DEFAULT NULL,
      dob DATE DEFAULT NULL,
      location VARCHAR(120) DEFAULT NULL,
      bio TEXT DEFAULT NULL,
      profile_pic VARCHAR(255) NOT NULL DEFAULT 'default.jpg',
      occupation VARCHAR(120) DEFAULT NULL,
      marital_status VARCHAR(50) DEFAULT NULL,
      relationship_type VARCHAR(50) DEFAULT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");

  $conn->query("
    CREATE TABLE IF NOT EXISTS user_photos (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      photo VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (user_id),
      CONSTRAINT fk_user_photos_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");

  $conn->query("
    CREATE TABLE IF NOT EXISTS user_personality (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL UNIQUE,
      personality_type VARCHAR(120) DEFAULT NULL,
      hobbies TEXT DEFAULT NULL,
      interests TEXT DEFAULT NULL,
      about_me TEXT DEFAULT NULL,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      CONSTRAINT fk_user_personality_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");

  $conn->query("
    CREATE TABLE IF NOT EXISTS likes (
      id INT AUTO_INCREMENT PRIMARY KEY,
      from_user INT NOT NULL,
      to_user INT NOT NULL,
      action ENUM('like','pass') NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY unique_user_action (from_user, to_user),
      INDEX (to_user),
      CONSTRAINT fk_likes_from_user
        FOREIGN KEY (from_user) REFERENCES users(id)
        ON DELETE CASCADE,
      CONSTRAINT fk_likes_to_user
        FOREIGN KEY (to_user) REFERENCES users(id)
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");

  $conn->query("
    CREATE TABLE IF NOT EXISTS matches (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user1 INT NOT NULL,
      user2 INT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY unique_match (user1, user2),
      INDEX (user2),
      CONSTRAINT fk_matches_user1
        FOREIGN KEY (user1) REFERENCES users(id)
        ON DELETE CASCADE,
      CONSTRAINT fk_matches_user2
        FOREIGN KEY (user2) REFERENCES users(id)
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");

  $conn->query("
    CREATE TABLE IF NOT EXISTS conversations (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user1 INT NOT NULL,
      user2 INT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY unique_conversation (user1, user2),
      INDEX (user2),
      CONSTRAINT fk_conversations_user1
        FOREIGN KEY (user1) REFERENCES users(id)
        ON DELETE CASCADE,
      CONSTRAINT fk_conversations_user2
        FOREIGN KEY (user2) REFERENCES users(id)
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");

  $conn->query("
    CREATE TABLE IF NOT EXISTS messages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      conversation_id INT NOT NULL,
      sender_id INT NOT NULL,
      message TEXT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (conversation_id),
      INDEX (sender_id),
      CONSTRAINT fk_messages_conversation
        FOREIGN KEY (conversation_id) REFERENCES conversations(id)
        ON DELETE CASCADE,
      CONSTRAINT fk_messages_sender
        FOREIGN KEY (sender_id) REFERENCES users(id)
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");

  $conn->query("
    CREATE TABLE IF NOT EXISTS notifications (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      type VARCHAR(50) NOT NULL,
      content TEXT NOT NULL,
      is_read TINYINT(1) NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (user_id),
      CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
} catch (mysqli_sql_exception $e) {
  die("DB setup failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8"));
}
?>
