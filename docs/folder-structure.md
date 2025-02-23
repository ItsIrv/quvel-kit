# Folder Structure

## Overview

QuVel Kit follows a structured layout to ensure clarity, maintainability, and ease of development. Below is an overview of the key directories and files within the project.

## Directory Layout

```bash
├── backend/          # Laravel API (PHP)
├── frontend/         # Quasar SSR (Vue.js)
├── docker/           # Docker configurations & SSL certificates
│   ├── certs/        # SSL certificates (generated via mkcert)
│   ├── traefik/      # Traefik reverse proxy setup
│   │   ├── dynamic/  # Dynamic routing configurations
│   │   │   ├── certificates.yaml
│   │   │   ├── frontend.yml
│   │   │   ├── traefik.yml
│   ├── backend.Dockerfile  # Dockerfile for backend
│   ├── frontend.Dockerfile # Dockerfile for frontend
│   ├── docker-compose.yml  # Docker Compose configuration
│   ├── php.ini             # PHP configuration
├── docs/             # Documentation files
│   ├── frontend/     # Frontend-specific documentation
│   ├── backend/      # Backend-specific documentation
├── scripts/          # Utility scripts for setup & management
│   ├── setup.sh      # One-command setup script
│   ├── start.sh      # Start services manually
│   ├── stop.sh       # Stop services manually
│   ├── reset.sh      # Reset environment
│   ├── restart.sh    # Restart environment
│   ├── logs.sh       # View logs for services
├── .github/          # GitHub Actions & workflows
│   ├── workflows/    # CI/CD configuration
│   │   ├── backend-ci.yml  # CI pipeline for backend
├── .gitignore        # Git ignore settings
├── README.md         # Main project README
```

## Explanation of Key Directories

### **Backend (`backend/`)**

- Houses the Laravel application, including routes, models, controllers, and middleware.
- Contains API authentication, business logic, and database migrations.

### **Frontend (`frontend/`)**

- Contains the Quasar SSR application built with Vue.js.
- Manages UI components, pages, and state management.

### **Docker (`docker/`)**

- Holds configurations for Docker services, including backend, frontend, MySQL, and Redis.
- Stores SSL certificates used for local HTTPS development.
- **Traefik reverse proxy** setup for handling subdomains and secure routing.
- **Dynamic configuration** for Traefik, managing certificates and frontend routing.

### **Docs (`docs/`)**

- Documentation covering setup, architecture, and development guides.
- Split into frontend and backend sections for better organization.

### **Scripts (`scripts/`)**

- Collection of bash scripts to automate setup and management.
- Handles starting/stopping services, resetting environments, and logging.

### **GitHub Workflows (`.github/workflows/`)**

- Houses CI/CD pipelines for automated testing and deployment.
- Contains workflow files for backend and frontend testing.

## Additional Notes

- **Environment Variables:** The project uses `.env` files in both frontend and backend for configuration.
- **Code Quality:** Linters and formatters are configured to ensure consistent coding standards.
- **Hot Reloading:** Changes in `frontend/` and `backend/` are reflected in real time during development.

## Next Steps

- **[Getting Started](./getting-started.md)** – Follow the setup guide.
- **[Frontend Usage](./frontend/frontend-usage.md)** – Learn about the Vue.js SSR environment.
- **[Backend Usage](./backend-usage.md)** – Understand Laravel’s structure and API usage.
