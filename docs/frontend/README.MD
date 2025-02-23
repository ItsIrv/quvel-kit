# QuVel Kit Frontend Documentation

## Overview

The **frontend** of QuVel Kit is built with **Quasar SSR**, providing a powerful and flexible environment for web and mobile applications. This documentation covers key areas of frontend development, including service management, validation, translations, and tasks.

## Quick Start

To get started with the frontend:

```bash
docker exec -it quvel-frontend sh
```

Inside the container, you can run:

```bash
yarn dev # Start development mode
yarn build:ssr # Build for production
quasar # Access Quasar CLI
exit # Exit container
```

For more details, see **[Frontend Usage](./frontend-usage.md)**.

---

## 📌 **Frontend Sections**

### **1️⃣ Frontend Usage**  

**[Read More →](./frontend-usage.md)**

- Running Quasar SSR & SPA
- Debugging & environment variables
- Hot reloading & logs

### **2️⃣ Service Container**  

**[Read More →](./frontend-service-container.md)**

- Core services (`api`, `i18n`, `validation`, `task`)
- Dynamic service registration
- Dependency injection & SSR compatibility

### **3️⃣ Task Management**  

**[Read More →](./frontend-task-management.md)**

- Creating and running async tasks
- Handling success & errors
- Managing loading states

### **4️⃣ Session Management**  

**[Read More →](./frontend-session.md)**

- Pinia session store for authentication
- SSR state hydration
- Using services inside Pinia stores

### **5️⃣ Validation**  

**[Read More →](./frontend-validation.md)**

- Schema-based validation with Zod
- Integration with Vue components
- Translating validation errors

### **6️⃣ Translations**  

**[Read More →](./frontend-translations.md)**

- Managing multilingual support
- i18n setup & language switching

### **7️⃣ Component Usage**  

**[Read More →](./frontend-component-usage.md)**

- Using tasks inside Vue components
- Handling form validation
- Accessing services in components

## Need Help?

For troubleshooting, check **[Troubleshooting Guide](../troubleshooting.md)** or open an issue in the project repository.
