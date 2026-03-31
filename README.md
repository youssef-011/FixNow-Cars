# FixNow Cars

FixNow Cars is a car service management website built with PHP and MySQL. Drivers can add their cars and request service, technicians can manage assigned jobs, and admins can keep the whole workflow organized from one place.

## Roles

- `user`
- `technician`
- `admin`

## Key Features

- registration and login with role-based access
- user profile and car management
- service request creation and tracking
- technician job acceptance and status updates
- admin service management and request monitoring
- reports and receipts
- admin-side technician account creation

## Project Structure

```text
FixNow Cars/
├── admin/
├── assets/
│   ├── css/
│   └── js/
├── config/
├── database/
├── docs/
├── includes/
├── technician/
├── user/
├── index.php
├── login.php
├── register.php
└── logout.php
```

- `config/db.php` handles the database connection.
- `includes/` contains shared layout, helper, and auth files.
- `user/`, `technician/`, and `admin/` contain the role pages.
- `assets/` stores the shared CSS and JavaScript files.
- `database/fixnow_cars.sql` contains the schema and seed data.
- `docs/` includes the project outline and testing checklist.

## Run Locally

1. Install and open XAMPP.
2. Start `Apache` and `MySQL`.
3. Copy the project folder to `C:\xampp\htdocs\Web-Programming`.
4. Open `http://localhost/phpmyadmin`.
5. Create a database named `fixnow_cars`.
6. Import `database/fixnow_cars.sql`.
7. Check `config/db.php` and confirm the host, username, password, and database name match your local setup.
8. Open `http://localhost/Web-Programming/`.

## Test Account

- Email: `admin@fixnow.com`
- Password: `password`

Technician accounts can be created from the admin panel on the `Technicians` page.

## Project Docs

- [Documentation Outline](docs/documentation_outline.md)
- [Testing Checklist](docs/testing_checklist.md)

## Notes

- Ratings are included in the database schema, but rating pages are not implemented yet.
- There is no password reset or email verification.
- The project uses plain PHP pages and `mysqli`.
