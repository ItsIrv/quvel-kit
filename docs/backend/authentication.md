# Authentication

## Overview

QuVel Kit implements a robust authentication system using Laravel Sanctum for token-based authentication and Laravel Socialite for OAuth integration. This guide covers the authentication architecture, implementation details.

## Authentication Architecture

The authentication system is implemented in the `Auth` module and provides:

- Token-based authentication using Laravel Sanctum
- Social authentication via Socialite OAuth providers

## Authentication Flow

All Laravel Fortify authentication flows are supported.

## Authentication Configuration

Configure authentication settings in the Auth module's configuration `Modules/Auth/config/config.php`.

## Capacitor Flows

All authentication flows work in Capacitor, even Socialite OAuth flows.
This is done through WebSockets and Laravel Echo. A non-socket method will be developed when more thought is put into deep links, custom URL schemes, and other mobile app features.

[← Back to Backend Documentation](./README.md)
