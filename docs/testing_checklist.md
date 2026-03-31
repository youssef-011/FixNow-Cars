# FixNow Cars Testing Checklist

Use this checklist during final testing, screenshots, and project discussion.

## 1. Environment Check

- [ ] Apache starts in XAMPP
- [ ] MySQL starts in XAMPP
- [ ] `fixnow_cars` database exists
- [ ] `database/fixnow_cars.sql` was imported successfully
- [ ] Homepage opens at `http://localhost/Web-Programming/`

## 2. Public Pages

- [ ] Homepage opens correctly
- [ ] Navbar shows `Home`, `Login`, and `Register` for guests
- [ ] Login page opens
- [ ] Register page opens

## 3. Registration and Login

- [ ] User can register with valid data
- [ ] Registration blocks empty required fields
- [ ] Registration blocks duplicate email
- [ ] Registration blocks password mismatch
- [ ] User can log in with correct credentials
- [ ] Login rejects wrong email or password
- [ ] Logout returns the user to the homepage

## 4. User Module

### Profile

- [ ] User dashboard opens after login
- [ ] User profile page shows saved data
- [ ] User can update name, phone, and address

### Cars

- [ ] User can open `My Cars`
- [ ] User can add a car
- [ ] User can edit a saved car
- [ ] User can delete a car when allowed
- [ ] User cannot edit another user's car

### Requests

- [ ] User can open `Request Service`
- [ ] User sees a clear message if no cars exist
- [ ] User can create a valid request
- [ ] New request is saved with `pending` status
- [ ] User can view own requests
- [ ] User can filter requests by status
- [ ] User can open request details
- [ ] User can cancel only pending unassigned requests
- [ ] User cannot view another user's request

## 5. Technician Module

### Profile

- [ ] Technician dashboard opens after login
- [ ] Technician can update profile information

### Available Requests

- [ ] Technician can view pending unassigned requests
- [ ] Technician can accept a request
- [ ] Accepted request moves to `My Jobs`

### My Jobs

- [ ] Technician can view assigned jobs only
- [ ] Technician can filter jobs by status
- [ ] Technician can open update page
- [ ] Technician can update status
- [ ] Technician can update estimated price
- [ ] Technician can update final price
- [ ] Technician can update technician notes
- [ ] Technician cannot update a request not assigned to him

## 6. Admin Module

### Dashboard and Monitoring

- [ ] Admin dashboard opens after login
- [ ] Admin can open users page
- [ ] Admin can open technicians page
- [ ] Admin can open services page
- [ ] Admin can open requests page
- [ ] Admin can open reports page
- [ ] Admin can open receipts page

### Users and Technicians

- [ ] Admin can search users
- [ ] Admin can search technicians

### Services

- [ ] Admin can add a service
- [ ] Admin can edit a service
- [ ] Admin can delete a service when not linked to requests
- [ ] Admin is blocked from deleting a service linked to requests

### Requests

- [ ] Admin can filter requests by status
- [ ] Admin can search requests
- [ ] Admin can open request details

### Reports and Receipts

- [ ] Reports page shows completed requests count
- [ ] Reports page shows cancelled requests count
- [ ] Reports page shows total revenue
- [ ] Reports page shows most requested services
- [ ] Receipts page lists saved receipts
- [ ] Receipt can be generated for eligible completed request
- [ ] Duplicate receipts are prevented

## 7. Authorization Checks

- [ ] Guest cannot open user pages directly
- [ ] Guest cannot open technician pages directly
- [ ] Guest cannot open admin pages directly
- [ ] Logged-in user cannot open technician pages
- [ ] Logged-in user cannot open admin pages
- [ ] Logged-in technician cannot open user pages not meant for technicians
- [ ] Logged-in technician cannot open admin pages
- [ ] Logged-in admin cannot access protected pages incorrectly without proper role flow

## 8. UI and Navigation Check

- [ ] Page headings are clear and consistent
- [ ] Navbar updates correctly by role
- [ ] Status badges appear correctly
- [ ] Tables are readable
- [ ] Empty states display correctly
- [ ] Success and error messages display clearly
- [ ] Buttons and forms are consistent

## 9. Seeded Admin Test

- [ ] Login with seeded admin works
- [ ] Email: `admin@fixnow.com`
- [ ] Password: `password`

## 10. Known Limitations Confirmed

- [ ] Ratings are not implemented in the current interface
- [ ] No password reset
- [ ] No email verification
- [ ] Project stays within plain PHP/MySQL course scope
