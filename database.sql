-- ============================================================
-- CampusTrade — Database Setup Script (Round 2)
-- UECS2094 / UECS2194 / EECS2194 Web Application Development
--
-- 8 tables: users, listings, wishlist, reviews, contact_messages,
--           orders, conversations, messages
--
-- Usage: import this file in phpMyAdmin, or run:
--   mysql -u root < database.sql
-- ============================================================

DROP DATABASE IF EXISTS campustrade;
CREATE DATABASE campustrade
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE campustrade;

-- ------------------------------------------------------------
-- Table: users
-- qr_image stores the seller's TNG eWallet QR code filename.
-- ------------------------------------------------------------
CREATE TABLE users (
  user_id       INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  email         VARCHAR(100) NOT NULL UNIQUE,
  password      VARCHAR(255) NOT NULL,
  phone         VARCHAR(20)  NULL,
  profile_image VARCHAR(255) NOT NULL DEFAULT 'default.png',
  qr_image      VARCHAR(255) NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: listings
-- ------------------------------------------------------------
-- price always holds the CURRENT selling price. When a discount is
-- active, original_price keeps the pre-discount price; cancelling the
-- discount restores price from original_price and clears these fields.
CREATE TABLE listings (
  listing_id     INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL,
  title          VARCHAR(100) NOT NULL,
  description    TEXT NOT NULL,
  price          DECIMAL(10,2) NOT NULL,
  original_price DECIMAL(10,2) NULL,
  is_discounted  TINYINT(1) NOT NULL DEFAULT 0,
  discounted_at  DATETIME NULL,
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
-- Table: reviews  (Round 2: attached to the SELLER, not the listing)
-- listing_id is optional context for which item the deal involved.
-- ------------------------------------------------------------
CREATE TABLE reviews (
  review_id   INT AUTO_INCREMENT PRIMARY KEY,
  seller_id   INT NOT NULL,
  reviewer_id INT NOT NULL,
  listing_id  INT NULL,
  rating      INT NOT NULL,
  comment     TEXT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_seller FOREIGN KEY (seller_id)
    REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id)
    REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_listing FOREIGN KEY (listing_id)
    REFERENCES listings(listing_id) ON DELETE SET NULL,
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

-- ------------------------------------------------------------
-- Table: orders
-- Payment methods: FPX (simulated online banking), QR (TNG
-- eWallet with proof screenshot), Cash (face-to-face meetup).
-- ------------------------------------------------------------
CREATE TABLE orders (
  order_id       INT AUTO_INCREMENT PRIMARY KEY,
  listing_id     INT NOT NULL,
  buyer_id       INT NOT NULL,
  seller_id      INT NOT NULL,
  payment_method ENUM('FPX','QR','Cash') NOT NULL,
  bank_name      VARCHAR(100) NULL,
  proof_image    VARCHAR(255) NULL,
  meetup_details TEXT NULL,
  amount         DECIMAL(10,2) NOT NULL,
  status         ENUM('Pending','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_listing FOREIGN KEY (listing_id)
    REFERENCES listings(listing_id) ON DELETE CASCADE,
  CONSTRAINT fk_orders_buyer FOREIGN KEY (buyer_id)
    REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_orders_seller FOREIGN KEY (seller_id)
    REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: conversations
-- One thread per buyer + listing (seller comes from the listing).
-- ------------------------------------------------------------
CREATE TABLE conversations (
  conversation_id INT AUTO_INCREMENT PRIMARY KEY,
  listing_id      INT NOT NULL,
  buyer_id        INT NOT NULL,
  seller_id       INT NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_conv_listing FOREIGN KEY (listing_id)
    REFERENCES listings(listing_id) ON DELETE CASCADE,
  CONSTRAINT fk_conv_buyer FOREIGN KEY (buyer_id)
    REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_conv_seller FOREIGN KEY (seller_id)
    REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT uq_conversation UNIQUE (listing_id, buyer_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: messages
-- ------------------------------------------------------------
CREATE TABLE messages (
  message_id      INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_id       INT NOT NULL,
  message_text    TEXT NOT NULL,
  is_read         TINYINT(1) NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_msg_conversation FOREIGN KEY (conversation_id)
    REFERENCES conversations(conversation_id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id)
    REFERENCES users(user_id) ON DELETE CASCADE
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
(2, 'Graphing Paper Pad + Drawing Set', 'Unused graphing pad plus compass/protractor set still in packaging.', 10.00, 'Stationery', 'New', 'Sold', NOW() - INTERVAL 14 DAY),
(3, 'UTAR Hoodie Size L', 'Official UTAR merchandise hoodie, dark blue. Worn twice, like new.', 40.00, 'Clothing', 'Like New', 'Sold', NOW() - INTERVAL 15 DAY),
(4, 'Principles of Marketing Kotler 17th Ed', 'Used for UKMM1043. Some notes in pencil, easy to erase.', 38.00, 'Textbooks', 'Good', 'Available', NOW() - INTERVAL 16 DAY),
(5, 'Desk Lamp with USB Port', 'LED desk lamp with 3 brightness levels and a USB charging port at the base.', 22.00, 'Electronics', 'Like New', 'Sold', NOW() - INTERVAL 17 DAY);

-- Two active discounts so the price-drop badge demos immediately
UPDATE listings SET original_price = 1400.00, price = 1250.00, is_discounted = 1,
  discounted_at = NOW() - INTERVAL 1 DAY WHERE listing_id = 7;   -- laptop: ~11% off
UPDATE listings SET original_price = 80.00, price = 65.00, is_discounted = 1,
  discounted_at = NOW() - INTERVAL 2 DAY WHERE listing_id = 12;  -- office chair: ~19% off

-- Orders: every Completed order backs a seller review below;
-- the Pending Cash order matches the Reserved mini fridge.
INSERT INTO orders (listing_id, buyer_id, seller_id, payment_method, bank_name, proof_image, meetup_details, amount, status, created_at) VALUES
(14, 5, 3, 'Cash', NULL, NULL, 'Meet at Block D cafeteria, Friday 2pm', 40.00, 'Completed', NOW() - INTERVAL 5 DAY),
(13, 4, 2, 'Cash', NULL, NULL, 'Library entrance, Tuesday after class (4pm)', 10.00, 'Completed', NOW() - INTERVAL 6 DAY),
(16, 2, 5, 'FPX', 'Maybank', NULL, NULL, 22.00, 'Completed', NOW() - INTERVAL 4 DAY),
(11, 2, 4, 'Cash', NULL, NULL, 'KB Block lobby, Saturday 11am', 180.00, 'Pending', NOW() - INTERVAL 1 DAY);

-- Seller reviews (each reviewer has a Completed order with that seller)
INSERT INTO reviews (seller_id, reviewer_id, listing_id, rating, comment, created_at) VALUES
(3, 5, 14, 5, 'Hoodie quality is great, fast deal on campus. Friendly and punctual seller!', NOW() - INTERVAL 4 DAY),
(2, 4, 13, 5, 'Item was brand new as described. Smooth meetup, highly recommended seller.', NOW() - INTERVAL 5 DAY),
(5, 2, 16, 4, 'Lamp works perfectly. Seller replied fast and confirmed my FPX payment quickly.', NOW() - INTERVAL 3 DAY);

INSERT INTO wishlist (user_id, listing_id) VALUES
(2, 3), (2, 7), (3, 1), (3, 5), (4, 4), (5, 6);

-- Conversations + messages
INSERT INTO conversations (listing_id, buyer_id, seller_id, created_at, updated_at) VALUES
(3, 2, 3, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 HOUR),   -- aisyah asks weijie about the desk
(7, 3, 5, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 5 HOUR),   -- weijie asks marcus about the laptop
(11, 2, 4, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY);   -- aisyah arranged the fridge deal with priya

INSERT INTO messages (conversation_id, sender_id, message_text, is_read, created_at) VALUES
(1, 2, 'Hi! Is the study desk still available?', 1, NOW() - INTERVAL 2 DAY),
(1, 3, 'Yes it is! You can self collect at Westlake condo.', 1, NOW() - INTERVAL 2 DAY + INTERVAL 10 MINUTE),
(1, 2, 'Great, would Saturday morning work for you?', 0, NOW() - INTERVAL 2 HOUR),
(2, 3, 'Hello, how is the battery life on the laptop?', 1, NOW() - INTERVAL 1 DAY),
(2, 5, 'About 4-5 hours of normal use. Battery health is around 85%.', 0, NOW() - INTERVAL 5 HOUR),
(3, 2, 'Hi, I would like to buy the mini fridge. Cash on Saturday?', 1, NOW() - INTERVAL 1 DAY),
(3, 4, 'Sure! KB Block lobby at 11am works for me.', 1, NOW() - INTERVAL 1 DAY + INTERVAL 15 MINUTE);
