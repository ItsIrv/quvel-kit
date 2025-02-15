# Folder Structure

## Overview

The **QuVel Kit** repository follows a structured layout to ensure clarity and maintainability. Below is an overview of the key directories and files within the project.

## 📂 Directory Layout

```bash
├── backend/          # Laravel API (PHP)
├── frontend/         # Quasar SSR (Vue.js)
├── docker/           # Docker configurations & SSL certs
│   ├── certs/        # SSL certificates (generated via mkcert)
│   │   ├── certificates.yaml
│   │   ├── selfsigned.crt
│   │   ├── selfsigned.key
│   ├── backend.Dockerfile  # Dockerfile for backend
│   ├── frontend.Dockerfile # Dockerfile for frontend
│   ├── docker-compose.yml  # Docker Compose configuration
│   ├── traefik.yml         # (Optional) Traefik configuration
├── docs/             # Project documentation
├── scripts/          # Utility scripts for setup & management
│   ├── setup.sh      # One-command setup script
│   ├── start.sh      # Start services manually
│   ├── stop.sh       # Stop services manually
│   ├── reset.sh    # Reset environment
│   ├── restart.sh    # Restart environment
│   ├── logs.sh       # View logs for services
├── .gitignore        # Git ignore settings
├── README.md         # Main project README
```

## 📝 Explanation of Key Directories

- **backend/**: Houses the Laravel application, including routes, models, controllers, and middleware.
- **frontend/**: Contains the Quasar SSR app with Vue.js components and pages.
- **docker/**: Holds configurations for Docker and Traefik, including certificates.
- **docs/**: Documentation files for developers.
- **scripts/**: Utility scripts for automation and environment management.

Ensure you're familiar with the structure to navigate efficiently. 🚀
