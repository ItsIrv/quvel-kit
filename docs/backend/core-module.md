# Core Module

## Overview

The Core module provides the foundational functionality for QuVel Kit, including base services, security features, frontend configuration, and common utilities used across all other modules.

## Architecture

### Service Registration

The Core module registers essential services including `FrontendService` for URL generation and redirect handling. It also registers the `CoreConfigPipe` for tenant configuration management.

## Core Services

### FrontendService

Manages frontend URL generation and redirects with tenant-aware configuration. Provides methods for generating frontend URLs with query parameters and creating redirect responses.

### Security Features

The Core module provides CAPTCHA verification through configurable providers, supporting Google reCAPTCHA and other verification services via the `CaptchaVerifierInterface`.

## Core Traits

### Utility Traits

- **RendersBadRequest**: Standardizes bad request responses
- **TranslatableEnum**: Enables translation support for enums  
- **TranslatableException**: Adds translation support to exceptions

## Tenant Configuration

### CoreConfigPipe

The Core module handles essential tenant configuration through `CoreConfigPipe`, which processes settings like:

- **Application settings**: `app_name`, `app_url`, `app_timezone`, `app_locale`
- **Frontend URLs**: `frontend_url`, `internal_api_url`
- **WebSocket configuration**: Pusher settings for real-time features
- **CORS settings**: Based on frontend URL configuration

The pipe has priority 100 (high priority) and exposes key values like `apiUrl`, `appName`, and `pusherAppKey` as public configuration for frontend consumption.

## Module Base Classes

### Base Service Providers

- **ModuleServiceProvider**: Base class for all module service providers with config and translation registration
- **ModuleRouteServiceProvider**: Base class for module route providers with web and API route mapping

## Core Components

### Enums
- **StatusEnum**: Common status values (active, inactive, pending, suspended, deleted)
- **CoreHeader**: HTTP headers used across the application

### Contracts
- **TranslatableEntity**: Interface for entities supporting translation
- **CaptchaVerifierInterface**: Interface for CAPTCHA verification providers

### Configuration
Core module configuration includes frontend URLs, security settings (CAPTCHA), and API configuration through environment variables.

---

[← Back to Backend Documentation](./README.md)