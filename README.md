# JobHub – AI-Powered Job Board

## Overview

JobHub is a full-stack job board application that connects employers with job seekers. Employers can create and manage job listings, while job seekers can search for jobs, apply with resumes, and manage their profiles.

The application is built using PHP, PostgreSQL, Bootstrap, jQuery, and deployed on Vercel using Neon PostgreSQL.

---

# Features

## Authentication

- User Registration
- User Login
- Stateless Bearer Token Authentication
- Role-Based Access Control (Employer & Job Seeker)
- Automatic Authentication Validation
- Secure Logout
- Password Hashing

## Job Seeker Features

- Browse Jobs
- Search Jobs
- View Job Details
- Apply for Jobs
- Upload Resume (PDF)
- Manage Profile
- Dashboard

## Employer Features

- Employer Dashboard
- Create Job Listings
- Update Job Listings
- Delete Job Listings
- View Applicants
- Manage Posted Jobs

## Validation & Security

- Client-side validation
- Server-side validation
- Bearer Token Authentication
- Password Hashing
- PDO Prepared Statements
- SQL Injection Protection
- Input Sanitization
- Error Handling

---

# Technology Stack

## Frontend

- HTML5
- CSS3
- Bootstrap 5
- JavaScript
- jQuery

## Backend

- PHP 8.5
- PDO
- REST-style API

## Database

- PostgreSQL
- Neon Database

## DevOps

- Git
- GitHub
- GitHub Actions
- Vercel
- Neon PostgreSQL

---

# Project Structure

```
JobHub/
│
├── api/
│   ├── controllers/
│   ├── utils/
│   ├── config.php
│   └── database.sql
│
├── assets/
│   ├── css/
│   ├── js/
│   └── uploads/
│
├── views/
│
├── .github/workflows/
├── vercel.json
├── index.html
└── README.md
```

---

# Installation

## Clone Repository

```bash
git clone https://github.com/yourusername/JobHub.git
```

## Navigate

```bash
cd JobHub
```

## Configure Database

Create a PostgreSQL database and import:

```
database.sql
```

Update:

```
api/config.php
```

or configure environment variables if deploying.

## Run Locally

```bash
php -S localhost:8000
```

Open

```
http://localhost:8000
```

---

# Authentication

The application uses Stateless Bearer Token Authentication.

Authentication Flow

1. User logs in.
2. Backend validates credentials.
3. A secure token is generated.
4. The token is securely stored in the database as a SHA-256 hash.
5. The raw token is returned to the client.
6. The frontend stores the token in Local Storage.
7. Every API request automatically includes:

```
Authorization: Bearer <token>
```

8. Protected APIs validate the token before processing requests.

---

# CI/CD

GitHub Actions automatically:

- Validate repository
- Execute workflow on every push
- Deploy latest version to Vercel

---

# Deployment

Application Hosting

- Vercel Serverless Functions

Database

- Neon PostgreSQL

Authentication

- Stateless Bearer Token Authentication

---

# Security

Implemented security measures include:

- Password Hashing
- SHA-256 Token Hashing
- Prepared Statements (PDO)
- SQL Injection Protection
- Input Validation
- Role-Based Authorization
- Token Expiration
- Secure Logout

---

# Future Improvements

- Email Verification
- Password Reset
- Job Bookmarking
- Notifications
- Company Logos
- Advanced Filters
- Pagination
- Admin Analytics

---

# AI Assistance

AI tools were used for:

- UI design
- Feature planning
- Documentation
- CI/CD workflow generation
- Code refinement

Application architecture, backend logic, authentication, testing, debugging, deployment, and final verification were completed by the developer.

---

# Author

Akanksha Gopu
