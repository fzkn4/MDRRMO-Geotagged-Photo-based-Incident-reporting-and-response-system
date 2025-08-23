# Docker Setup for Geotagged Incident Reporting System

This guide will help you run the Geotagged Incident Reporting System using Docker instead of XAMPP.

## Prerequisites

- Docker installed on your system
- Docker Compose installed on your system

## Windows Users

If you're on Windows, you have several options:

1. **Use the Windows batch file:**

   ```cmd
   docker-manage.bat start
   ```

2. **Use the PowerShell script:**

   ```powershell
   .\docker-manage.ps1 start
   ```

3. **Use Docker commands directly:**
   ```cmd
   docker-compose up --build
   ```

## Quick Start

1. **Build and start the application:**

   ```bash
   docker-compose up --build
   ```

2. **Access the application:**
   Open your browser and go to: `http://localhost:8080`

3. **Stop the application:**
   ```bash
   docker-compose down
   ```

## Development Mode

For development with live code changes:

```bash
# Start in detached mode
docker-compose up -d

# View logs
docker-compose logs -f

# Stop
docker-compose down
```

## Demo Credentials

The application comes with demo credentials:

- **Admin User:**

  - Username: `admin`
  - Password: `mdrrmo2024`

- **Client User:**
  - Username: `client`
  - Password: `client2024`

## File Persistence

- User data is stored in `users.json` and persists between container restarts
- The entire application directory is mounted, so code changes are reflected immediately

## Container Management

### View running containers:

```bash
docker ps
```

### Access container shell:

```bash
docker exec -it geotagged-incident-reporting bash
```

### View container logs:

```bash
docker logs geotagged-incident-reporting
```

### Rebuild after changes:

```bash
docker-compose up --build
```

## Adding Database Support (Optional)

If you want to add MySQL database support later, uncomment the database services in `docker-compose.yml`:

1. Uncomment the `db` service
2. Uncomment the `phpmyadmin` service
3. Uncomment the `volumes` section
4. Update your PHP code to use database instead of file storage

## Troubleshooting

### Port already in use:

If port 8080 is already in use, change the port mapping in `docker-compose.yml`:

```yaml
ports:
  - "8081:80" # Change 8080 to 8081 or another available port
```

### Permission issues:

If you encounter permission issues, run:

```bash
sudo chown -R $USER:$USER .
```

### Container won't start:

Check the logs:

```bash
docker-compose logs
```

## Production Deployment

For production deployment:

1. Remove the volume mount for the entire directory
2. Use environment variables for configuration
3. Set up proper SSL/TLS certificates
4. Configure proper backup for the `users.json` file
5. Consider migrating to a proper database

## Environment Variables

You can customize the setup by adding environment variables to `docker-compose.yml`:

```yaml
environment:
  - APACHE_DOCUMENT_ROOT=/var/www/html
  - PHP_MEMORY_LIMIT=256M
  - PHP_MAX_EXECUTION_TIME=300
```

## Security Notes

- This setup is for development purposes
- For production, consider:
  - Using HTTPS
  - Implementing proper session management
  - Adding rate limiting
  - Using environment variables for sensitive data
  - Regular security updates
