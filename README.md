# Task Manager API  
### Cytonn Software Engineering Internship Challenge

This project is a complete, production-ready task management system built with Laravel 10 and a responsive Vue.js interface. It was developed as part of the Cytonn Software Engineering Internship Challenge, with a strong focus on clean architecture, clear business rule enforcement, and consistent API design.

The system exposes a RESTful API and includes a simple frontend interface for interacting with tasks in real time.

---

## Table of Contents

- [Live Demo](#-Live-Demo)
- [Features](#-Features)
- [Tech Stack](#-tech-stack)
- [Business Rules](#-business-rules)
- [API Documentation](#-api-documentation)
- [Local Setup](#-local-setup)
- [Deployment Guide](#-deployment-guide)
- [Testing](#-testing)
- [Project Structure](#-project-structure)
- [Error Handling](#-error-handling)
- [Database Schema](#-database-schema)
- [Author](#-author)

---

## Live Demo

The application is deployed and accessible online:

| Environment | URL |
|-------------|-----|
| **Live Application** | https://web-production-1b37.up.railway.app |
| **API Base URL** | https://web-production-1b37.up.railway.app/api |
| **GitHub Repository** | https://github.com/EFadhili/Task-Manager-API |

---

## Features

### Core Functionality

- **Create Tasks**  
  Add new tasks with a title, due date, and priority level (`low`, `medium`, `high`).

- **List Tasks**  
  Retrieve all tasks sorted by priority (high → low) and then by due date.

- **Filter Tasks**  
  Optionally filter tasks by status using query parameters.

- **Update Task Status**  
  Move tasks through a defined workflow: `pending → in_progress → done`.

- **Delete Tasks**  
  Remove tasks, with deletion restricted to completed tasks only.

- **Daily Report (Bonus)**  
  Generate a report summarizing tasks by priority and status for a given date.

---

### Technical Highlights

- **Global Exception Handling**  
  All errors are handled centrally to ensure consistent API responses.

- **Input Validation**  
  Requests are validated with clear and meaningful error messages.

- **Vue.js Interface**  
  A simple and interactive UI built with Vue.js and styled using Tailwind CSS.

- **MySQL Database**  
  Well-structured schema with migrations and seeders included.

- **Comprehensive Documentation**  
  All endpoints are documented with request and response examples.

---

## Tech Stack

| Layer | Technology | Version |
|-------|------------|---------|
| Backend Framework | Laravel | 10.48.8 |
| Language | PHP | 8.3 |
| Frontend | Vue.js | 2 (CDN) |
| Styling | Tailwind CSS | 2 |
| HTTP Client | Axios | 1.6 |
| Database | MySQL | 8.0+ (MariaDB compatible) |
| Deployment | Railway | - |

---

## Business Rules

### Task Creation Rules

| Rule | Description |
|------|-------------|
| Title Uniqueness | A task cannot have the same title and due date as an existing task |
| Due Date Validation | The due date must be today or a future date |
| Priority Validation | Priority must be one of: `low`, `medium`, `high` |

---

### Status Progression Rules

| Current Status | Allowed Next Status |
|----------------|---------------------|
| `pending` | `in_progress` only |
| `in_progress` | `done` only |
| `done` | No further updates allowed |

---

### Deletion Rules

| Rule | Description |
|------|-------------|
| Delete Restriction | Only tasks marked as `done` can be deleted |
| Error Handling | Attempting to delete a non-completed task returns a `403 Forbidden` response |

---

### Task Listing Rules

| Rule | Description |
|------|-------------|
| Primary Sorting | Tasks are sorted by priority: high → medium → low |
| Secondary Sorting | Tasks are sorted by due date (earliest first) |
| Optional Filtering | Tasks can be filtered by status using `?status=` |

---

## API Documentation

### Base URL
Local: http://127.0.0.1:8000/api

Production: https://web-production-1b37.up.railway.app/api


---

### 1. Create Task

**POST** `/api/tasks`

Creates a new task after validating the request data.

**Request Headers**

Content-Type: application/json

**Request Body**
```json
{
    "title": "Complete project documentation",
    "due_date": "2026-04-05",
    "priority": "high"
}
```
**Successful Response (201 Created)**
```json
{
    "success": true,
    "message": "Task created successfully",
    "data": {
        "id": 1,
        "title": "Complete project documentation",
        "due_date": "2026-04-05",
        "priority": "high",
        "status": "pending",
        "created_at": "2026-04-01T10:00:00.000000Z",
        "updated_at": "2026-04-01T10:00:00.000000Z"
    }
}
```

**Validation Error (422)**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "title": ["A task with this title already exists for this due date."],
        "due_date": ["The due date must be a date after or equal to today."],
        "priority": ["The selected priority is invalid."]
    }
}
```

### 2. List All Tasks

**GET** `/api/tasks`

Returns all tasks sorted by priority and due date.

---
Optional Query Parameters
| Parameter | Values                     | Example         |
| --------- | -------------------------- | --------------- |
| status    | pending, in_progress, done | ?status=pending |

---

**Response (200 OK)**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Complete Cytonn challenge",
            "due_date": "2026-04-01",
            "priority": "high",
            "status": "pending",
            "created_at": "2026-04-01T10:00:00.000000Z",
            "updated_at": "2026-04-01T10:00:00.000000Z"
        }
    ],
    "count": 1
}
```
### 3. Get Single Task

**GET** `/api/tasks/{id}`

**Response (200 OK)**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Complete Cytonn challenge",
        "due_date": "2026-04-01",
        "priority": "high",
        "status": "pending",
        "created_at": "2026-04-01T10:00:00.000000Z",
        "updated_at": "2026-04-01T10:00:00.000000Z"
    }
}
```
**Not Found (404)**
```json
{
    "success": false,
    "message": "Task not found",
    "error": "The requested resource does not exist"
}
```

### 4. Update Task Status

**PATCH** `/api/tasks/{id}/status`

**Request Body**
```json
{
    "status": "in_progress"
}
```
**Success Response**
```json
{
    "success": true,
    "message": "Task status updated from 'pending' to 'in_progress'",
    "data": {
        "id": 1,
        "status": "in_progress",
        "updated_at": "2026-04-01T10:30:00.000000Z"
    }
}
```
**Invalid Transition (403)**
```json
{
    "success": false,
    "message": "Cannot change status from 'pending' to 'done'",
    "details": {
        "current_status": "pending",
        "allowed_statuses": ["in_progress"],
        "requested_status": "done"
    }
}
```

### 5. Delete Task

**DELETE** `/api/tasks/{id}`

Only allowed if the task status is done

**Success Response (204 No Content)**

**Error (403)**
```json
{
    "success": false,
    "message": "Cannot delete task",
    "error": "Only completed tasks can be deleted. Current status: 'pending'",
    "required_status": "done",
    "current_status": "pending"
}
```

### 6. Daily Task Report

**GET** `/api/tasks/report?date=YYYY-MM-DD`

**Response**
```json
{
    "success": true,
    "data": {
        "date": "2026-04-01",
        "summary": {
            "high": { "pending": 2, "in_progress": 1, "done": 0 }
        },
        "statistics": {
            "total_tasks": 8,
            "completed": 4,
            "pending": 3,
            "in_progress": 1,
            "completion_rate": "50%"
        }
    }
}
```

## Local Setup

**Prerequisites**

- PHP 8.3

- Composer

- MySQL 8.0+ (or SQLite)

- Git

**Installation Steps**
```
git clone https://github.com/EFadhili/Task-Manager-API.git
cd Task-Manager-API
composer install
```

**Environment Setup**
```
cp .env.example .env
php artisan key:generate
```

**Database Setup**

**MySQL**
```SQL
CREATE DATABASE tasks;
```
**Update .env, then run:**
```
php artisan migrate
php artisan db:seed --class=TaskSeeder
```
**Run the Application**
```
php artisan serve
```

## Deployment

Supports deployment via Railway or Render with standard Laravel build and start commands.

### Testing

You can test using:

- cURL

- Postman

- Browser (for GET endpoints)

**Example:js**
```
curl http://127.0.0.1:8000/api/tasks
```

## Project Structure
```
app/
database/
resources/
routes/
public/
```
Key components include controllers, models, migrations, and a Vue.js interface.

## Error Handling

**All responses follow a consistent format:**
```json
{
    "success": false,
    "message": "Error message",
    "errors": {},
    "details": {},
    "error": ""
}
```

## Database Schema

**Tasks Table**

- Unique constraint on (title, due_date)

- Enum fields for priority and status

A full SQL dump is included in database.sql

## Author

- Name: Elton Fadhili Mumalasi

- Email: fadhilielton@gmail.com

- GitHub: https://github.com/EFadhili

- Date: April 1, 2026

## Acknowledgment

This project was built specifically for the Cytonn Software Engineering Internship Challenge. All requirements have been implemented and tested.
