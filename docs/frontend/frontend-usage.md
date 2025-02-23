# Frontend Usage

## Overview

QuVel Kit's frontend is built with **Quasar SSR**, offering a robust environment that supports **server-side rendering (SSR)**, **single-page application (SPA) mode**, and **mobile builds** via Capacitor. The frontend is containerized using Docker for **consistent deployment** and **easy development**.

---

## Running Quasar Commands

To interact with the Quasar container, open a terminal and execute:

```bash
docker exec -it quvel-frontend sh
```

Inside the container, you can use the Quasar CLI:

```bash
yarn build:ssr  # Build the production frontend
quasar dev      # Start Quasar in development mode
exit            # Exit the container
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
