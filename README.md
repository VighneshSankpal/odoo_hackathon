#  Reimbursement Management System (Odoo Hackathon)

##  Problem Statement

Companies often struggle with manual expense reimbursement processes that are:

* Time-consuming
* Error-prone
* Lack transparency

This project solves these issues by building a **dynamic and automated reimbursement system** with multi-level approval workflows and flexible rules.

---

##  Objective

To develop a system where:

* Employees can submit expense claims
* Managers/Admins can approve or reject them
* The system supports **multi-level and conditional approvals**

---

##  Key Features

###  Authentication & User Management

* Secure login/signup system
* Auto creation of **Company & Admin** on first signup
* Role-based access:

  * 👨‍💼 Admin
  * 👨‍💻 Manager
  * 👤 Employee

---

###  Expense Submission

* Employees can:

  * Submit expenses (Amount, Category, Description, Date)
  * Upload receipts
  * View expense history (Approved / Rejected / Pending)

---

###  Approval Workflow

* Multi-level approval system:

  * Manager → Finance → Director
* Sequential approval process
* Next approver receives request only after previous approval

---

###  Conditional Approval Flow

Supports advanced approval rules:

* 📊 Percentage-based (e.g., 60% approvals required)
* 👤 Specific approver (e.g., CFO approval)
* 🔀 Hybrid rules (Combination of both)

---

###  Roles & Permissions

| Role     | Permissions                                   |
| -------- | --------------------------------------------- |
| Admin    | Manage users, define rules, view all expenses |
| Manager  | Approve/reject expenses, view team data       |
| Employee | Submit expenses, track status                 |

---

###  OCR Integration (Bonus Feature)

* Upload receipt image
* Automatically extract:

  * Amount
  * Date
  * Vendor name

---

###  API Integration

* Country & Currency API
* Currency conversion support

---

##  Tech Stack

### Frontend

* HTML
* CSS
* JavaScript

### Backend

* PHP

### Database

* MySQL

---

##  Project Structure

```
odoo_hackathon/
│
├── dashboards/        # Dashboard UI
├── loginsystem/       # Authentication system
├── uploads/           # Receipt storage
├── db.php             # Database connection
├── reimbursement_db.sql  # Database schema
└── README.md
```

---

## ⚙️ How It Works

1. User signs up → Company & Admin created
2. Admin adds employees/managers
3. Employee submits expense
4. System routes request based on approval rules
5. Managers/Admin approve/reject
6. Final status shown to employee

---

##  Future Enhancements

* Real-time notifications
* Mobile responsive UI
* Advanced analytics dashboard
* AI-based fraud detection

---

##  Team

* Shreyash Mulik
* Vighnesh Sankpal
* sanskar kulkarni
* poorv ghugre

---

##  Conclusion

This project transforms traditional reimbursement systems into a **smart, automated, and transparent workflow**, making it efficient for modern organizations.

---
