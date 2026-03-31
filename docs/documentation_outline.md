# FixNow Cars Documentation Outline

This outline can be used directly when preparing the final project report, Word document, or presentation notes.

## 1. Cover Page Content

- Project Title: `FixNow Cars`
- Course Name: `Web Programming`
- Project Type: `University Web Programming Project`
- Student Name: `________________`
- Student ID: `________________`
- Department / Level: `________________`
- Instructor Name: `________________`
- Submission Date: `________________`

## 2. Introduction

FixNow Cars is a web-based car service management system designed for a university web programming project. The website helps users request car services, allows technicians to manage assigned jobs, and enables admins to monitor the full workflow. The project uses PHP, MySQL, HTML, CSS, JavaScript, sessions, validation, and CRUD operations only.

## 3. Problem Definition

Traditional car service coordination can be unclear and difficult to manage when users, technicians, and administrators do not share one system. Users need a simple way to submit service requests. Technicians need a way to accept and update jobs. Admins need a way to monitor users, services, requests, reports, and receipts. The project solves this by organizing the workflow inside one website.

## 4. Project Functionality

The website provides:

- user registration and login
- role-based access control
- user profile management
- car management
- service request creation and tracking
- technician request acceptance and update flow
- admin monitoring and services management
- reports and receipts pages

## 5. Main Building Blocks

### Front-End

- HTML for structure
- CSS for styling and responsive layout
- basic JavaScript for navigation toggle and flash behavior

### Back-End

- PHP for page logic and form handling
- sessions for login state
- shared reusable includes
- `mysqli` prepared statements for database operations

### Database

- MySQL database with six main tables
- primary keys, foreign keys, and simple constraints

## 6. Website Actors

### Guest

- can open the homepage
- can register
- can log in

### User

- can manage personal profile
- can manage own cars
- can create and follow service requests

### Technician

- can update personal profile
- can accept available requests
- can update assigned jobs

### Admin

- can monitor all actors and requests
- can manage services
- can view reports and receipts

## 7. Database Functionalities

### `users`

Stores all accounts in the system with roles: `user`, `technician`, and `admin`.

### `cars`

Stores cars that belong to users.

### `services`

Stores the available service types and their base prices.

### `service_requests`

Stores the request workflow between users, technicians, and admin.

### `receipts`

Stores payment-related records for completed requests.

### `ratings`

Prepared in the schema for future course extension, but not yet implemented in the interface.

## 8. PHP Functionalities

The PHP part of the project includes:

- reusable layout includes
- shared helper functions
- safe session start and role checks
- form validation
- prepared statements
- CRUD forms and page-level workflows
- flash success and error messages
- ownership checks for protected records

## 9. ERD Description

The ERD can be explained as follows:

- One `user` can own many `cars`.
- One `user` can create many `service_requests`.
- One `technician` can be assigned to many `service_requests`.
- One `service` can appear in many `service_requests`.
- One `service_request` can have one `receipt`.
- One completed `service_request` may later have one `rating`.

This ERD is simple, relational, and suitable for explaining foreign key relationships in a university discussion.

## 10. Discussion Highlights

Useful points to discuss during presentation:

- Why role-based access control is important
- How ownership checks protect user and technician data
- Why prepared statements are used
- How the workflow moves from request creation to technician update to receipt generation
- How the database tables support the full system
- Why the project stays within course scope without frameworks

## 11. Known Limitations

- Ratings are not yet implemented in the pages
- Admin does not create technician accounts through the interface yet
- No password reset or email verification
- No advanced deployment or framework structure

## 12. Suggested Conclusion

FixNow Cars demonstrates the main topics of a web programming course in one clear project: authentication, sessions, validation, CRUD operations, role-based dashboards, database relationships, reporting, and simple workflow management.
