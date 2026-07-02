# JobHub – AI-Powered Job Board

## Overview

JobHub is a full-stack job board application that connects employers with job seekers. Employers can post jobs and manage applicants, while job seekers can search jobs, apply with resumes, and manage their profiles.

---

## Features

### Authentication

- User Registration
- User Login
- Session Management
- Role-Based Access (Employer & Job Seeker)

### Job Seeker

- Browse available jobs
- View job details
- Apply for jobs
- Upload resume (PDF)
- Manage profile

### Employer

- Employer Dashboard
- Post new jobs
- View applicants
- Manage job listings

### Validation

- Client-side form validation
- Server-side validation
- Error handling
- Secure session management

---

## Technology Stack

### Frontend

- HTML5
- CSS3
- Bootstrap 5
- JavaScript
- jQuery

### Backend

- PHP

### Database

- PostgreSQL

### DevOps

- Git
- GitHub
- GitHub Actions (CI)
- Vercel (Deployment)
- Neon PostgreSQL

---

## Project Structure

```
JobHub/
│
├── api/
│   ├── controllers/
│   ├── utils/
│   └── config.php
│
├── assets/
│   ├── css/
│   ├── js/
│   └── uploads/
│
├── views/
│
├── database.sql
├── index.html
└── README.md
```

---

## Installation

### Clone Repository

```bash
git clone https://github.com/Akanksha-Gopu/JobHub.git
```

### Open Project

```bash
cd JobHub
```

### Configure Database

1. Create a PostgreSQL database.
2. Import `database.sql`.
3. Update database credentials in `api/config.php`.

### Run PHP Server

```bash
php -S localhost:8000
```

Open:

```
http://localhost:8000
```

---

## CI/CD

This project includes a GitHub Actions workflow that automatically validates the repository on every push.

---

## Deployment

- Frontend hosted using Vercel.
- PostgreSQL database hosted on Neon.

---

## Future Improvements

- Email verification
- Password reset
- Advanced job search
- Admin dashboard
- Company logo uploads
- Notifications

---

## Author

Akanksha Gopu
