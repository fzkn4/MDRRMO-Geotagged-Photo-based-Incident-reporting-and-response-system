#!/bin/bash

# Docker management script for Geotagged Incident Reporting System

case "$1" in
    "start")
        echo "Starting Geotagged Incident Reporting System..."
        docker-compose up -d
        echo "Application is running at http://localhost:8080"
        echo "Demo credentials:"
        echo "  Admin: admin / mdrrmo2024"
        echo "  Client: client / client2024"
        ;;
    "stop")
        echo "Stopping Geotagged Incident Reporting System..."
        docker-compose down
        ;;
    "restart")
        echo "Restarting Geotagged Incident Reporting System..."
        docker-compose down
        docker-compose up -d
        echo "Application is running at http://localhost:8080"
        ;;
    "build")
        echo "Building and starting Geotagged Incident Reporting System..."
        docker-compose up --build -d
        echo "Application is running at http://localhost:8080"
        ;;
    "logs")
        echo "Showing logs..."
        docker-compose logs -f
        ;;
    "shell")
        echo "Opening shell in container..."
        docker exec -it geotagged-incident-reporting bash
        ;;
    "status")
        echo "Container status:"
        docker-compose ps
        ;;
    "clean")
        echo "Cleaning up containers and images..."
        docker-compose down --rmi all --volumes --remove-orphans
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|build|logs|shell|status|clean}"
        echo ""
        echo "Commands:"
        echo "  start   - Start the application"
        echo "  stop    - Stop the application"
        echo "  restart - Restart the application"
        echo "  build   - Build and start the application"
        echo "  logs    - Show application logs"
        echo "  shell   - Open shell in container"
        echo "  status  - Show container status"
        echo "  clean   - Clean up containers and images"
        echo ""
        echo "Access the application at: http://localhost:8080"
        echo "Demo credentials: admin/mdrrmo2024 or client/client2024"
        exit 1
        ;;
esac
