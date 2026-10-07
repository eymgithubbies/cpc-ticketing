# CPC-TICKET - IT Helpdesk System

A web-based ticketing system for IT support and issue tracking.

## Features

- **User Management:** Admin and employee roles
- **Ticket Management:** Create, assign, track, and resolve tickets
- **Department System:** Organize tickets by department
- **Activity Logging:** Track all ticket activities and changes
- **Announcements:** Post important updates for all users
- **Modern UI:** Clean, responsive design with animations

## Quick Start (Docker)

```bash
# Clone or copy the CPC-TICKET folder
cd CPC-TICKET

# Start with Docker
docker-compose up -d

# Access at http://localhost:8080
```

## Quick Start (Manual)

1. **Setup Database:**
   - Import `database.sql` into MySQL
   - Or run `setup.php` in browser

2. **Configure:**
   - Update `config.php` with database credentials

3. **Access:**
   - Login: http://localhost/CPC-TICKET/
   - Admin: http://localhost/CPC-TICKET/admin/dashboard.php

## Default Accounts

| Type | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Employee | employee | employee123 |

## File Structure

```
CPC-TICKET/
  - admin/              # Admin dashboard and management
  - employee/           # Employee dashboard and tickets
  - auth/               # Login and registration
  - Logi image/         # Logo files
  - config.php          # Database configuration
  - index.php           # Login page
  - register.php        # Registration page
  - setup.php           # Database setup script
  - database.sql        # SQL schema (for Docker)
  - Dockerfile          # Docker image config
  - docker-compose.yml  # Docker services
```

## Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Web Server (Apache/Nginx)
- Docker (optional)

## Deployment

See `DEPLOYMENT.md` for detailed deployment instructions.

## Support

For IT Helpdesk support, contact your IT department.
