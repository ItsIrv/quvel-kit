# Getting Started

## Prerequisites

Before you begin, ensure you have the following installed:

- **Docker** & **Docker Compose**
- **Node.js** (for mkcert SSL setup)

## Setting Up the Project

### 1️. **Clone the Repository**

```bash
git clone https://github.com/ItsIrv/quvel-kit.git
cd quvel-kit
```

### 2️. **Run Setup Script**

This will install dependencies, generate SSL certificates, and start the Docker services.

```bash
./scripts/setup.sh
```

### 3️. **Access the Services**

| Service   | URL |
|-----------|--------------------------------|
| **Frontend**  | [https://quvel.127.0.0.1.nip.io](https://quvel.127.0.0.1.nip.io) |
| **API**       | [https://api.quvel.127.0.0.1.nip.io](https://api.quvel.127.0.0.1.nip.io) |
| **Traefik Dashboard** | [http://localhost:8080](http://localhost:8080) |

## Common Commands

| Action | Command |
|--------|---------|
| Start services | `./scripts/start.sh` |
| Stop services | `./scripts/stop.sh` |
| Restart services | `./scripts/restart.sh` |
| View logs | `./scripts/logs.sh` |

Once setup is complete, you're ready to start developing with QuVel Kit! 🎉
