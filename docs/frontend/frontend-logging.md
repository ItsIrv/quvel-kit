# Logging Service

## Overview

QuVel Kit provides a robust **Logging Service** that works across both client and server environments. This service offers structured logging with different severity levels, context enrichment, and environment-aware behavior to ensure proper debugging capabilities while maintaining security.

## Features

- **Universal Logging** – Works in both browser and SSR environments
- **Multiple Log Levels** – Support for debug, info, warn, error, and fatal levels
- **Context Enrichment** – Automatically adds timestamp, environment, and custom context
- **Environment-Aware** – Adapts behavior based on development or production environments
- **Service Container Integration** – Available through the container as `container.log`

## Using the Logging Service

### In Vue Components

Access the logging service through the service container:

```ts
import { useContainer } from 'src/modules/Core/composables/useContainer';

const { log } = useContainer();

// Basic logging
log.info('User logged in successfully');
log.error('Failed to load data', { userId: '123', endpoint: '/api/data' });

// With context
log.debug('Component mounted', { component: 'UserProfile' });
```

### In Pinia Stores

The logging service is available in Pinia stores via the container:

```ts
import { defineStore } from 'pinia';

export const useUserStore = defineStore('user', {
  actions: {
    async fetchUser(id: string) {
      try {
        const response = await this.$container.api.get(`/users/${id}`);
        this.$container.log.info('User fetched successfully', { id });
        return response.data;
      } catch (error) {
        this.$container.log.error('Failed to fetch user', { id, error });
        throw error;
      }
    }
  }
});
```

## Log Levels

The logging service provides the following log levels in order of severity:

| Level | Method | Description | When to Use |
|-------|--------|-------------|------------|
| Debug | `log.debug()` | Verbose information for debugging | Development-time troubleshooting |
| Info | `log.info()` | General information about system operation | Normal operational messages |
| Warn | `log.warn()` | Potential issues that don't prevent operation | Non-critical issues |
| Error | `log.error()` | Errors that prevent a specific operation | Failed API calls, validation errors |
| Fatal | `log.fatal()` | Critical errors that prevent the application from functioning | Application crashes |

## Context Enrichment

The logging service automatically enriches log messages with additional context:

```ts
log.info('User action', { userId: '123' });

// Produces a log entry like:
{
  level: 'info',
  message: 'User action',
  timestamp: '2025-05-17T20:55:50-07:00',
  environment: 'development',
  context: {
    userId: '123'
  }
}
```

## Environment-Specific Behavior

The logging service adapts its behavior based on the environment:

### Development Environment

- All log levels are output to the console
- Debug logs are displayed with full context
- Colorized output for better readability

### Production Environment

- Debug logs are suppressed by default
- Error and fatal logs are always output
- Info and warn logs can be configured to be output or suppressed
- Sensitive data is automatically redacted from logs

## Custom Loggers

The logging service supports custom logger implementations through the `Logger` interface:

```ts
import { Logger } from 'src/modules/Core/services/Logger/Logger';

class CustomLogger implements Logger {
  debug(message: string, context?: Record<string, unknown>): void {
    // Custom implementation
  }
  
  info(message: string, context?: Record<string, unknown>): void {
    // Custom implementation
  }
  
  // Other methods...
}

// Register with the service container
container.addService(Logger, new CustomLogger());
```

## Source Files

- **[LogService.ts](../../frontend/src/modules/Core/services/LogService.ts)** – Core logging service
- **[ConsoleLogger.ts](../../frontend/src/modules/Core/services/Logger/ConsoleLogger.ts)** – Console implementation
- **[Logger.ts](../../frontend/src/modules/Core/services/Logger/Logger.ts)** – Logger interface

---

[← Back to Frontend Docs](./README.md)
