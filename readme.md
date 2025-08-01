ECHO is on.

### Labonno Glamour World

A comprehensive parlor management system designed to streamline appointments, billing, and business analytics.

### Technical Stack

* **Backend:** PHP
* **Database:** MySQL / MariaDB
* **Frontend:** HTML, CSS, Bootstrap 5, JavaScript, jQuery, Chart.js
* **Dependencies:** PHPMailer, FPDF (managed via Composer)
* **Composer:** [https://getcomposer.org/download/](https://getcomposer.org/download/)

---

### Functional Requirements

**Parlor Management System – Functional Requirements**

1. **User Registration & Login**

   Users can sign up (email verification), log in, and manage their profiles.
2. **Appointment Management**

   Customers can book, reschedule, or cancel appointments.

   Admin can assign time slots to beauticians/stylists.
3. **Service Management**

   Customers can view available services with pricing (e.g., haircut, facial, bridal package, skincare, spa, nail).

   Admin can manage (add, edit, remove) the list of services.
4. **Notifications**

   The system sends **email reminders** to customers for appointments.
5. **Billing & Payments**

   The system generates a bill for each completed appointment.

   Customers can pay using **cash** at the parlor and receive a PDF receipt via email.

   Customers can pay using **bKash** (manual transaction):

   – Instructions and bKash payment number are shown during payment.

   – Customers must enter the bKash transaction ID when submitting payment.

   – Payment is marked as pending until **admin confirms** in the backend.

   – Upon admin approval, the customer receives a PDF receipt via email.
6. **Admin Dashboard**

   Admin can manage all services, employees (beauticians/stylists), and appointments.

   Admin can approve/confirm bKash payments.

   Admin can view reports, billing information, and all user activity.
7. **Employee/Beautician Dashboard**

   Beauticians/stylists can log in and view their assigned schedule/appointments.
8. **Ratings & Reviews**

   Customers can leave ratings and reviews for their appointments/services.

## **Checklist Version (for SRS, use as a table or bullet points)**

---

**Functional Requirements Checklist**

| #  | Requirement                                                                                  | Implemented | Notes |
| -- | -------------------------------------------------------------------------------------------- | ----------- | ----- |
| 1  | Users can sign up, log in, and manage their profiles                                         | [ ]         |       |
| 2  | Customers can book, reschedule, or cancel appointments                                       | [ ]         |       |
| 3  | Admin can assign time slots to beauticians/stylists                                          | [ ]         |       |
| 4  | Customers can view available services with pricing                                           | [ ]         |       |
| 5  | Admin can manage the list of services                                                        | [ ]         |       |
| 6  | System sends email reminders for appointments                                                | [ ]         |       |
| 7  | System generates a bill for each completed appointment                                       | [ ]         |       |
| 8  | Cash payments: customers receive PDF receipt by email                                        | [ ]         |       |
| 9  | bKash payments (manual): customers enter transaction ID, admin approves, PDF receipt emailed | [ ]         |       |
| 10 | Admin dashboard for services, employees, appointments, payment approval, reports             | [ ]         |       |
| 11 | Beauticians can view their assigned schedules/appointments                                   | [ ]         |       |
| 12 | Customers can leave ratings and reviews                                                      | [ ]         |       |

### Non-Functional Requirements

* User-friendly for admin and customer.
* Responsive (smartphones, tablets, PCs).
* Customer data privacy & secure login.
* 99% uptime.
* Fast booking/billing (under 3 seconds).
* Weekly auto-backups.

### Optional/Advanced Features

* Loyalty points for repeat customers.
* Online payment gateways (bKash, Nagad, etc.) [optional].
* Barcode-based check-in.
* Multi-branch support.

### **users**

| Column Name                    | Type                                    | Constraints                       | Description                            |
| :----------------------------- | :-------------------------------------- | :-------------------------------- | :------------------------------------- |
| id                             | INT                                     | PK, AUTO_INCREMENT                | User ID                                |
| name                           | VARCHAR(100)                            | NOT NULL                          | User’s full name                      |
| email                          | VARCHAR(100)                            | NOT NULL, UNIQUE                  | User’s email                          |
| password                       | VARCHAR(255)                            | NOT NULL                          | Password (hashed)                      |
| phone                          | VARCHAR(20)                             |                                   | Phone number                           |
| role                           | ENUM('customer', 'admin', 'beautician') | NOT NULL                          | User type/role                         |
| **`is_verified`**      | **`TINYINT(1)`**                | **`NOT NULL, DEFAULT 0`** | **`0=No, 1=Yes`**              |
| **`verify_token`**     | **`VARCHAR(255)`**              | **`NULL`**                | **`Email verification token`** |
| created_at                     | DATETIME                                | DEFAULT CURRENT_TIMESTAMP         | Account creation date                  |
| profile_update_code            | varchar(16)                             | NULL                              | OTP CODE                               |
| profile_update_code_expires_at | datetime                                | NULL                              | KEEP THE OTP TIME                      |

---

### **employees**

| Column Name    | Type                       | Constraints               | Description                        |
| -------------- | -------------------------- | ------------------------- | ---------------------------------- |
| id             | INT                        | PK, AUTO_INCREMENT        | Employee ID                        |
| user_id        | INT                        | NOT NULL, FK → users(id) | Linked user account                |
| specialization | VARCHAR(100)               |                           | Special skill (e.g., Hair, Makeup) |
| hire_date      | DATE                       |                           | Date hired                         |
| status         | ENUM('active', 'inactive') | DEFAULT 'active'          | Employment status                  |

---

### **services**

| Column Name  | Type          | Constraints        | Description            |
| ------------ | ------------- | ------------------ | ---------------------- |
| id           | INT           | PK, AUTO_INCREMENT | Service ID             |
| name         | VARCHAR(100)  | NOT NULL           | Service name           |
| description  | TEXT          |                    | Details of the service |
| price        | DECIMAL(10,2) | NOT NULL           | Service price          |
| duration_min | INT           | NOT NULL           | Duration (minutes)     |

---

### **appointments**

| Column Name  | Type                                                 | Constraints                   | Description                 |
| ------------ | ---------------------------------------------------- | ----------------------------- | --------------------------- |
| id           | INT                                                  | PK, AUTO_INCREMENT            | Appointment ID              |
| customer_id  | INT                                                  | NOT NULL, FK → users(id)     | Customer’s user ID         |
| employee_id  | INT                                                  | NOT NULL, FK → employees(id) | Assigned beautician/stylist |
| service_id   | INT                                                  | NOT NULL, FK → services(id)  | Booked service              |
| scheduled_at | DATETIME                                             | NOT NULL                      | Appointment date/time       |
| status       | ENUM('booked','cancelled','completed','rescheduled') | DEFAULT 'booked'              | Appointment status          |
| created_at   | DATETIME                                             | DEFAULT CURRENT_TIMESTAMP     | Booking creation date       |
| notes        | TEXT                                                 |                               | Optional notes              |

---

### **bills**

| Column Name    | Type          | Constraints                      | Description              |
| -------------- | ------------- | -------------------------------- | ------------------------ |
| id             | INT           | PK, AUTO_INCREMENT               | Bill ID                  |
| appointment_id | INT           | NOT NULL, FK → appointments(id) | Linked appointment       |
| amount         | DECIMAL(10,2) | NOT NULL                         | Total amount billed      |
| payment_mode   | ENUM('cash')  | NOT NULL, DEFAULT 'cash'         | Payment type (cash only) |
| payment_time   | DATETIME      | DEFAULT CURRENT_TIMESTAMP        | Payment date/time        |

---

### **receipts**

| Column Name | Type     | Constraints               | Description              |
| ----------- | -------- | ------------------------- | ------------------------ |
| id          | INT      | PK, AUTO_INCREMENT        | Receipt ID               |
| bill_id     | INT      | NOT NULL, FK → bills(id) | Linked bill              |
| issued_at   | DATETIME | DEFAULT CURRENT_TIMESTAMP | Receipt issued date/time |

---

### **reviews**

| Column Name    | Type             | Constraints                      | Description          |
| -------------- | ---------------- | -------------------------------- | -------------------- |
| id             | INT              | PK, AUTO_INCREMENT               | Review ID            |
| customer_id    | INT              | NOT NULL, FK → users(id)        | Review by (customer) |
| appointment_id | INT              | NOT NULL, FK → appointments(id) | Linked appointment   |
| rating         | TINYINT UNSIGNED | NOT NULL, 1-5                    | Star rating          |
| comments       | TEXT             |                                  | Review text/comments |
| created_at     | DATETIME         | DEFAULT CURRENT_TIMESTAMP        | Review date/time     |

---

### **appointment_reminders**

| Column Name    | Type                  | Constraints                      | Description        |
| -------------- | --------------------- | -------------------------------- | ------------------ |
| id             | INT                   | PK, AUTO_INCREMENT               | Reminder ID        |
| appointment_id | INT                   | NOT NULL, FK → appointments(id) | Linked appointment |
| sent_at        | DATETIME              | DEFAULT CURRENT_TIMESTAMP        | Time sent          |
| status         | ENUM('sent','failed') | DEFAULT 'sent'                   | Email status       |

---

### **employee_services**

| Column Name             | Type | Constraints                      | Description         |
| ----------------------- | ---- | -------------------------------- | ------------------- |
| id                      | INT  | PK, AUTO_INCREMENT               | Row ID              |
| employee_id             | INT  | NOT NULL, FK → employees(id)    | Employee            |
| service_id              | INT  | NOT NULL, FK → services(id)     | Service assigned    |
| unique_employee_service |      | UNIQUE (employee_id, service_id) | Ensures unique pair |

**users**

Holds all user accounts, including customers, admins, and beauticians/stylists. Stores credentials, contact details, and user role for login and access control.

**employees**

Contains additional details about beauticians/stylists who work at the parlor. Linked to the users table, it tracks specialization, hire date, and employment status.

**services**

Lists all the parlor services offered (e.g., haircut, facial), including names, descriptions, prices, and durations. Used for service selection during booking.

**appointments**

Manages appointment bookings. Each record connects a customer, an employee, and a service with a specific date/time, status (booked, completed, etc.), and any extra notes.

**bills**

Records billing information for completed appointments. Tracks total amount charged, payment mode (cash), and payment time.

**receipts**

Generates and stores receipts for each bill, confirming that payment was received. Linked to the bill and records issuance time.

**reviews**

Stores feedback from customers about their appointments. Each review is connected to a customer and appointment, with a rating (1–5 stars) and optional comments.

**appointment_reminders**

Logs email reminders sent to customers for their appointments, including send status and timestamp. Useful for tracking notification delivery.

**employee_services**

Maps which services each employee can perform (many-to-many relationship). Ensures customers are only assigned to employees qualified for the selected service.



## Testing Plan

This testing plan ensures all critical system components work correctly. Follow these steps to verify system functionality.

### 1. Admin Setup Testing

#### 1.1 Employee Management

- **Create 5 employees with the following details:**

  | Name          | Email              | Phone       | Specialization  |
  | ------------- | ------------------ | ----------- | --------------- |
  | Nadia Ahmed   | nadia@labonno.com  | 01712345601 | Hair Styling    |
  | Farida Rahman | farida@labonno.com | 01712345602 | Makeup Artist   |
  | Tania Islam   | tania@labonno.com  | 01712345603 | Nail Technician |
  | Rima Khan     | rima@labonno.com   | 01712345604 | Skin Care       |
  | Sadia Jahan   | sadia@labonno.com  | 01712345605 | Spa & Massage   |
- **For each employee, verify:**

  - Profile creation success
  - Ability to login with provided credentials
  - Profile details display correctly in admin dashboard
  - Employee appears in appointment assignment dropdown

#### 1.2 Service Management

- **Create 10 services with the following details:**

  | Service Name         | Description                          | Price (Tk) | Duration (min) |
  | -------------------- | ------------------------------------ | ---------- | -------------- |
  | Basic Haircut        | Simple haircut and styling           | 500        | 30             |
  | Premium Hair Styling | Cut, color and style                 | 1500       | 90             |
  | Regular Manicure     | Basic nail care and polish           | 300        | 30             |
  | Gel Nail Extension   | Long-lasting gel nail extensions     | 800        | 60             |
  | Basic Facial         | Cleansing and moisturizing facial    | 700        | 45             |
  | Premium Facial       | Deep cleansing with special mask     | 1200       | 60             |
  | Bridal Makeup        | Complete bridal makeup package       | 5000       | 120            |
  | Party Makeup         | Evening/party makeup application     | 2000       | 60             |
  | Full Body Massage    | Relaxing full body massage           | 1500       | 60             |
  | Hair Spa Treatment   | Deep conditioning and hair treatment | 1000       | 45             |
- **For each service, verify:**

  - Service appears in the services list
  - Details (price, duration) are correctly saved
  - Service can be edited and updated
  - Service appears in appointment booking options

### 2. Customer Appointment Flow Testing

#### 2.1 User Registration & Login

- Create 3 test customer accounts
- Verify email verification process
- Test login functionality
- Test profile management options

#### 2.2 Appointment Booking

- **Book 5 appointments with different combinations:**

  - Different services
  - Different employees
  - Different time slots
  - Different payment methods (both cash and online)
- **For each appointment, verify:**

  - Appointment appears in customer's dashboard
  - Appointment appears in admin dashboard
  - Appointment appears in assigned employee's dashboard
  - Confirmation email is sent to customer

#### 2.3 Appointment Rescheduling

- Reschedule 2 appointments
- Verify updated time reflects across all dashboards
- Check notification/email for rescheduled appointment

#### 2.4 Appointment Cancellation

- Cancel 1 appointment
- Verify removal from dashboards
- Check notification/email for cancellation

### 3. Payment Processing Testing

#### 3.1 Cash Payment Testing

- **Process 2 cash payments:**
  - Admin marks appointment as completed
  - Generate bill for completed appointment
  - Record cash payment
  - Verify receipt generation

#### 3.2 Online Payment Testing

- **Process 3 online payments:**

  | Method | Transaction ID | Amount |
  | ------ | -------------- | ------ |
  | bKash  | BK7856943215   | 1200   |
  | Nagad  | NG1209876543   | 2000   |
  | Rocket | RK3245678901   | 500    |


  - Customer submits online payment details
  - Admin reviews and approves payment
  - Verify status change to "approved"
  - Check receipt generation and delivery

### 4. Employee Workflow Testing

#### 4.1 Employee Dashboard

- Log in as each employee
- Verify correct appointments are displayed
- Test calendar view functionality
- Test list view functionality

#### 4.2 Appointment Management

- Employee marks appointments as "completed"
- Verify status change reflects in admin dashboard
- Verify bill generation option becomes available

### 5. Review & Rating Testing

#### 5.1 Customer Reviews

- Submit reviews for 3 completed appointments
- Test different ratings (1-5 stars)
- Add comments with the reviews

#### 5.2 Review Display

- Verify reviews appear on employee profiles
- Check review summaries in admin dashboard
- Test average rating calculations

### 6. Reporting Testing

#### 6.1 Admin Reports

- Generate and verify accuracy of:
  - Daily revenue report
  - Service popularity report
  - Employee performance report
  - Payment method report

#### 6.2 Data Export

- Test CSV/PDF export functionality
- Verify exported data matches dashboard information

### 7. Notification System Testing

#### 7.1 Email Notifications

- Verify appointment confirmation emails
- Test reminder emails (10min, 30min, day before)
- Check payment confirmation emails
- Verify receipt delivery emails

---

## Testing Checklist

| Test Area                 | Status | Notes |
| ------------------------- | ------ | ----- |
| Admin login               |        |       |
| Employee creation         |        |       |
| Service creation          |        |       |
| Customer registration     |        |       |
| Appointment booking       |        |       |
| Appointment rescheduling  |        |       |
| Appointment cancellation  |        |       |
| Cash payment processing   |        |       |
| Online payment submission |        |       |
| Payment verification      |        |       |
| Employee dashboard        |        |       |
| Service completion        |        |       |
| Bill generation           |        |       |
| Receipt delivery          |        |       |
| Customer reviews          |        |       |
| Reports generation        |        |       |
| Email notifications       |        |       |

## Test Accounts

### Admin Account

- **Email:** admin@labonno.com
- **Password:** admin123

### Test Customer Accounts

- **Customer 1:** customer1@test.com / pass123
- **Customer 2:** customer2@test.com / pass123
- **Customer 3:** customer3@test.com / pass123

### Employee Test Accounts

- Use the employee emails created during testing with default password: employee123

## **Checklist Version (for SRS, use as a table or bullet points)**

---

| #  | Requirement                                                                                  | Status         | Notes                                                                                             |
| -- | -------------------------------------------------------------------------------------------- | -------------- | ------------------------------------------------------------------------------------------------- |
| 1  | Users can sign up, log in, and manage their profiles                                         | ✔️ Done      | Includes email verification, profile editing, and secure password changes.                        |
| 2  | Customers can book, reschedule, or cancel appointments                                       | ○ To Do       | The customer-facing interface for appointment management is not yet built.                        |
| 3  | Admin can assign time slots to beauticians/stylists                                          | ✔️ Done      | Implemented as part of the "Create Appointment" feature in the admin panel.                       |
| 4  | Customers can view available services with pricing                                           | ○ To Do       | The public-facing services page is not yet built.                                                 |
| 5  | Admin can manage the list of services (CRUD)                                                 | ✔️ Done      | Admin can add, edit, and delete services. Includes a safety check for deleting used services.     |
| 6  | System sends email reminders for appointments                                                | ○ To Do       | The database table and email function exist, but the trigger/cron job has not been implemented.   |
| 7  | System generates a bill for each completed appointment                                       | ✔️ Done      | Bills are generated from completed appointments and can be viewed in a dedicated section.         |
| 8  | Cash payments: customers receive PDF receipt by email                                        | ◐ In Progress | A printable PDF invoice is generated, but the functionality to email it automatically is pending. |
| 9  | bKash payments (manual): customers enter transaction ID, admin approves, PDF receipt emailed | ○ To Do       | This payment method has not been implemented yet.                                                 |
| 10 | Admin dashboard for services, employees, appointments, payment approval, reports             | ✔️ Done      | A comprehensive dashboard with charts, stats, and activity feed is complete.                      |
| 11 | Beauticians can view their assigned schedules/appointments                                   | ✔️ Done      | Complete employee panel with dashboard, list/calendar views, and status management.               |
| 12 | Customers can leave ratings and reviews                                                      | ◐ In Progress | Employees can view their ratings and reviews. Customer-side submission is pending.                |
