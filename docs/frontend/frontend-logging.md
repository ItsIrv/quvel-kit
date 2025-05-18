# Logging Service

## Overview

QuVel Kit provides a robust **Logging Service** that works across both client and server environments. This service offers structured logging with different severity levels, context enrichment, and environment-aware behavior to ensure proper debugging capabilities while maintaining security. The system also includes a distributed tracing mechanism that maintains context across SSR and client environments.

## Features

- **Universal Logging** – Works in browser, SSR, and native environments
- **PSR-3 Inspired Levels** – Support for eight standard log levels (emergency to debug)
- **Distributed Tracing** – Trace IDs flow from server to client for request tracking
- **Context Enrichment** – Automatically adds trace info, timestamp, environment, and tenant
- **Environment-Aware** – Adapts behavior based on development or production environments
- **Service Container Integration** – Available through the container

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

The logging service provides the following PSR-3 inspired log levels in order of severity:

| Level | Method | Description | When to Use |
|-------|--------|-------------|------------|
| Emergency | `log.emergency()` | System is unusable | Catastrophic failures |
| Alert | `log.alert()` | Action must be taken immediately | Critical system alerts |
| Critical | `log.critical()` | Critical conditions | Component failures |
| Error | `log.error()` | Runtime errors | Failed operations, exceptions |
| Warning | `log.warning()` | Exceptional occurrences | Non-critical issues |
| Notice | `log.notice()` | Normal but significant events | Important state changes |
| Info | `log.info()` | Informational messages | Normal operational events |
| Debug | `log.debug()` | Detailed debug information | Development troubleshooting |

## Trace System

The logging system includes a distributed tracing mechanism that maintains context across server and client environments:

```ts
// TraceInfo structure
interface TraceInfo {
  id: string;            // Unique trace identifier
  timestamp: string;     // ISO timestamp when trace was created
  environment: string;   // Runtime environment (dev, prod, etc.)
  tenant?: string;       // Current tenant identifier
  runtime: 'server' | 'client' | 'native'; // Runtime context
}
```

Trace information is automatically:

1. Generated on the server for each SSR request
2. Passed to the client via window.__TRACE__
3. Included with all log messages

This enables correlation of logs across server and client for the same user session.

### Accessing Trace Information

```ts
// Get the current trace info
const traceInfo = log.getTraceInfo();

// Include trace ID in API calls for backend correlation
api.get('/endpoint', {
  headers: {
    'X-Trace-ID': traceInfo.id
  }
});
```

## Configuration

Logging behavior is configured through environment variables:

```env
# Logger implementation to use (CONSOLE or NULL)
VITE_LOGGER=CONSOLE

# Minimum log level to display (debug, info, notice, warning, error, critical, alert, emergency)
VITE_LOG_LEVEL=info
```

## Context Enrichment

The logging service automatically enriches log messages with trace information and additional context:

```ts
log.info('User action', { userId: '123' });

// Produces a log entry like:
{
  level: 'info',
  message: 'User action',
  trace: {
    id: '550e8400-e29b-41d4-a716-446655440000',
    timestamp: '2025-05-17T20:55:50-07:00',
    environment: 'development',
    tenant: 'default',
    runtime: 'client'
  },
  context: {
    userId: '123'
  }
}
```

## Logger Implementations

QuVel Kit includes two logger implementations:

1. **ConsoleLogger** - Outputs logs to the console with formatting
2. **NullLogger** - Silent logger that doesn't output anything (for production)

The implementation is selected based on the `VITE_LOGGER` environment variable.

## Creating Custom Loggers

You can create custom logger implementations by implementing the `LoggerInterface`:

```ts
import { LoggerInterface, TraceInfo } from 'src/modules/Core/types/logging.types';

class CustomLogger implements LoggerInterface {
  constructor(private traceInfo: TraceInfo) {}
  
  getTraceInfo(): TraceInfo {
    return this.traceInfo;
  }
  
  debug(message: string, context?: Record<string, unknown>): void {
    // Custom implementation
  }
  
  info(message: string, context?: Record<string, unknown>): void {
    // Custom implementation
  }
  
  // Implement other methods...
}
```

## Source Files

- **[LogService.ts](../../frontend/src/modules/Core/services/LogService.ts)** – Core logging service
- **[ConsoleLogger.ts](../../frontend/src/modules/Core/services/Logger/ConsoleLogger.ts)** – Console implementation
- **[NullLogger.ts](../../frontend/src/modules/Core/services/Logger/NullLogger.ts)** – Silent implementation
- **[logging.types.ts](../../frontend/src/modules/Core/types/logging.types.ts)** – Logger interface and types
- **[loggingUtil.ts](../../frontend/src/modules/Core/utils/loggingUtil.ts)** – Utility functions
- **[render.ts](../../frontend/src-ssr/middlewares/render.ts)** – SSR trace generation

---

[← Back to Frontend Docs](./README.md)
