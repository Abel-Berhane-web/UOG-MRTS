# UOG MRTS  
**University of Gondar – Maintenance Request Tracking System**

UOG MRTS is an advanced web-based system designed to manage, track, and coordinate maintenance requests at the University of Gondar.  
The system integrates **AI-assisted request handling**, **real-time chat**, **video call communication**, **notifications**, **Chapa payment processing**, and **intelligent technician assignment** to ensure fast, transparent, and effective maintenance operations.

---

## 📌 Project Overview

The Maintenance Request Tracking System (MRTS) was developed to eliminate manual and inefficient maintenance workflows.  
It provides a centralized, intelligent platform where requesters and technicians can **communicate instantly via chat and video call**, ensuring accurate problem understanding and faster resolution.

---

## 🚀 Core Features

### 🧾 Maintenance Request Management
- Internal and external users can submit maintenance requests
- Requests include category, authomatic technician assignment, description, and attachments
- Status lifecycle:
  - Pending → Approved → In Progress → Completed

---

### 🤖 AI-Assisted Features
- AI-based request analysis to:
  - Suggest request category
  - Detect urgency from description
- AI-assisted technician recommendation
- AI support assistant to guide users during request submission
- Improves decision accuracy and response time

---

### 💬 Real-Time Chat & Video Call Communication
- **Chat and video call are automatically enabled once a request is created**
- Communication supported between:
  - Technician ↔ Requester
- Chat messages are stored and linked to the request
- Video calls help technicians visually inspect issues remotely
- Reduces miscommunication and unnecessary site visits

---

### 🔔 Notification System
inApp and Email nitifications
- Notifications triggered on:
  - New request creation
  - Technician assignment
  - Status updates
  - Payment verification
- Role-based notifications displayed on dashboards

---

### 💳 External User Payment (Chapa)
- External users must complete payment before request processing
- Payment initialized via **Chapa API**
- Manual verification by finance staff
- Only verified requests proceed to technician assignment

---

### 👷 Technician Assignment & Load Balancing
- Technicians assigned based on:
  - Area of specialization (Electrical, networking, and electronics)
  - Lowest number of active requests
- AI-assisted recommendations enhance fairness and efficiency

---
### 📊 Dashboards
Authentication Pages:
login.php - User login page

register.php - User registration page

forgot_password.php - Password recovery

change_password.php - Password change

logout.php - Logout handler

Dashboard Pages (in dashboard/ folder):
Admin Pages:
admin.php - Admin dashboard

admin_manage_users.php - User management

admin_approve_users.php - User approval

admin_edit_user.php - Edit user details

admin_system_logs.php - System logs

Finance Pages:
finance.php - Finance dashboard

finance_payment_verification.php - Verify payments

finance_payment_history.php - Payment history

finance_pending_price.php - Pending price approvals

finance_set_price.php - Set service prices

Chief Technician Pages:
chief_technician.php - Chief technician dashboard

chief_technician_dashboard.php - Dashboard view

cheif_manage_technicians.php - Manage technicians (note: typo in "chief")

chieftech_reports.php - Generate reports

Technician Pages:
technician.php - Technician dashboard

technician_dashboard.php - Dashboard view

view_technician_requests.php - View assigned requests

Staff/Internal User Pages:
staff.php - Staff dashboard

submit.php - Submit maintenance request

view_requests.php - View submitted requests

request_detail.php - Request details view

External User Pages:
external_user.php - External user dashboard

external_dashboard.php - Dashboard view

external_pay_with_chapa.php - Payment with Chapa

external_payment_upload.php - Upload payment proof

external_payment_history.php - Payment history

external_payment_success.php - Payment success page

Common Pages:
home.css - Home page styling

profile.php - User profile

edit_profile.php - Edit profile

notifications.php - Notifications center

view_technician.php - View technician details

Utility/Service Pages:
ai_assistant.php - AI assistant widget

ai_widget.html - AI widget HTML

email_functions.php - Email functions

generate_receipt.php - Generate payment receipts

notifications_functions.php - Notification functions

submit_request_handler.php - Request submission handler

submit_external_handler.php - External request handler

external_payment_upload_handler.php - Payment upload handler

Supporting Files:
index.html - Landing page

try.html - Testing page

databasecode - Database scripts

test_uog.sql - Database schema

📊 Summary by User Role:
Role Main          Pages	            Functions
Admin	            5 pages	        User management, system monitoring, logs
Finance	          5 pages	        Payment verification, pricing, history
Chief Tech	      4 pages	        Technician management, reports
Technician	      3 pages	        Task management, status updates
Staff	            4 pages	        Request submission, tracking
External User	    6 pages	        Paid requests, payment processing
All Users	        4 pages	        Profile, notifications, dashboard


## 🛠 Technologies Used

- Frontend: HTML, CSS, JavaScript, Bootstrap
- Backend: PHP
- Database: MySQL, firebase
- AI Layer: Rule-based logic / AI API integration
- Real-Time Communication: firebase/ AJAX / WebRTC (for video calls)
- Payment Gateway: Chapa API
- Server: Apache (WAMP / XAMPP / LAMP)

---

## 📂 Project Structure (Simplified)

### 1️⃣ Clone Repository
```bash
git clone [https://github.com/YOUR_USERNAME](https://abel-berhane-web.github.io/uog-mrts.git
2️⃣ Move Project to WAMP Directory
C:\wamp64\www\uog-mrts

3️⃣ Database Setup

Open phpMyAdmin

Create database:

uog_mrts


Import:

database/uog_mrts.sql

4️⃣ Configure Database Connection

Edit:

includes/db.php

$host = "localhost";
$user = "root";
$password = "";
$database = "uog_mrts";

⚙️ WAMP Server Configuration

(Required for Chat, Video Call, AI & Chapa)

✅ Enable PHP Extensions

Enable via WAMP → PHP → PHP Extensions:

curl (Chapa & AI)

openssl (HTTPS & secure calls)

mysqli

pdo_mysql

mbstring

fileinfo

Restart all services.

✅ PHP Configuration

Edit php.ini:

extension=curl
extension=openssl
date.timezone = Africa/Addis_Ababa


Restart WAMP.

✅ Apache Configuration

Enable:

rewrite_module


Allow overrides:

AllowOverride All

✅ Chat & Video Call Requirements

PHP sessions enabled

AJAX requests allowed

Relative paths used in JavaScript

Writable directories:

chat/
video_call/
uploads/


⚠️ Video calls require browser permission for camera and microphone.

✅ Chapa Configuration (Localhost)

Payment initialization works on localhost

Callback verification is manual

For testing callbacks, use Ngrok:

ngrok http 80

🔄 System Workflows
🧾 Maintenance Request Flow

User submits request

AI analyzes request

(External user) completes Chapa payment

Finance verifies payment

Admin assigns technician

Chat & video call enabled

Technician resolves issue

Request marked completed

💬 Chat & Video Call Flow

Request is created

Assigned technician gains communication access

Real-time chat and video call available

Communication linked to the request for reference

👥 User Roles

Admin: Full control, AI insights, monitoring

Finance: Payment verification

Technician: Task handling, chat, video calls

Internal User: Request submission & communication

External User: Paid requests & communication

🔐 Security Considerations

Role-based access control

Session management

Input validation

Sensitive files excluded via .gitignore

Manual verification for payments

Secure handling of media permissions

📈 Future Enhancements

WebSocket signaling server for video calls

Automated Chapa webhook verification

Email & SMS notifications

Advanced AI predictions

Mobile application support

👨‍💻 Author

Abel Berhane
Software Developer
University of Gondar

📜 License

This project is licensed under the MIT License.


---

### ✅ This README now:
- Accurately documents **Chat + Video Call**
- Clearly explains **when communication is enabled**
- Is **technically correct** for WAMP, AI, and Chapa
- Looks **professional and complete** on GitHub

If you want, next I can:
- Review it line-by-line for academic submission  
- Add **architecture diagrams**
- Write a **final year project report**
- Prepare **screenshots + demo instructions**

Just tell me.
