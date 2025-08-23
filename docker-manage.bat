@echo off
setlocal enabledelayedexpansion

if "%1"=="" goto :usage

if "%1"=="start" goto :start
if "%1"=="stop" goto :stop
if "%1"=="restart" goto :restart
if "%1"=="build" goto :build
if "%1"=="logs" goto :logs
if "%1"=="shell" goto :shell
if "%1"=="status" goto :status
if "%1"=="clean" goto :clean

goto :usage

:start
echo Starting Geotagged Incident Reporting System...
docker-compose up -d
echo Application is running at http://localhost:8080
echo Demo credentials:
echo   Admin: admin / mdrrmo2024
echo   Client: client / client2024
goto :end

:stop
echo Stopping Geotagged Incident Reporting System...
docker-compose down
goto :end

:restart
echo Restarting Geotagged Incident Reporting System...
docker-compose down
docker-compose up -d
echo Application is running at http://localhost:8080
goto :end

:build
echo Building and starting Geotagged Incident Reporting System...
docker-compose up --build -d
echo Application is running at http://localhost:8080
goto :end

:logs
echo Showing logs...
docker-compose logs -f
goto :end

:shell
echo Opening shell in container...
docker exec -it geotagged-incident-reporting bash
goto :end

:status
echo Container status:
docker-compose ps
goto :end

:clean
echo Cleaning up containers and images...
docker-compose down --rmi all --volumes --remove-orphans
goto :end

:usage
echo Usage: %0 {start^|stop^|restart^|build^|logs^|shell^|status^|clean}
echo.
echo Commands:
echo   start   - Start the application
echo   stop    - Stop the application
echo   restart - Restart the application
echo   build   - Build and start the application
echo   logs    - Show application logs
echo   shell   - Open shell in container
echo   status  - Show container status
echo   clean   - Clean up containers and images
echo.
echo Access the application at: http://localhost:8080
echo Demo credentials: admin/mdrrmo2024 or client/client2024
goto :end

:end
endlocal
