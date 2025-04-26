# QuVel Kit Frontend Documentation

## Overview

The **enterprise frontend architecture** of QuVel Kit is built with **Vue 3**, **TypeScript**, and **Quasar 3** in SSR mode, providing a robust foundation for scalable web and mobile applications. This documentation covers the modular architecture and key services that power the frontend ecosystem.

## Technology Stack

- **Vue 3**: Component-based UI framework
- **TypeScript**: Static typing for enhanced code quality
- **Quasar 3**: UI framework with SSR capabilities
- **Pinia**: State management solution
- **Tailwind CSS**: Utility-first CSS framework
- **Axios**: HTTP client for API requests
- **Zod**: Schema validation library
- **Laravel Echo & Pusher**: Real-time WebSocket communication

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

## **Frontend Documentation Sections**

### **Frontend Usage**  

**[Read More →](./frontend-usage.md)**

- Running Quasar SSR & SPA modes
- Development environment configuration
- Testing and debugging strategies

### **Service Container**  

**[Read More →](./frontend-service-container.md)**

- Core services architecture (`api`, `i18n`, `validation`, `task`, `ws`)
- Dependency injection patterns
- SSR-compatible service design

### **Task Management**  

**[Read More →](./frontend-task-management.md)**

- Asynchronous operation orchestration
- Error handling and recovery strategies
- Loading state management

### **Session Management**  

**[Read More →](./frontend-session.md)**

- Pinia-based authentication flow
- SSR state hydration techniques
- Service integration within stores

### **Validation**  

**[Read More →](./frontend-validation.md)**

- Schema-based validation with Zod
- Form validation patterns
- Error message localization

### **Translations**  

**[Read More →](./frontend-translations.md)**

- Internationalization architecture
- Dynamic locale management
- Translation organization strategies

### **Component Usage**  

**[Read More →](./frontend-component-usage.md)**

- Service integration in components
- Form handling best practices
- Reusable component patterns

### **WebSockets**  

**[Read More →](./frontend-websockets.md)**

- Real-time communication architecture
- Channel subscription patterns
- Presence and private channels

### **Pagination**  

**[Read More →](./frontend-pagination.md)**

- Data pagination strategies
- Cursor and offset pagination
- Pinia store integration

## Need Help?

For troubleshooting, check **[Troubleshooting Guide](../troubleshooting.md)** or open an issue in the project repository.
