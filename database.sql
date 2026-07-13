-- ============================================================
-- CampusTrade — Database Setup Script
-- UECS2094 / UECS2194 / EECS2194 Web Application Development
--
-- Usage: import this file in phpMyAdmin, or run:
--   mysql -u root < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS campustrade
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE campustrade;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE users (
  user_id       INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  email         VARCHAR(100) NOT NULL UNIQUE,
  password      VARCHAR(255) NOT NULL,
  phone         VARCHAR(20)  NULL,
  profile_image VARCHAR(255) NOT NULL DEFAULT 'default.png',
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: listings
-- ------------------------------------------------------------
CREATE TABLE listings (
  listing_id     INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL,
  title          VARCHAR(100) NOT NULL,
  description    TEXT NOT NULL,
  price          DECIMAL(10,2) NOT NULL,
  category       VARCHAR(50) NOT NULL,
  item_condition ENUM('New','Like New','Good','Fair') NOT NULL,
  image          VARCHAR(255) NULL,
  status         ENUM('Available','Sold','Reserved') NOT NULL DEFAULT 'Available',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_listings_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: wishlist
-- UNIQUE (user_id, listing_id) prevents duplicate saves.
-- ------------------------------------------------------------
CREATE TABLE wishlist (
  wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  listing_id  INT NOT NULL,
  added_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_listing FOREIGN KEY (listing_id)
    REFERENCES listings(listing_id) ON DELETE CASCADE,
  CONSTRAINT uq_wishlist UNIQUE (user_id, listing_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: reviews
-- Rating range 1–5 enforced with a CHECK constraint (MySQL 8+)
-- and validated again in PHP.
-- ------------------------------------------------------------
CREATE TABLE reviews (
  review_id  INT AUTO_INCREMENT PRIMARY KEY,
  listing_id INT NOT NULL,
  user_id    INT NOT NULL,
  rating     INT NOT NULL,
  comment    TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_listing FOREIGN KEY (listing_id)
    REFERENCES listings(listing_id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: contact_messages
-- ------------------------------------------------------------
CREATE TABLE contact_messages (
  message_id   INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100) NOT NULL,
  email        VARCHAR(100) NOT NULL,
  subject      VARCHAR(200) NOT NULL,
  message      TEXT NOT NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Sample / seed data
-- All seed accounts use the password:  password123
-- ============================================================

INSERT INTO users (username, email, password, phone, role) VALUES
('admin',    'admin@campustrade.test',  '$2y$12$363/L51TUGHWAidBIcH.2e8NLK4.As3PwOAUrvj3GvdAJtJDXg0Ge', '012-0000000', 'admin'),
('aisyah',   'aisyah@1utar.my',         '$2y$12$363/L51TUGHWAidBIcH.2e8NLK4.As3PwOAUrvj3GvdAJtJDXg0Ge', '012-3456789', 'user'),
('weijie',   'weijie@1utar.my',         '$2y$12$363/L51TUGHWAidBIcH.2e8NLK4.As3PwOAUrvj3GvdAJtJDXg0Ge', '016-9876543', 'user'),
('priya',    'priya@1utar.my',          '$2y$12$363/L51TUGHWAidBIcH.2e8NLK4.As3PwOAUrvj3GvdAJtJDXg0Ge', '017-2223333', 'user'),
('marcus',   'marcus@1utar.my',         '$2y$12$363/L51TUGHWAidBIcH.2e8NLK4.As3PwOAUrvj3GvdAJtJDXg0Ge', NULL,          'user');

INSERT INTO listings (user_id, title, description, price, category, item_condition, status, created_at) VALUES
(2, 'Calculus Early Transcendentals 8th Ed', 'James Stewart textbook used for UECM1024. Minor highlighting in chapters 1-3, otherwise clean. No missing pages.', 45.00, 'Textbooks', 'Good', 'Available', NOW() - INTERVAL 2 DAY),
(2, 'Casio FX-570EX Scientific Calculator', 'Approved model for UTAR exams. Fully working, comes with cover. Selling because I graduated.', 35.00, 'Electronics', 'Like New', 'Available', NOW() - INTERVAL 3 DAY),
(3, 'IKEA Study Desk (LINNMON)', '100x60cm white desk, sturdy legs. Perfect for hostel room. Self collect at Westlake condo.', 80.00, 'Furniture', 'Good', 'Available', NOW() - INTERVAL 4 DAY),
(3, 'Fundamentals of Database Systems 7th Ed', 'Elmasri & Navathe. Required for UECS2084. Very good condition, no writing inside.', 50.00, 'Textbooks', 'Like New', 'Available', NOW() - INTERVAL 5 DAY),
(4, 'Logitech M185 Wireless Mouse', 'Reliable wireless mouse with USB receiver. Battery included. Used for one semester only.', 25.00, 'Electronics', 'Like New', 'Available', NOW() - INTERVAL 6 DAY),
(4, 'Lab Coat Size M', 'White lab coat required for chemistry/biology labs. Washed and ironed. Worn fewer than 10 times.', 20.00, 'Clothing', 'Good', 'Available', NOW() - INTERVAL 7 DAY),
(5, 'Acer Aspire 5 Laptop (i5, 8GB, 512GB)', 'Reliable laptop for programming and assignments. Battery health ~85%. Comes with charger and sleeve. Reason for selling: upgraded.', 1250.00, 'Electronics', 'Good', 'Available', NOW() - INTERVAL 8 DAY),
(5, 'Engineering Mathematics K.A. Stroud', 'Classic engineering maths reference. Cover slightly worn but all pages intact.', 30.00, 'Textbooks', 'Fair', 'Available', NOW() - INTERVAL 9 DAY),
(2, 'Foldable Clothes Drying Rack', 'Stainless steel drying rack, folds flat. Great for hostel balcony.', 15.00, 'Others', 'Good', 'Available', NOW() - INTERVAL 10 DAY),
(3, 'A4 Ring Files x6 (Assorted Colours)', 'Six thick A4 ring files, lightly used for one semester. Selling as a bundle.', 12.00, 'Stationery', 'Good', 'Available', NOW() - INTERVAL 11 DAY),
(4, 'Mini Fridge 45L', 'Compact fridge suitable for hostel room. Cold and quiet. Self collect only.', 180.00, 'Electronics', 'Good', 'Reserved', NOW() - INTERVAL 12 DAY),
(5, 'Office Chair with Wheels', 'Adjustable height office chair, comfortable for long study sessions. Small scratch on armrest.', 65.00, 'Furniture', 'Fair', 'Available', NOW() - INTERVAL 13 DAY),
(2, 'Graphing Paper Pad + Drawing Set', 'Unused graphing pad plus compass/protractor set still in packaging.', 10.00, 'Stationery', 'New', 'Available', NOW() - INTERVAL 14 DAY),
(3, 'UTAR Hoodie Size L', 'Official UTAR merchandise hoodie, dark blue. Worn twice, like new.', 40.00, 'Clothing', 'Like New', 'Sold', NOW() - INTERVAL 15 DAY),
(4, 'Principles of Marketing Kotler 17th Ed', 'Used for UKMM1043. Some notes in pencil, easy to erase.', 38.00, 'Textbooks', 'Good', 'Available', NOW() - INTERVAL 16 DAY),
(5, 'Desk Lamp with USB Port', 'LED desk lamp with 3 brightness levels and a USB charging port at the base.', 22.00, 'Electronics', 'Like New', 'Available', NOW() - INTERVAL 17 DAY);

INSERT INTO reviews (listing_id, user_id, rating, comment, created_at) VALUES
(1, 3, 5, 'Bought this book — exactly as described, very clean copy. Friendly seller!', NOW() - INTERVAL 1 DAY),
(1, 4, 4, 'Good price for this textbook, highlighting is minimal as stated.', NOW() - INTERVAL 12 HOUR),
(3, 2, 5, 'Desk is sturdy and the seller helped me carry it. Recommended.', NOW() - INTERVAL 2 DAY),
(7, 2, 4, 'Laptop runs smoothly, honest description of battery condition.', NOW() - INTERVAL 3 DAY),
(14, 5, 5, 'Hoodie quality is great, fast deal on campus.', NOW() - INTERVAL 5 DAY);

INSERT INTO wishlist (user_id, listing_id) VALUES
(2, 3), (2, 7), (3, 1), (3, 5), (4, 4), (5, 6), (2, 14);
