# JobHub - Feature Documentation

## Overview

JobHub is a full-stack job portal that enables employers to publish job openings and job seekers to search and apply through a secure web application.

---

# User Roles

## Job Seeker

- Register
- Login
- Browse Jobs
- Search Jobs
- View Job Details
- Apply for Jobs
- Upload Resume
- Manage Profile
- Dashboard

---

## Employer

- Register
- Login
- Dashboard
- Create Jobs
- Edit Jobs
- Delete Jobs
- View Applicants
- Manage Job Listings

---

# Authentication

The application implements Stateless Bearer Token Authentication.

Features include:

- User Registration
- User Login
- Bearer Token Generation
- Automatic Token Validation
- Role-Based Authorization
- Secure Logout
- Token Expiration

---

# Job Management

Employers can:

- Create Job Listings
- Edit Jobs
- Delete Jobs
- Manage Applicants

Job Seekers can:

- Search Jobs
- View Details
- Submit Applications

---

# Resume Upload

Applicants can upload PDF resumes.

Validation

- PDF Only
- Maximum Size 5 MB

---

# Validation

Client Side

- Required Fields
- Email Validation
- Password Validation
- Form Validation

Server Side

- Authentication
- Duplicate Email Detection
- Input Validation
- Request Validation

---

# User Experience

- Responsive Bootstrap Interface
- Employer Dashboard
- Job Seeker Dashboard
- Mobile Friendly
- Success Alerts
- Error Alerts
- Fast Search

---

# Security

- Stateless Bearer Token Authentication
- SHA-256 Token Hashing
- Password Hashing
- PDO Prepared Statements
- SQL Injection Protection
- Authorization Header Validation
- Input Sanitization
- Secure Logout

---

# Database

PostgreSQL stores

- Users
- Profiles
- Jobs
- Categories
- Applications
- Authentication Tokens

---

# Technologies

## Frontend

- HTML5
- CSS3
- Bootstrap 5
- JavaScript
- jQuery

## Backend

- PHP 8.5
- PDO

## Database

- PostgreSQL
- Neon

## DevOps

- GitHub
- GitHub Actions
- Vercel
- Neon PostgreSQL

---

# Deployment

The application is deployed using

- Vercel Serverless Functions
- Neon PostgreSQL
- GitHub Actions CI Pipeline
- Stateless Bearer Token Authentication

---

# Future Improvements

- Email Verification
- Password Reset
- Notifications
- Company Logos
- Saved Jobs
- Advanced Search Filters
- Pagination
- Admin Dashboard

---

# AI Assistance

AI tools assisted with:

- UI Design
- Feature Planning
- Documentation
- CI/CD Pipeline
- Code Review

Business logic, authentication implementation, testing, debugging, deployment, and integration were completed and verified by the developer.
