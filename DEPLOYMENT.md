# CPC-TICKET Docker Deployment Guide

## Quick Start

### 1. Copy Files to Server
Copy the entire `CPC-TICKET` folder to your server.

### 2. Run Docker Compose
```bash
cd CPC-TICKET
docker-compose up -d
```

### 3. Access the Application
- **Web App:** http://localhost:8080
- **Admin Login:** http://localhost:8080/admin/dashboard.php
- **Database:** localhost:3306 (root/rootpassword)

## Default Credentials

### Admin Account
- **Username:** admin
- **Password:** admin123
- **Type:** Administrator

### Employee Account
- **Username:** employee
- **Password:** employee123
- **Type:** Employee

## Docker Commands

### Start Services
```bash
docker-compose up -d
```

### Stop Services
```bash
docker-compose down
```

### View Logs
```bash
docker-compose logs -f
```

### Restart Services
```bash
docker-compose restart
```

### Rebuild Container
```bash
docker-compose down
docker-compose build
docker-compose up -d
```

### Access Database
```bash
docker exec -it cpc-ticketing-db mysql -uroot -prootpassword cpc_ticketing
```

## Configuration

### Database Credentials
Edit `docker-compose.yml` to change database credentials:
```yaml
environment:
  MYSQL_ROOT_PASSWORD: your_password
  MYSQL_DATABASE: cpc_ticketing
  MYSQL_USER: your_user
  MYSQL_PASSWORD: your_password
```

### Web Port
Change the port mapping in `docker-compose.yml`:
```yaml
ports:
  - "8080:80"  # Change 8080 to your desired port
```

## File Structure
```
CPC-TICKET/
  - Dockerfile              # Docker image configuration
  - docker-compose.yml      # Docker services configuration
  - database.sql           # Database schema and data
  - config.docker.php      # Docker-specific config (use this instead of config.php)
  - .dockerignore          # Files to exclude from Docker build
  - admin/                 # Admin pages
  - employee/              # Employee pages
  - auth/                  # Authentication pages
  - Logi image/            # Logo images
```

## Troubleshooting

### Container won't start
```bash
docker-compose logs
```

### Database connection issues
```bash
docker-compose down
docker volume rm cpc-ticketing_mysql_data
docker-compose up -d
```

### Permission issues
```bash
docker exec -it cpc-ticketing-app chown -R www-data:www-data /var/www/html
```

## Production Deployment

### For production use:
1. Change passwords in `docker-compose.yml`
2. Update `config.docker.php` with production database credentials
3. Remove or secure the `setup.php` file
4. Use environment variables for sensitive data
5. Enable SSL/HTTPS
6. Set up proper backups

## Support
For issues, check logs:
```bash
docker-compose logs web
docker-compose logs db
```
