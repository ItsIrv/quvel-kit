# Environment Setup & Usage

## Overview

QuVel Kit's frontend is built with **Quasar SSR**, offering a robust environment that supports **server-side rendering (SSR)**, **single-page application (SPA) mode**, and **mobile builds** via Capacitor. The frontend is containerized using Docker for **consistent deployment** and **easy development**.

---

## Quick Start

To get started with the frontend:

```bash
docker exec -it quvel-frontend sh
```

Inside the container, you can run:

```bash
yarn dev        # Start development mode
yarn build:ssr  # Build for production
quasar          # Access Quasar CLI
exit            # Exit container
```

### Running Quasar on Local Machine

QuVel Kit supports running the frontend alongside your Docker environment.  

To start using the frontend, on your local machine, simply execute commands as normal in your terminal, but add `LOCAL=1` before the command. This lets the Quasar configuration helpers to set the appropriate environment variables.
I have set up some shortcuts for you.

```bash
yarn dev-local        # SPA Mode
yarn dev-local:ssr    # SSR Mode
yarn dev:ios # Always runs in local
yarn dev:electron # Always runs in local
```

- By default, running locally starts at **`second-tenant`**.  
- The **local instance** can be accessed at:

```bash
https://quvel.127.0.0.1.nip.io:3000/ # Main Quvel
https://second-tenant.quvel.127.0.0.1.nip.io/ # Second Tenant
https://second-tenant.quvel.127.0.0.1.nip.io:3001 # Capacitor
```

- Please note the port `3000` at the end of URLs. Due to the nip.io domain routing system, domains on your local machine can be anything, ie <https://not-quvel.127.0.0.1.nip.io:3000>. This just routes you to 127.0.0.1:3000 under the hood.

- To avoid having two frontend instances up, **manually stop** the container `quvel-frontend`.This can be configured in **`configs/ssr.ts`** and **`configs/spa.ts`**.

```bash
docker stop quvel-frontend
```

---

## Debugging Frontend Issues

### Viewing Frontend Logs

Monitor frontend logs using:

```bash
docker logs -f quvel-frontend  # Follow live logs
```

### Restarting Quasar in Development Mode

```bash
docker restart quvel-frontend
```

If hot reloading fails, restart the container.

---

## Customizing the Frontend

### **Modifying Environment Variables**

Environment variables are stored in `.env`. To edit:

```bash
nano frontend/.env
```

Apply changes by restarting the frontend:

```bash
docker restart quvel-frontend
```

### **Hot Reloading Support**

QuVel Kit supports **hot reloading**, allowing changes to take effect without restarting the container.

---

## Testing the Frontend

QuVel Kit uses **Vitest** for unit testing.

### Running Unit Tests

Execute unit tests with:

```bash
yarn test:unit  # Run unit tests
```

For a UI-based debugging experience:

```bash
yarn test:unit:ui  # Open Vitest UI
```

For CI environments:

```bash
yarn test:unit:ci  # Run unit tests in CI mode
```

### **Viewing Vitest Coverage Reports**

The docker environment sets up a **coverage server** at:

<https://coverage.quvel.127.0.0.1.nip.io/__vitest__/>

---

## Building for Production

To build the frontend for production:

```bash
yarn build:ssr  # Builds the Quasar SSR version
```

---

[← Back to Frontend Docs](./README.md)
