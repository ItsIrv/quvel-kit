# Traefik Setup for Reverse Proxy & SSL

## Overview

QuVel Kit uses **Traefik** as a **reverse proxy** to handle **SSL termination** and **subdomain routing** for both the frontend (Quasar SSR) and backend (Laravel API). This setup ensures that:

- All services run over **HTTPS** (even internally).
- WebSockets function correctly.
- Local development mimics a **real-world production environment**.

---

## How Traefik Works in QuVel Kit

Traefik runs as a **Docker service**, acting as a **gateway** between the host machine and application services. It dynamically routes **requests** based on container labels and predefined **rules**.

### Key Features in This Setup

- **Automatic SSL Certificates** – Uses **mkcert** for local trusted certificates.
- **Subdomain-Based Routing** – Routes requests to `https://quvel.127.0.0.1.nip.io`.
- **WebSockets Support** – Ensures Quasar’s SSR and Vite dev server work over secure WebSockets.
- **Dynamic Configuration** – Uses `traefik.yml` and dynamic `.yaml` files for services.

---

## Traefik Configuration Structure

### 1. Main Configuration (`traefik.yml`)

- Defines **entry points** (`http`, `https`).
- Enables the **Traefik dashboard**.
- Configures the **certificate resolver** for HTTPS.

### 2. Dynamic Configuration (`dynamic/*.yaml`)

- Stores service-specific routing rules for:
  - **Frontend (Quasar SSR)**
  - **Backend (Laravel API)**
  - **Certificate settings**
- Each service has its own `.yaml` file.

### 3. Docker Labels

- Each container (backend, frontend) is assigned **labels** in `docker-compose.yml`.
- These labels tell **Traefik** where to route requests.

---

## Setting Up Traefik

### 1. Ensure mkcert is Installed

Before running the project, **mkcert** should be installed for generating SSL certificates.

```bash
mkcert -install
```

This ensures that **local SSL certificates** are trusted.

---

### 2. Verify SSL Certificate Files

The **Traefik service** expects SSL certificates in:

```bash
docker/certs/
```

Ensure the following files exist:

- `selfsigned.crt`
- `selfsigned.key`
- `certificates.yaml` (defines certificate mappings)

If missing, regenerate them:

```bash
./scripts/setup.sh
```

---

### 3. Understanding the Routing System

Each service in QuVel Kit is mapped via **subdomains**, using **`.nip.io`** for local resolution.

| Service   | Local URL |
|-----------|--------------------------------|
| **Frontend**  | `https://quvel.127.0.0.1.nip.io` |
| **API (Laravel)** | `https://api.quvel.127.0.0.1.nip.io` |
| **Traefik Dashboard** | `http://localhost:8080` |

**How It Works:**

- Requests to `quvel.127.0.0.1.nip.io` go to the **Quasar frontend**.
- Requests to `api.quvel.127.0.0.1.nip.io` go to the **Laravel API**.
- The **Traefik Dashboard** runs separately on port `8080`.

---

### 4. Running the Project with Traefik

Traefik is included in `docker-compose.yml`. To start the entire stack:

```bash
./scripts/start.sh
```

To restart Traefik only:

```bash
docker-compose restart traefik
```

---

### 5. Debugging & Logs

#### Check Traefik Logs

```bash
docker logs -f quvel-traefik
```

#### View Running Routes

```bash
docker exec -it quvel-traefik traefik routes
```

#### Check the Dashboard

If enabled, the Traefik Dashboard is accessible at:

```bash
http://localhost:8080
```

---

## Why HTTPS Internally?

Normally, Quasar’s dev server (`Vite`) runs over HTTP. However, **WebSockets** (HMR, Live Reload, etc.) require HTTPS in some cases, especially in a **proxy setup**.

To fix this, we:

- Serve **Quasar SSR over HTTPS** internally.
- Ensure all communication (frontend/backend) remains encrypted.

This guarantees:

- WebSockets work correctly
- Secure connections even in development
- Better alignment with production environments

---

## Recap & Next Steps

- Traefik handles **subdomain routing, SSL, and reverse proxying**.
- Services are accessed via `*.127.0.0.1.nip.io` domains.
- WebSockets work because **everything is HTTPS**.
- The setup closely **mirrors production environments**.

Next, check out:

- **[Frontend Usage](./frontend/frontend-usage.md)** – Learn how the frontend interacts with Traefik.
- **[Backend Usage](./backend-usage.md)** – Understand how Laravel is exposed via Traefik.
