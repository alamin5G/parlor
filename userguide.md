# 🐾 PetCareHub - Complete User Guide

**Version:** 2.0  
**Last Updated:** September 2025  
**System:** Pet Care Management System with MySQL Database

---

## 📚 Table of Contents

1. [System Overview](#-system-overview)
2. [Getting Started](#-getting-started)
3. [🔄 System Setup Workflow (WHO FIRST?)](#-system-setup-workflow-who-first)
4. [User Types & Access Levels](#-user-types--access-levels)
5. [Employee Login & Features](#-employee-login--features)
6. [Customer Login & Features](#-customer-login--features)
7. [Module-by-Module Guide](#-module-by-module-guide)
8. [Troubleshooting](#-troubleshooting)
9. [FAQ](#-frequently-asked-questions)

---

## 🎯 System Overview

**PetCareHub** is a comprehensive pet care management system that provides:

- **Pet Medical Services** - Veterinary care and medical records
- **Pet Hotel Services** - Pet boarding and accommodation
- **Salon Services** - Pet grooming and beauty treatments
- **Customer Management** - Pet owner information and pet records
- **Employee Management** - Staff administration and role management

### Key Features:

✅ Multi-user system (Employees & Customers)  
✅ Role-based access control  
✅ Real-time medical records  
✅ Hotel booking system  
✅ Salon service management  
✅ Comprehensive reporting

---

## 🚀 Getting Started

### System Requirements:

- Web browser (Chrome, Firefox, Safari, Edge)
- Internet connection
- Valid login credentials

### Access URLs:

- **Main Application:** `http://localhost/pemay/`
- **Employee Login:** `http://localhost/pemay/auth/login.php`
- **Customer Login:** `http://localhost/pemay/auth/customer-login.php`
- **Registration:** `http://localhost/pemay/auth/register.php`

---

## � System Setup Workflow (WHO FIRST?)

> **📝 This section explains the COMPLETE WORKFLOW from system startup to daily operations - WHO logs in first, WHAT data they need to provide, and HOW the system flows.**

### **🎯 STEP-BY-STEP WORKFLOW PROCESS:**

---

### **PHASE 1: SYSTEM INITIALIZATION** 🏗️

#### **👨‍💼 STEP 1: ADMIN MUST LOGIN FIRST**

**WHO:** System Administrator  
**URL:** `http://localhost/pemay/auth/login.php`  
**LOGIN CREDENTIALS:**

- Username: `admin` (default)
- Password: `admin123` (or assigned password)
- Role: **Admin**
- CAPTCHA: Complete verification

**WHY FIRST?** Admin has full system access and must set up the foundation data.

---

#### **🏢 STEP 2: ADMIN SETS UP BASIC SYSTEM DATA**

##### **A. Setup Employee Accounts** 👥

**LOCATION:** Employee Management → Add New Employee  
**URL:** `http://localhost/pemay/pages/owner/users.php`

**DATA TO INPUT:**

```
👨‍⚕️ DOCTOR ACCOUNT:
- Name: "Dr. John Smith"
- Username: "doctor1"
- Password: "secure123"
- Position: "Doctor"
- Email: "doctor@petcare.com"
- Phone: "+1234567890"

👨‍💼 STAFF ACCOUNT:
- Name: "Jane Wilson"
- Username: "staff1"
- Password: "staff123"
- Position: "Staff"
- Email: "staff@petcare.com"
- Phone: "+1234567891"

🐕 VET ACCOUNT:
- Name: "Dr. Sarah Johnson"
- Username: "vet1"
- Password: "vet123"
- Position: "Vet"
- Email: "vet@petcare.com"
- Phone: "+1234567892"
```

##### **B. Setup Cage/Room System** 🏠

**LOCATION:** Pet Hotel Dashboard → Cage Tab  
**URL:** `http://localhost/pemay/pages/pet-hotel/dashboard.php`

**DATA TO INPUT:**

```
CAGE 1: Size = "Small" (for cats, small dogs)
CAGE 2: Size = "Medium" (for medium dogs)
CAGE 3: Size = "Large" (for large dogs)
CAGE 4: Size = "Extra Large" (for very large pets)
```

**HOW TO ADD:**

1. Click **"Cage"** tab
2. Enter cage **"Size"**
3. Click **"Add Item"**
4. Repeat for all needed cages

---

### **PHASE 2: CUSTOMER REGISTRATION & DATA ENTRY** 👨‍👩‍👧‍👦

#### **🙋‍♀️ STEP 3: CUSTOMERS REGISTER THEMSELVES**

**WHO:** Pet Owners (Customers)  
**URL:** `http://localhost/pemay/auth/register.php`

**CUSTOMER REGISTRATION DATA:**

```
EXAMPLE CUSTOMER 1:
- Full Name: "Alice Johnson"
- Username: "alice2025"
- Email: "alice@email.com"
- Password: "alice123"
- Phone: "+1555123456"
- Address: "123 Pet Street, City, State"
- Date of Birth: "1990-05-15"
- Emergency Contact: "+1555987654"
```

**PROCESS:**

1. Customer fills registration form
2. Completes CAPTCHA
3. Clicks **"Register"**
4. Account automatically created in system

#### **🐕 STEP 4: CUSTOMERS LOGIN & ADD THEIR PETS**

**WHO:** Registered Customers  
**URL:** `http://localhost/pemay/auth/customer-login.php`

**LOGIN PROCESS:**

1. Enter username/email + password
2. Complete CAPTCHA
3. Click **"Login"**
4. Redirected to Pet Information page

**PET REGISTRATION DATA:**

```
CUSTOMER'S PET EXAMPLE:
- Pet Name: "Buddy"
- Animal Type: "Dog"
- Age: "3 years"
- Weight: "25.5 kg"
- Notes: "Friendly, loves treats, scared of loud noises"
```

**HOW TO ADD PET:**

1. Go to **"My Pets"** section
2. Click **"+"** button (bottom right)
3. Fill pet information
4. Click **"Save Pet"**

---

### **PHASE 3: DAILY OPERATIONS WORKFLOW** ⚡

#### **🏥 STEP 5: MEDICAL SERVICES WORKFLOW**

##### **WHO LOGS IN:** Doctor/Vet

**URL:** `http://localhost/pemay/auth/login.php`  
**CREDENTIALS:** doctor1/vet1 accounts created in Step 2

##### **MEDICAL SERVICE PROCESS:**

**LOCATION:** `http://localhost/pemay/pages/pet-medical/dashboard.php`

1. **Doctor/Vet selects patient:**

   - Choose from registered pets dropdown
   - View pet's medical history

2. **Input medical data:**

   ```
   MEDICAL RECORD EXAMPLE:
   - Diagnosis: "Ear infection"
   - Treatment: "Cleaning + antibiotic drops"
   - Medications: "Otomax drops, 3 drops twice daily"
   - Notes: "Follow up in 1 week"
   - Date: Auto-filled
   ```

3. **Add medications (if needed):**
   - Switch to **"Medication"** tab
   - Add prescribed medicines
   - Set dosage and frequency

##### **WHO RECEIVES DATA:**

- **Pet Owner** (can view through future updates)
- **Other medical staff** (for continuity of care)
- **Admin** (for reporting and oversight)

---

#### **🏨 STEP 6: HOTEL SERVICES WORKFLOW**

##### **WHO LOGS IN:** Staff/Admin

**URL:** Pet Hotel Dashboard

##### **HOTEL BOOKING PROCESS:**

**CUSTOMER REQUEST:** "I want to book my pet 'Buddy' for 3 days"

**STAFF PROCESS:**

1. **Select Reservation Details:**

   ```
   RESERVATION EXAMPLE:
   - Reservator: "Pet: Buddy | Owner: Alice Johnson"
   - Check-In: "2025-09-15 10:00 AM"
   - Check-Out: "2025-09-18 10:00 AM"
   - Room: "No: 2 | Size: Medium"
   - Price: Auto-calculated (e.g., $45/day × 3 days = $135)
   ```

2. **Submit Reservation:** Click **"Add Item"**

##### **WHO RECEIVES DATA:**

- **Customer** (receives confirmation)
- **Hotel Staff** (manages daily care)
- **Admin** (tracks occupancy and revenue)

---

#### **💅 STEP 7: SALON SERVICES WORKFLOW**

##### **WHO LOGS IN:** Vet/Doctor/Admin

**URL:** Salon Services

##### **GROOMING SERVICE PROCESS:**

**CUSTOMER REQUEST:** "I need grooming for my pet"

**STAFF PROCESS:**

1. **Book Service:**
   ```
   SALON BOOKING EXAMPLE:
   - Pet: "Buddy"
   - Service: "Full Grooming Package"
   - Date: "2025-09-20"
   - Time: "2:00 PM"
   - Special Notes: "Sensitive skin, use gentle products"
   ```

##### **WHO RECEIVES DATA:**

- **Customer** (service confirmation)
- **Groomer** (service instructions)
- **Admin** (scheduling and revenue tracking)

---

### **📊 STEP 8: DATA FLOW SUMMARY**

#### **WHO PROVIDES WHAT DATA:**

| **USER TYPE**     | **DATA THEY INPUT**                      | **WHERE THEY INPUT IT**      |
| ----------------- | ---------------------------------------- | ---------------------------- |
| **👨‍💼 Admin**      | Employee accounts, System settings       | Employee Management          |
| **👨‍⚕️ Doctor/Vet** | Medical records, Treatments, Medications | Medical Dashboard            |
| **👨‍💼 Staff**      | Hotel bookings, Customer service         | Hotel Dashboard              |
| **🙋‍♀️ Customer**   | Personal info, Pet information           | Registration + Pet Info Page |

#### **WHO RECEIVES WHAT DATA:**

| **DATA TYPE**          | **WHO GETS IT**             | **WHERE THEY SEE IT** |
| ---------------------- | --------------------------- | --------------------- |
| **📋 Medical Records** | Doctor, Vet, Admin          | Medical Dashboard     |
| **🏨 Hotel Bookings**  | Staff, Admin, Customer      | Hotel Dashboard       |
| **💅 Salon Bookings**  | Groomer, Admin, Customer    | Salon Services        |
| **🐕 Pet Information** | Owner, Medical Staff, Admin | Pet Management        |

---

### **🔄 DAILY WORKFLOW EXAMPLE:**

#### **MONDAY MORNING WORKFLOW:**

```
08:00 AM - 👨‍💼 Staff logs in → Checks hotel reservations
08:30 AM - 🙋‍♀️ Customer calls → Books hotel for pet "Buddy"
09:00 AM - 👨‍💼 Staff → Creates hotel reservation in system
10:00 AM - 🐕 Pet "Buddy" → Arrives for check-in
10:30 AM - 👨‍⚕️ Doctor → Examines pet, updates medical record
11:00 AM - 👨‍💼 Staff → Assigns pet to cage, updates status
12:00 PM - 👨‍💼 Admin → Reviews daily reports and bookings
```

#### **WORKFLOW DEPENDENCIES:**

```
1. Admin MUST create employee accounts FIRST
2. Employees MUST setup cages/rooms BEFORE bookings
3. Customers MUST register BEFORE pets can be added
4. Pets MUST be registered BEFORE services can be provided
5. All users MUST have proper login credentials
```

---

## �👥 User Types & Access Levels

### **1. Employees** 👨‍💼

| Role           | Access Level      | Permissions                                   |
| -------------- | ----------------- | --------------------------------------------- |
| **Admin**      | Full Access       | All modules, user management, system settings |
| **Doctor/Vet** | Medical + General | Medical services, salon services, pet records |
| **Staff**      | Limited           | Basic operations, customer service            |

### **2. Customers** 👨‍👩‍👧‍👦

- **Pet Owners** - Manage own pets, view services, book appointments

---

## 🏢 Employee Login & Features

### **Login Process:**

1. Go to: `http://localhost/pemay/auth/login.php`
2. Enter your **Username** and **Password**
3. Select your **Role** from dropdown (Admin/Doctor/Staff/Vet)
4. Complete **CAPTCHA** verification
5. Click **"Login"**

### **Employee Dashboard Navigation:**

#### **🏠 Main Navigation Menu:**

- **PetCareHub Logo** - Return to dashboard
- **Managements** dropdown:
  - Pet Medical Dashboard
  - Pet Hotel Dashboard
  - Employee Management
  - Pet Owner Management
- **Pet Services** dropdown:
  - Salon Services
  - Medical Services
- **Profile & Logout** (top-right)

---

## 🐕 Customer Login & Features

### **Registration Process:**

1. Go to: `http://localhost/pemay/auth/register.php`
2. Fill in all required fields:
   - **Full Name**
   - **Username** (unique)
   - **Email** (unique)
   - **Password** (secure)
   - **Phone Number**
   - **Address**
   - **Date of Birth**
   - **Emergency Contact**
3. Complete **CAPTCHA**
4. Click **"Register"**
5. Account created successfully!

### **Customer Login Process:**

1. Go to: `http://localhost/pemay/auth/customer-login.php`
2. Enter **Username/Email** and **Password**
3. Complete **CAPTCHA**
4. Click **"Login"**

### **Customer Dashboard:**

- **My Profile** - View personal information
- **My Pets** - Manage pet information
- **Service History** - View past services
- **Appointments** - Schedule and manage appointments

---

## 📋 Module-by-Module Guide

### **🏥 1. Pet Medical Dashboard**

**URL:** `http://localhost/pemay/pages/pet-medical/dashboard.php`  
**Access:** Admin, Doctor, Vet

#### **Features:**

- **Medical Records Management**
- **Medication Tracking**
- **Treatment History**
- **Veterinary Notes**

#### **How to Use:**

1. Navigate to Medical Dashboard
2. Select **"Medical Services"** tab
3. Choose patient from dropdown
4. Fill in medical information:
   - **Diagnosis**
   - **Treatment**
   - **Medications**
   - **Notes**
5. Click **"Save Medical Record"**

#### **Medication Tab:**

1. Switch to **"Medication"** tab
2. Add medications:
   - **Medicine Name**
   - **Dosage**
   - **Frequency**
   - **Duration**
3. Click **"Add Medication"**

---

### **🏨 2. Pet Hotel Dashboard**

**URL:** `http://localhost/pemay/pages/pet-hotel/dashboard.php`  
**Access:** Admin, Staff, Doctor

#### **Two Main Sections:**

##### **A. Pet Hotel Reservations Tab**

**Creating a Reservation:**

1. Select **"Pet Hotel"** tab
2. Fill in **"Pet Check-In Information"**:
   - **Reservator Name** - Choose pet and owner from dropdown
   - **Check-In Date** - Select check-in date
   - **Check-Out Date** - Select check-out date
   - **Room No.** - Choose available cage/room
3. **Room Price** auto-calculates
4. Click **"Add Item"**

**Managing Reservations:**

- View all reservations in table below
- **Edit** - Update reservation details
- **Delete** - Cancel reservation
- **Status Tracking** - Scheduled → In Progress → Completed

##### **B. Cage Management Tab**

**Adding New Cages:**

1. Select **"Cage"** tab
2. Fill in **"Pet Cage Information"**:
   - **Size** - Enter cage size (Small/Medium/Large or dimensions)
3. Click **"Add Item"**

**Managing Cages:**

- View all cages in **"Listed Cages"** table
- **Status Types**: Empty, Filled, Scheduled
- **Delete** cages (only if Empty status)

---

### **💅 3. Salon Services**

**URL:** `http://localhost/pemay/pages/salon/salon-services.php`  
**Access:** Admin, Vet, Doctor

#### **Available Services:**

- **Grooming Services**
- **Bathing & Cleaning**
- **Nail Trimming**
- **Styling & Cuts**

#### **Booking Process:**

1. Navigate to Salon Services
2. Select **Service Type**
3. Choose **Pet** from registered pets
4. Select **Date & Time**
5. Add **Special Instructions**
6. Click **"Book Service"**

---

### **👥 4. Employee Management**

**URL:** `http://localhost/pemay/pages/owner/users.php`  
**Access:** Admin Only

#### **Managing Staff:**

1. **Add New Employee:**
   - Name, Username, Password
   - Position (Admin/Doctor/Staff/Vet)
   - Contact Information
2. **Edit Employee:** Update details
3. **Delete Employee:** Remove from system
4. **View Employee List:** All staff members

---

### **🐾 5. Pet Owner Management**

**Access:** Admin, Staff

#### **Customer Information:**

- View all registered pet owners
- Contact details and emergency contacts
- Pet ownership records
- Service history

---

### **🏠 6. Customer Pet Information Page**

**URL:** `http://localhost/pemay/pages/customer/pet-information.php`  
**Access:** Customers Only

#### **Features:**

1. **Personal Profile Section:**
   - Name, Email, Phone
   - Address information
2. **My Pets Section:**
   - View all registered pets
   - Pet details (Name, Type, Age, Weight)
   - Add new pets
   - Edit pet information

#### **Adding a New Pet:**

1. Click **"+"** button (bottom right)
2. Fill in pet details:
   - **Pet Name**
   - **Animal Type**
   - **Age**
   - **Weight**
   - **Notes**
3. Click **"Save Pet"**

---

## 🔧 Troubleshooting

### **Common Issues:**

#### **1. Login Problems**

- **Issue:** "Invalid username or password"
- **Solution:**
  - Check username/email spelling
  - Verify password case-sensitivity
  - Ensure correct user type (Employee vs Customer)
  - Try password reset if available

#### **2. CAPTCHA Issues**

- **Issue:** "Invalid CAPTCHA"
- **Solution:**
  - Refresh the page for new CAPTCHA
  - Enter CAPTCHA exactly as shown
  - Check for case sensitivity

#### **3. Database Connection Errors**

- **Issue:** "Database connection failed"
- **Solution:**
  - Check if XAMPP/server is running
  - Verify database configuration
  - Contact system administrator

#### **4. Access Denied Errors**

- **Issue:** "Access denied" or "Insufficient permissions"
- **Solution:**
  - Verify you're logged in with correct role
  - Check if your account has required permissions
  - Contact administrator for role updates

#### **5. Form Submission Issues**

- **Issue:** Forms not submitting or showing errors
- **Solution:**
  - Fill all required fields (marked with \*)
  - Check for valid email format
  - Ensure unique usernames/emails
  - Verify date formats

---

## ❓ Frequently Asked Questions

### **General Questions:**

**Q: How do I reset my password?**
A: Currently, contact your system administrator for password reset. Feature may be added in future updates.

**Q: Can customers book services directly?**
A: Currently, customers can view their pets but service booking is handled by employees. Direct booking feature planned for future updates.

**Q: How do I update my profile information?**
A: Customers can view but not edit profile info. Contact staff for updates. Self-editing feature planned for future.

### **Employee Questions:**

**Q: What's the difference between Admin and Doctor roles?**
A:

- **Admin**: Full system access, user management, all modules
- **Doctor/Vet**: Medical services, salon services, pet records
- **Staff**: Basic operations, limited access

**Q: How do I add a new pet owner to the system?**
A: Navigate to Pet Owner Management and use the "Add New Owner" function, or owners can self-register.

**Q: Can I edit medical records after saving?**
A: Yes, medical records can typically be edited by medical staff (Doctor/Vet/Admin).

### **Customer Questions:**

**Q: How do I add a new pet?**
A: Go to your pet information page and click the "+" button to add a new pet with all required details.

**Q: Can I see my pet's medical history?**
A: Currently, medical history is managed by veterinary staff. Future updates may include customer access to medical records.

**Q: How do I schedule a grooming appointment?**
A: Contact the salon staff or visit the facility to schedule appointments. Online booking may be added in future updates.

---

## 🛠️ System Administration

### **Database Management:**

- **Location:** MySQL database
- **Tables:** Employees, PetOwners, Animals, Cages, HotelServices, etc.
- **Backup:** Regular database backups recommended

### **User Management:**

- **Employee Accounts:** Created by Admin users
- **Customer Accounts:** Self-registration available
- **Role Assignment:** Admin can modify user roles

---

## 📞 Support & Contact

For technical support or questions:

- **System Administrator:** Contact your IT department
- **Feature Requests:** Submit through proper channels
- **Bug Reports:** Document and report to development team

---

## 🔄 Version History

- **v2.0 (September 2025):** Major update with English interface, improved authentication, MySQL database
- **v1.x:** Initial Indonesian version with Oracle database

---

## 📋 Quick Reference

### **Important URLs:**

```
Main App: http://localhost/pemay/
Employee Login: http://localhost/pemay/auth/login.php
Customer Login: http://localhost/pemay/auth/customer-login.php
Registration: http://localhost/pemay/auth/register.php
```

### **Default Access:**

- **Admin:** Full access to all modules
- **Doctor/Vet:** Medical + Salon + Pet management
- **Staff:** Basic operations + Customer service
- **Customer:** Personal pets + Profile viewing

---

_This user guide covers all major functionalities of PetCareHub v2.0. For specific technical issues or additional features, please consult your system administrator._

**Happy Pet Care Management! 🐾**
