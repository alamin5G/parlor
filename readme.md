ECHO is on.

### Labonno Glamour World

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

| Column Name | Type                                    | Constraints               | Description           |
| ----------- | --------------------------------------- | ------------------------- | --------------------- |
| id          | INT                                     | PK, AUTO_INCREMENT        | User ID               |
| name        | VARCHAR(100)                            | NOT NULL                  | User’s full name     |
| email       | VARCHAR(100)                            | NOT NULL, UNIQUE          | User’s email         |
| password    | VARCHAR(255)                            | NOT NULL                  | Password (hashed)     |
| phone       | VARCHAR(20)                             |                           | Phone number          |
| role        | ENUM('customer', 'admin', 'beautician') | NOT NULL                  | User type/role        |
| created_at  | DATETIME                                | DEFAULT CURRENT_TIMESTAMP | Account creation date |

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
