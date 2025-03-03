# Getting Started

## Prerequisites

Before setting up QuVel Kit, ensure you have the following installed:

- **Docker & Docker Compose** – Required for running the entire stack.
- **Node.js & Yarn** – Needed for frontend development and mkcert SSL setup.
- **mkcert** – Used to generate self-signed SSL certificates for local development.

### **Verify Installation**

Run the following commands to confirm everything is installed:

```bash
docker -v       # Check Docker version
docker-compose -v  # Check Docker Compose version
node -v         # Check Node.js version
yarn -v         # Check Yarn version
mkcert -install # Ensure mkcert is configured
```

### iOS Capacitor Requirements

- **Xcode** – Version 12.0 or higher
- **iOS Simulator** – Version 12.0 or higher

---

## Setting Up the Project

### **1. Clone the Repository**

```bash
git clone https://github.com/ItsIrv/quvel-kit.git
cd quvel-kit
```

### **2. Run Setup Script**

The setup script will:

- Install dependencies  
- Generate self-signed SSL certificates  
- Start Docker services  

Run:

```bash
./scripts/setup.sh
```

### **3. Access the Services**

Once the setup is complete, the following services will be available:

| Service            | URL |
|--------------------|--------------------------------|
| **Frontend**       | [https://quvel.127.0.0.1.nip.io](https://quvel.127.0.0.1.nip.io) |
| **API (Backend)**  | [https://api.quvel.127.0.0.1.nip.io](https://api.quvel.127.0.0.1.nip.io) |
| **Traefik Dashboard** | [http://localhost:8080](http://localhost:8080) |

---

## Common Commands

| Action              | Command |
|---------------------|---------|
| **Start Services**  | `./scripts/start.sh` |
| **Stop Services**   | `./scripts/stop.sh` |
| **Restart Services** | `./scripts/restart.sh` |
| **View Logs**       | `./scripts/logs.sh` |
| **Reset Everything** | `./scripts/reset.sh` |

---

## Next Steps

- **[Folder Structure](./folder-structure.md)** – Understand the project layout.
- **[Frontend Usage](./frontend/frontend-usage.md)** – Running Quasar SSR and the Vue frontend.
- **[Backend Usage](./backend-usage.md)** – Working with Laravel, database migrations, and debugging.
- **[Service Container](./frontend/frontend-service-container.md)** – Managing services like API, validation, tasks, and translations.
