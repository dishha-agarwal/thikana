# Thikana 

Thikana is a trust-first PG and hostel discovery MVP for students. It helps students find transparent stays near college, lets owners submit properties with a simple form, supports direct WhatsApp contact, offers a practical compare page, and includes a lightweight roommate board.

## Tech Stack

- HTML5
- CSS3
- Vanilla JavaScript
- PHP
- MySQL
- XAMPP for local development

## Folder Structure

```text
thikana/
|-- index.php
|-- listings.php
|-- listing-details.php
|-- owner-list-property.php
|-- roommate-board.php
|-- compare.php
|-- submit-review.php
|-- submit-scam-report.php
|-- submit-roommate-post.php
|-- process-owner-listing.php
|-- README.md
|-- schema.sql
|-- includes/
|   |-- db.php
|   |-- header.php
|   `-- footer.php
|-- assets/
|   |-- css/style.css
|   |-- js/script.js
|   |-- images/
|   `-- icons/
|-- uploads/
|   |-- listings/
|   |-- reviews/
|   `-- reports/
`-- sample-data/
    `-- notes.txt
```

## Main Features

- Homepage with quick search, featured listings, trust sections, roommate preview, and review preview
- Listings page with MySQL-powered filters and sorting
- Listing details page with pricing transparency, rules, amenities, media, reviews, and scam report CTA
- Owner property submission form with image/video validation and pending approval flow
- Roommate board with 30-day post expiry
- Compare page for 2 or 3 approved listings
- Review submission with moderation note and proof upload
- Scam reporting form with optional screenshot upload
- Dark mode with `localStorage` persistence

## Default Database Credentials

These are set in [includes/db.php](/c:/xampp/htdocs/thikana/includes/db.php):

- Host: `localhost`
- Username: `root`
- Password: empty string
- Database: `thikana`

## How To Run On XAMPP

1. Copy the `thikana` folder into `C:\xampp\htdocs\`.
2. Open XAMPP Control Panel.
3. Start `Apache`.
4. Start `MySQL`.
5. Open `http://localhost/phpmyadmin/`.
6. Create a database named `thikana` if it does not already exist.
7. Click the `thikana` database in phpMyAdmin.
8. Import [schema.sql](/c:/xampp/htdocs/thikana/schema.sql).
9. Open `http://localhost/thikana/`.

## phpMyAdmin Import Steps

1. In phpMyAdmin, click `New`.
2. Enter database name: `thikana`.
3. Click `Create`.
4. Open the `thikana` database.
5. Click the `Import` tab.
6. Choose [schema.sql](/c:/xampp/htdocs/thikana/schema.sql).
7. Click `Go`.

`schema.sql` creates all required tables and inserts demo owners, listings, listing media, reviews, and roommate posts.

## Notes For Beginners

- Shared header and footer live in [includes/header.php](/c:/xampp/htdocs/thikana/includes/header.php) and [includes/footer.php](/c:/xampp/htdocs/thikana/includes/footer.php).
- Database connection and helper functions live in [includes/db.php](/c:/xampp/htdocs/thikana/includes/db.php).
- Main site styling lives in [assets/css/style.css](/c:/xampp/htdocs/thikana/assets/css/style.css).
- Dark mode and small UI behavior live in [assets/js/script.js](/c:/xampp/htdocs/thikana/assets/js/script.js).
- User-uploaded files are saved inside the `uploads/` folders.

## Product Logic Highlights

- Listings are not auto-verified. Verified badges are manual.
- New owner submissions are stored with `status = pending` and `verified = 0`.
- Reviews are saved with `approved = 0` until moderated.
- Roommate posts expire automatically after 30 days.
- Contact happens through prefilled `wa.me` links instead of in-app chat.

## Future Improvements

- Minimal admin moderation pages for approvals
- Better image compression for uploads
- Search by city and landmark
- Saved comparison links
- Owner edit flow for submitted listings
- Review moderation dashboard
- Basic fraud pattern tagging for internal team use
