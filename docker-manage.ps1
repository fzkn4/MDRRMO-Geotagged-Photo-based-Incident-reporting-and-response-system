param(
    [Parameter(Position=0)]
    [ValidateSet("start", "stop", "restart", "build", "logs", "shell", "status", "clean")]
    [string]$Command
)

function Show-Usage {
    Write-Host "Usage: .\docker-manage.ps1 {start|stop|restart|build|logs|shell|status|clean}" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Commands:" -ForegroundColor Cyan
    Write-Host "  start   - Start the application" -ForegroundColor White
    Write-Host "  stop    - Stop the application" -ForegroundColor White
    Write-Host "  restart - Restart the application" -ForegroundColor White
    Write-Host "  build   - Build and start the application" -ForegroundColor White
    Write-Host "  logs    - Show application logs" -ForegroundColor White
    Write-Host "  shell   - Open shell in container" -ForegroundColor White
    Write-Host "  status  - Show container status" -ForegroundColor White
    Write-Host "  clean   - Clean up containers and images" -ForegroundColor White
    Write-Host ""
    Write-Host "Access the application at: http://localhost:8080" -ForegroundColor Green
    Write-Host "Demo credentials: admin/mdrrmo2024 or client/client2024" -ForegroundColor Green
}

if (-not $Command) {
    Show-Usage
    exit 1
}

switch ($Command) {
    "start" {
        Write-Host "Starting Geotagged Incident Reporting System..." -ForegroundColor Green
        docker-compose up -d
        Write-Host "Application is running at http://localhost:8080" -ForegroundColor Green
        Write-Host "Demo credentials:" -ForegroundColor Yellow
        Write-Host "  Admin: admin / mdrrmo2024" -ForegroundColor White
        Write-Host "  Client: client / client2024" -ForegroundColor White
    }
    "stop" {
        Write-Host "Stopping Geotagged Incident Reporting System..." -ForegroundColor Yellow
        docker-compose down
    }
    "restart" {
        Write-Host "Restarting Geotagged Incident Reporting System..." -ForegroundColor Yellow
        docker-compose down
        docker-compose up -d
        Write-Host "Application is running at http://localhost:8080" -ForegroundColor Green
    }
    "build" {
        Write-Host "Building and starting Geotagged Incident Reporting System..." -ForegroundColor Green
        docker-compose up --build -d
        Write-Host "Application is running at http://localhost:8080" -ForegroundColor Green
    }
    "logs" {
        Write-Host "Showing logs..." -ForegroundColor Cyan
        docker-compose logs -f
    }
    "shell" {
        Write-Host "Opening shell in container..." -ForegroundColor Cyan
        docker exec -it geotagged-incident-reporting bash
    }
    "status" {
        Write-Host "Container status:" -ForegroundColor Cyan
        docker-compose ps
    }
    "clean" {
        Write-Host "Cleaning up containers and images..." -ForegroundColor Yellow
        docker-compose down --rmi all --volumes --remove-orphans
    }
}
