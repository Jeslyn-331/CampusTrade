# CampusTrade — A Secondhand Marketplace for University Students

Group assignment for **UECS2094 / UECS2194 / EECS2194 Web Application Development**.

A dynamic web application where university students buy and sell pre-owned items
(textbooks, electronics, furniture, stationery, clothing) within their campus community.

**Technology stack:** HTML5, CSS3, vanilla JavaScript, PHP, MySQL — no frameworks or libraries.

---

## Setup Instructions (XAMPP)

1. **Install XAMPP** (Apache + PHP + MySQL) and start the **Apache** and **MySQL** modules.

2. **Copy the project folder** into XAMPP's web root so the path is:
   ```
   C:\xampp\htdocs\campustrade\
   ```

3. **Import the database:**
   - Open `http://localhost/phpmyadmin`
   - Click *Import*, choose `database.sql`, and press *Go*.
   - This creates the `campustrade` database, all 5 tables, and sample data.

   Or from a terminal:
   ```
   mysql -u root < database.sql
   ```

4. **Check database credentials** in `includes/config.php` (defaults match XAMPP:
   host `localhost`, user `root`, empty password).

5. **Make sure `uploads/` is writable** (it is by default on Windows/XAMPP).
   Item photos are stored there.

6. Open **http://localhost/campustrade/** in your browser.

## Default Accounts (seed data)

All seed accounts use the password: **`password123`**

| Username | Email                    | Role  |
|----------|--------------------------|-------|
| admin    | admin@campustrade.test   | admin |
| aisyah   | aisyah@1utar.my          | user  |
| weijie   | weijie@1utar.my          | user  |
| priya    | priya@1utar.my           | user  |
| marcus   | marcus@1utar.my          | user  |

The admin account can delete any listing or review (moderation).

## Project Structure

```
campustrade/
├── database.sql          # CREATE DATABASE + tables + sample data
├── index.php             # Home page (hero, stats, categories, featured listings)
├── browse.php            # Item listing page (filters, sort, search, pagination)
├── item.php              # Item details (seller info, wishlist, reviews)
├── contact.php           # Contact form + embedded Google Map
├── register.php          # Account registration with validation
├── login.php             # Login (PHP sessions)
├── logout.php            # Session destroy
├── dashboard.php         # My listings (mark sold, edit, delete)
├── create_listing.php    # Create listing (with image upload)
├── edit_listing.php      # Edit listing
├── delete_listing.php    # Delete listing (owner or admin)
├── wishlist.php          # My wishlist
├── wishlist_action.php   # Add/remove wishlist entries
├── profile.php           # Edit profile + change password
├── includes/
│   ├── config.php        # DB credentials, constants, session bootstrap
│   ├── db.php            # mysqli connection
│   ├── functions.php     # Shared helpers (escaping, uploads, guards)
│   ├── header.php        # Navigation bar + page head
│   └── footer.php        # Footer + script include
├── css/style.css         # All styling (mobile-first media queries)
├── js/main.js            # Hamburger menu, dropdowns, client-side validation
├── images/               # Static assets (placeholder image)
└── uploads/              # User-uploaded item photos
```

## Feature → Requirement Mapping

| Req. | Feature            | Where |
|------|--------------------|-------|
| I    | Home Page          | `index.php` |
| II   | Database CRUD      | listings, reviews, wishlist, users, contact messages |
| III  | Navigation Menu    | `includes/header.php` (sticky, dropdown, hamburger) |
| IV   | Contact Page       | `contact.php` (form → DB, Google Map iframe) |
| V    | Item Listing Page  | `browse.php` (filters, sorting, 12/page pagination) |
| VI   | Item Details Page  | `item.php` (seller profile, wishlist, reviews) |
| VII  | Cart/Wishlist      | `wishlist.php` + `wishlist_action.php` |
| VIII | User Login System  | `register.php`, `login.php`, `profile.php`, `logout.php` |
| IX   | Responsive Design  | `css/style.css` media queries (<768, 768–1024, >1024) |

## Security Measures

- All SQL uses **mysqli prepared statements** (no string concatenation).
- Passwords hashed with `password_hash()` / verified with `password_verify()`.
- All user content escaped with `htmlspecialchars()` on output.
- `session_regenerate_id()` on login, HttpOnly cookies, 30-minute inactivity timeout.
- Server-side validation on every form (client-side JS is a convenience only).
- Image uploads: real MIME check via `finfo`, 2 MB limit, renamed with `uniqid()`.
- Ownership checks on every UPDATE/DELETE (`... AND user_id = ?`).
