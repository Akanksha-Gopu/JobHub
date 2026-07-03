-- Active: 1775629937179@@127.0.0.1@5432@job_board@public
-- Job Board Database Schema (PostgreSQL)

-- Drop tables if they exist to start fresh (useful for development)
DROP TABLE IF EXISTS applications CASCADE;
DROP TABLE IF EXISTS jobs CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS profiles CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- 1. Users Table
-- Stores credentials and user role (seeker, employer, admin)
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL CHECK (role IN ('seeker', 'employer', 'admin')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

SELECT * FROM users;

-- 2. Profiles Table
-- Stores profile metadata: seekers upload resumes, employers input company info
CREATE TABLE profiles (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(50),
    bio TEXT,
    company_name VARCHAR(150),       -- Employer specific
    company_website VARCHAR(255),    -- Employer specific
    resume_path VARCHAR(255),        -- Seeker specific (path to uploaded resume PDF)
    skills VARCHAR(255),             -- Seeker specific (comma-separated skills)
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

Select * FROM profiles;

-- 3. Categories Table
-- List of job categories (e.g. Frontend, Backend, Fullstack, Mobile)
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

-- 4. Jobs Table
-- Job listings posted by Employers
CREATE TABLE jobs (
    id SERIAL PRIMARY KEY,
    employer_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    location VARCHAR(150) NOT NULL,
    salary_range VARCHAR(100),       -- Simplistic text representation e.g. "$60k - $80k"
    job_type VARCHAR(50) NOT NULL CHECK (job_type IN ('full-time', 'part-time', 'contract', 'remote')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

SELECT * FROM jobs;

-- 5. Applications Table
-- Stores job applications submitted by Job Seekers
CREATE TABLE applications (
    id SERIAL PRIMARY KEY,
    job_id INT NOT NULL REFERENCES jobs(id) ON DELETE CASCADE,
    seeker_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    resume_path VARCHAR(255) NOT NULL, -- Captures the path of the resume PDF at application time
    cover_letter TEXT,
    status VARCHAR(50) DEFAULT 'applied' CHECK (status IN ('applied', 'reviewing', 'offered', 'rejected')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT unique_seeker_job_apply UNIQUE (seeker_id, job_id)
);

SELECT * FROM applications;


-- Insert Seed Data (Categories)
INSERT INTO categories (name) VALUES 
('Frontend Development'),
('Backend Development'),
('Full Stack Development'),
('Mobile App Development'),
('DevOps & Cloud'),
('Data Science & Analytics'),
('Product Management'),
('UI/UX Design');

-- 6. User Tokens Table
-- Stores SHA-256 hashed user session tokens
CREATE TABLE IF NOT EXISTS user_tokens (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash VARCHAR(64) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);

SELECT * FROM user_tokens;

