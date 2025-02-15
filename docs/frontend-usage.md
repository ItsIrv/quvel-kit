# Frontend Usage

## 🖥️ Accessing the Quasar Frontend

The frontend of QuVel Kit is powered by **Quasar SSR**, running an **Express server** inside a Docker container. By default, it operates in **SSR mode with a SPA fallback**.

---

## 🛠️ Running Quasar Commands

To access the Quasar container and run commands:

```bash
docker exec -it quvel-frontend sh
```

Once inside, you can use:

```bash
quasar dev  # Start Quasar in development mode (hot-reload enabled)
quasar build  # Build the production frontend
exit  # Exit the container
```

---

## 🔍 Debugging Frontend Issues

### View Frontend Logs

```bash
docker logs -f quvel-frontend  # Follow live logs
```

### Restarting Quasar in Development Mode

```bash
docker restart quvel-frontend
```

If hot reload does not work as expected, restart the container.

---

## 🎨 Customizing the Frontend

### **Modifying Environment Variables**

The frontend uses a `.env` file. Modify it with:

```bash
nano frontend/.env
```

Restart the frontend to apply changes:

```bash
docker restart quvel-frontend
```

### **Hot Reloading Support**

QuVel Kit supports **hot reloading**, meaning changes to Vue components are applied automatically without restarting the container.

---

## 🏐 Future Improvements

- Automate hot-reloading for Docker volumes.
- Provide a GUI for managing frontend settings.

🚀 **You're now set up to develop with the Quasar frontend!**
