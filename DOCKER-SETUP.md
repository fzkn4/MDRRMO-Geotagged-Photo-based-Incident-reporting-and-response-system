# Docker Setup Guide for MDRRMO Incident Reporting System

This guide will help you run the MDRRMO Geotagged Photo-based Incident Reporting System using Docker.

## Prerequisites

- **Docker Desktop** installed and running
  - Windows: Download from [docker.com](https://www.docker.com/products/docker-desktop)
  - Mac: Download from [docker.com](https://www.docker.com/products/docker-desktop)
  - Linux: Install Docker Engine and Docker Compose

## Quick Start

### Option 1: Using Docker Compose (Recommended)

1. **Open terminal/command prompt** in the project directory

2. **Build and start all services:**

   ```bash
   docker-compose up --build -d
   ```

3. **Wait for services to start** (first time may take a few minutes)

4. **Access the application:**
   - **Main Application:** http://localhost:8080
   - **phpMyAdmin:** http://localhost:8081

### Option 2: Using Management Scripts

**Windows:**

```cmd
docker-manage.bat build
```

**Linux/Mac:**

```bash
chmod +x docker-manage.sh
./docker-manage.sh build
```

## Services Overview

The Docker setup includes three services:

1. **geotagged-app** (PHP 8.2 + Apache)

   - Port: 8080
   - Main application server
   - Auto-reloads code changes (volume mounted)

2. **db** (MySQL 8.0)

   - Database server
   - Automatically initializes schema from `db/init.sql`
   - Data persists in Docker volume

3. **phpmyadmin**
   - Port: 8081
   - Database management interface
   - Server: `db`, User: `geotagged_user`, Password: `geotagged_password`

## Database Configuration

The database is automatically configured with these credentials:

- **Host:** `db` (use this inside Docker containers)
- **Port:** `3306`
- **Database:** `geotagged`
- **Username:** `geotagged_user`
- **Password:** `geotagged_password`

These are set in `docker-compose.yml` and automatically used by the application.

## First-Time Setup

1. **Start the containers:**

   ```bash
   docker-compose up --build -d
   ```

2. **Wait for database initialization** (check logs):

   ```bash
   docker-compose logs db
   ```

3. **Access the application:**

   - Open http://localhost:8080
   - You'll be redirected to the login page

4. **Create your first admin user:**
   - Click "Sign up here" on the login page
   - Fill in the registration form
   - Set role to "admin" (or "client" for regular users)
   - After registration, you can log in

## Common Commands

### Start services:

```bash
docker-compose up -d
```

### Stop services:

```bash
docker-compose down
```

### View logs:

```bash
docker-compose logs -f
```

### View specific service logs:

```bash
docker-compose logs -f geotagged-app
docker-compose logs -f db
```

### Rebuild after code changes:

```bash
docker-compose up --build -d
```

### Access container shell:

```bash
docker exec -it geotagged-incident-reporting bash
```

### Check container status:

```bash
docker-compose ps
```

### Clean everything (removes containers, volumes, images):

```bash
docker-compose down -v --rmi all
```

## Database Management

### Using phpMyAdmin

1. Open http://localhost:8081
2. Login with:
   - **Server:** `db`
   - **Username:** `geotagged_user`
   - **Password:** `geotagged_password`

### Using MySQL Command Line

```bash
docker exec -it geotagged-incident-reporting_db_1 mysql -u geotagged_user -pgeotagged_password geotagged
```

### Reset Database

To reset the database and start fresh:

```bash
docker-compose down -v
docker-compose up --build -d
```

This will:

- Remove all database data
- Re-run `db/init.sql` to recreate tables
- Start fresh with empty database

## Troubleshooting

### Port Already in Use

If port 8080 or 8081 is already in use, edit `docker-compose.yml`:

```yaml
ports:
  - "8082:80" # Change 8080 to any available port
```

### Database Connection Errors

1. **Check if database is ready:**

   ```bash
   docker-compose logs db
   ```

2. **Wait for initialization:** The database needs a few seconds to initialize on first start

3. **Check environment variables:** Ensure `DB_HOST=db` in your environment

### Application Not Loading

1. **Check container status:**

   ```bash
   docker-compose ps
   ```

2. **Check application logs:**

   ```bash
   docker-compose logs geotagged-app
   ```

3. **Restart services:**
   ```bash
   docker-compose restart
   ```

### Permission Issues (Linux/Mac)

If you encounter permission issues:

```bash
sudo chown -R $USER:$USER .
```

### Windows-Specific Issues

- **WSL2:** Ensure Docker Desktop is using WSL2 backend
- **File Permissions:** If files aren't updating, check Docker Desktop file sharing settings
- **Port Conflicts:** Use `netstat -ano | findstr :8080` to check if port is in use

## Development Workflow

The application directory is mounted as a volume, so code changes are immediately reflected:

1. Edit files in your project directory
2. Refresh browser (no need to restart containers)
3. PHP errors will appear in logs: `docker-compose logs -f geotagged-app`

## Production Deployment

For production:

1. **Remove volume mounts** for source code (use COPY in Dockerfile)
2. **Set environment variables** properly
3. **Use HTTPS** (configure reverse proxy)
4. **Set up database backups**
5. **Configure proper file permissions**
6. **Use environment-specific `.env` file**

## Environment Variables

You can customize the setup by creating a `.env` file:

```env
DB_HOST=db
DB_PORT=3306
DB_DATABASE=geotagged
DB_USER=geotagged_user
DB_PASSWORD=your_secure_password
APP_ENV=production
```

## File Structure

```
.
├── docker-compose.yml      # Docker services configuration
├── Dockerfile              # PHP application container definition
├── db/
│   └── init.sql           # Database schema initialization
├── .env.example           # Environment variables template
└── [application files]
```

## Support

For issues or questions:

1. Check container logs: `docker-compose logs`
2. Verify database is running: `docker-compose ps`
3. Check port availability
4. Review this guide's troubleshooting section

## Next Steps

After starting the application:

1. Create an admin account via signup
2. Log in with admin credentials
3. Access admin dashboard at http://localhost:8080/admin-dashboard.php
4. Create client accounts or test the incident reporting system
