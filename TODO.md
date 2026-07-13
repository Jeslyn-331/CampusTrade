# CampusTrade — Pre-Build Checklist

Derived from the CampusTrade Project Proposal (UECS2094/UECS2194/EECS2194 Group Assignment).
Work through every section **before** writing application code.

---

## 1. Administrative / Proposal Gaps (fill in before submission)

- [ ] Fill in Practical Class number (P__)
- [ ] Fill in Group Number (G__)
- [ ] Fill in all 4 group member names and student IDs
- [ ] Fill in submission date
- [ ] Generate the Table of Contents (currently empty in the proposal)
- [ ] Assign real names to Member 1–4 in the Workload Summary table
- [ ] Confirm the workload split with all members (each member must be able to explain ANY part of the code during demo)
- [ ] Fill in "Accessed" dates in the References section (Harvard format)

## 2. Environment Setup (every member)

- [ ] Install XAMPP (or equivalent Apache + PHP + MySQL stack) — agree on ONE PHP version for the whole team
- [ ] Verify Apache runs and serves PHP from `htdocs`
- [ ] Verify MySQL/phpMyAdmin is accessible
- [ ] Decide project folder name and URL (e.g., `http://localhost/campustrade/`)
- [ ] Agree on code editor + formatting conventions (indentation, naming)
- [ ] Set up shared code repository (Git/GitHub) and make sure all 4 members can push/pull
- [ ] Create agreed folder structure, e.g.:
  - `/css`, `/js`, `/images`, `/uploads` (item photos), `/includes` (db.php, header.php, footer.php), `/pages` or root PHP pages

## 3. Requirements Compliance Check (the 9 assignment requirements)

Confirm the plan covers each required feature and who owns it:

- [ ] I — Home Page: hero banner, featured listings, category quick-links, search bar, platform statistics
- [ ] II — Database CRUD: Create/Read/Update/Delete on listings + CRUD on reviews and wishlist
- [ ] III — Navigation Menu: sticky navbar, category dropdown, search, login/register links, wishlist icon, hamburger on mobile
- [ ] IV — Contact Page: form (name, email, subject, message), embedded Google Map, contact details, social links
- [ ] V — Item Listing Page: card grid, filters (category, price range, condition), sorting (date, price), pagination (12/page)
- [ ] VI — Item Details Page: image, full info, seller profile, Contact Seller, Add to Wishlist, reviews/ratings section
- [ ] VII — Wishlist Page: saved items, remove, status indicator (Available/Sold), login-protected
- [ ] VIII — User Login System: register with validation, login with PHP sessions, profile management, change password, logout
- [ ] IX — Responsive Design: pure CSS media queries for mobile (<768px), tablet (768–1024px), desktop (>1024px)

## 4. Technology Constraint Check (critical — grade risk)

- [ ] Confirm NO frameworks/libraries anywhere: no Bootstrap, Tailwind, jQuery, React, Vue, Angular, Laravel, CodeIgniter, Material UI, AdminLTE, no templates or website builders
- [ ] Only HTML5, CSS3, vanilla JavaScript, PHP, MySQL
- [ ] Google Map is embedded via plain `<iframe>` (allowed) — confirm with lecturer if unsure
- [ ] If using icons/fonts, decide how (own SVG/CSS vs. external CDN) — check whether external fonts/icon CDNs count as "libraries" with the lecturer
- [ ] Verify assignment brief for anything the proposal doesn't mention (report format, demo video length, ZIP naming, plagiarism rules)

## 5. Database Design — Verify Before Creating

- [ ] Create database (agree on name, e.g., `campustrade`) and charset `utf8mb4`
- [ ] Create the 5 tables exactly as designed: `users`, `listings`, `wishlist`, `reviews`, `contact_messages`
- [ ] Verify all foreign keys: listings.user_id → users; wishlist.user_id/listing_id; reviews.user_id/listing_id
- [ ] Decide FK ON DELETE behavior (e.g., deleting a listing should delete its wishlist entries and reviews — CASCADE) — proposal doesn't specify this
- [ ] Add UNIQUE constraint on wishlist (user_id, listing_id) to prevent duplicate saves — not in proposal, needed
- [ ] Confirm ENUM values: item_condition ('New','Like New','Good','Fair'), status ('Available','Sold','Reserved')
- [ ] Confirm categories list is fixed: Textbooks, Electronics, Furniture, Stationery, Clothing, Others (proposal stores category as VARCHAR — decide if a categories table or a fixed PHP array is used)
- [ ] Note: MySQL INT(1) for rating does NOT restrict range — enforce 1–5 in PHP (and CHECK constraint if MySQL 8+)
- [ ] Write `database.sql` script (CREATE DATABASE + tables) checked into the repo
- [ ] Prepare sample/seed data: at least a few users, 12+ listings (to test pagination/filters), reviews, wishlist entries
- [ ] Plan the final SQL export for submission (required deliverable in Week 12)

## 6. Design Decisions to Settle Before Coding

- [ ] Wireframes/mockups for all pages (proposal Week 1–2 deliverable)
- [ ] Color scheme, fonts, logo for CampusTrade
- [ ] Card layout design (image, title, price RM, condition badge colors, seller, date)
- [ ] Single image per listing (schema has one `image` column) vs. "image gallery" mentioned in section 6.4 — resolve this contradiction: either add a `listing_images` table or scope to one image
- [ ] "Contact Seller" behavior: reveal email/phone only to logged-in users (per proposal) — confirm no chat system is expected
- [ ] Decide whether "admin can delete listings" (Req. II mentions admins) needs an admin role — schema has NO role column; either add `role` to users or drop the admin claim
- [ ] Search behavior: which fields does search match (title only, or title + description)?
- [ ] Pagination style (page numbers vs. next/prev), 12 items per page
- [ ] What guests can do vs. logged-in users (guests: browse + view details + contact page; login required: wishlist, reviews, create listing, contact seller)
- [ ] Error/empty states: no search results, empty wishlist, sold items, deleted listing

## 7. Security Checklist (implement from day one, not at the end)

- [ ] ALL SQL uses prepared statements (mysqli or PDO — pick ONE for the whole team)
- [ ] Passwords stored with `password_hash()` / verified with `password_verify()` — never plain text
- [ ] All output of user content escaped with `htmlspecialchars()`
- [ ] `session_regenerate_id()` on login; HttpOnly session cookies; session timeout
- [ ] Server-side validation on EVERY form (client-side JS validation is extra, not the authority)
- [ ] File uploads: MIME type check, size limit, rename file (e.g., uniqid) to prevent traversal; store only filename in DB
- [ ] Ownership checks on all UPDATE/DELETE (`... WHERE listing_id = ? AND user_id = ?`)
- [ ] Protected pages check `$_SESSION` and redirect guests to login

## 8. Per-Member Task Confirmation (from Workload Summary)

- [ ] Member 1 — Front-end lead: Home page, navigation, responsive CSS, overall layout/styling
- [ ] Member 2 — Backend lead: register/login/profile, database setup, session management
- [ ] Member 3 — Listing module: listing page, item details, create/edit listing, reviews & ratings
- [ ] Member 4 — Wishlist & contact: wishlist page, contact page, search & filter, testing & QA
- [ ] Agree on shared files (db connection include, header/footer includes, CSS variables) BEFORE members start, so pages integrate cleanly
- [ ] Agree on session variable names (e.g., `$_SESSION['user_id']`, `$_SESSION['username']`) — everyone must use the same ones

## 9. Timeline Sanity Check (12-week plan)

- [ ] Wk 1–2: requirements final, DB schema, wireframes, page flow — DONE before coding
- [ ] Wk 3–4: DB + sample data, register/login, sessions
- [ ] Wk 5–6: home, nav, listing page, item details
- [ ] Wk 7–8: create/edit/delete listing, wishlist, reviews, contact form
- [ ] Wk 9–10: media queries all pages, cross-browser testing, polish
- [ ] Wk 11: integration, end-to-end testing, security review
- [ ] Wk 12: report, demo video, README, SQL export, ZIP packaging
- [ ] Map these weeks to actual calendar dates and the real submission deadline

## 10. Final Deliverables (know them now, prepare from the start)

- [ ] Project report (proposal format finalized, screenshots as you build)
- [ ] Demo video recording
- [ ] README file (setup steps: import SQL, place folder in htdocs, default accounts)
- [ ] SQL export of database with sample data
- [ ] ZIP packaging per assignment naming rules
- [ ] Harvard-format references updated with real accessed dates
