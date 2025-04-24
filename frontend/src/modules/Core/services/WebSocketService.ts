import Echo from 'laravel-echo';
import Pusher, { Channel } from 'pusher-js';
import { BootableService } from 'src/modules/Core/types/service.types';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { ConnectionState, PresenceHandlers } from '../types/websocket.types';

/**
 * Logger utility for WebSocket operations
 */
class WebSocketLogger {
  private static instance: WebSocketLogger;
  private debugMode = false;

  private constructor() {}

  /**
   * Gets the singleton instance
   */
  public static getInstance(): WebSocketLogger {
    if (!WebSocketLogger.instance) {
      WebSocketLogger.instance = new WebSocketLogger();
    }
    return WebSocketLogger.instance;
  }

  /**
   * Sets debug mode
   *
   * @param enabled - Whether debug mode is enabled
   */
  public setDebugMode(enabled: boolean): void {
    this.debugMode = enabled;
  }

  /**
   * Logs an info message
   *
   * @param message - Message to log
   * @param data - Optional data to log
   */
  public info(message: string, data?: unknown): void {
    console.info(`[WebSocketService] ${message}`, data !== undefined ? data : '');
  }

  /**
   * Logs a debug message (only in debug mode)
   *
   * @param message - Message to log
   * @param data - Optional data to log
   */
  public debug(message: string, data?: unknown): void {
    if (this.debugMode) {
      console.debug(`[WebSocketService] ${message}`, data !== undefined ? data : '');
    }
  }

  /**
   * Logs a warning message
   *
   * @param message - Message to log
   * @param data - Optional data to log
   */
  public warn(message: string, data?: unknown): void {
    console.warn(`[WebSocketService] ${message}`, data !== undefined ? data : '');
  }

  /**
   * Logs an error message
   *
   * @param message - Message to log
   * @param error - Optional error to log
   */
  public error(message: string, error?: unknown): void {
    console.error(`[WebSocketService] ${message}`, error !== undefined ? error : '');
  }
}

/**
 * WebSocket error class for handling specific WebSocket-related errors
 */
export class WebSocketError extends Error {
  /**
   * Creates a new WebSocketError instance
   *
   * @param message - Error message
   * @param code - Error code
   * @param originalError - Original error that caused this error
   */
  constructor(
    message: string,
    public readonly code: string = 'WEBSOCKET_ERROR',
    public readonly originalError?: unknown,
  ) {
    super(message);
    this.name = 'WebSocketError';
  }
}

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

/**
 * WebSocketService provides a centralized service for managing WebSocket connections
 * using Laravel Echo and Pusher.
 */
export class WebSocketService implements BootableService {
  private api: ApiService | null = null;
  private echo: Echo<'pusher'> | null = null;
  private connectionPromise: Promise<void> | null = null;
  private connectionState: ConnectionState = ConnectionState.DISCONNECTED;
  private readonly apiKey: string;
  private readonly cluster: string;
  private connectionListeners: Array<(state: ConnectionState, error?: Error) => void> = [];
  private lastError: Error | null = null;
  private logger = WebSocketLogger.getInstance();
  private debugMode = true;

  /**
   * Creates a new WebSocketService instance
   *
   * @param options - Configuration options
   */
  constructor({ apiKey, cluster }: { apiKey: string; cluster: string; apiUrl: string }) {
    this.apiKey = apiKey;
    this.cluster = cluster;
    this.logger.info('WebSocketService initialized', { apiKey, cluster });

    if (typeof window !== 'undefined' && !window.Pusher) {
      window.Pusher = Pusher;
    }
  }

  /**
   * Enables or disables debug mode
   *
   * @param enabled - Whether debug mode is enabled
   */
  public setDebugMode(enabled: boolean): void {
    this.debugMode = enabled;
    this.logger.setDebugMode(enabled);
    this.logger.info(`Debug mode ${enabled ? 'enabled' : 'disabled'}`);
  }

  /**
   * Registers the service with the service container
   *
   * @param container - Service container
   */
  public register(container: ServiceContainer): void {
    this.api = container.api;
    this.logger.debug('Service registered with container');
  }

  /**
   * Boots the service
   */
  public boot(): void {}

  /**
   * Gets the current connection state
   *
   * @returns The current connection state
   */
  public getConnectionState(): ConnectionState {
    return this.connectionState;
  }

  /**
   * Gets the last error that occurred
   *
   * @returns The last error or null if no error occurred
   */
  public getLastError(): Error | null {
    return this.lastError;
  }

  /**
   * Adds a connection state change listener
   *
   * @param listener - Listener function
   * @returns Function to remove the listener
   */
  public addConnectionListener(
    listener: (state: ConnectionState, error?: Error) => void,
  ): () => void {
    this.connectionListeners.push(listener);

    // Immediately notify with current state
    listener(this.connectionState, this.lastError || undefined);

    return () => {
      this.connectionListeners = this.connectionListeners.filter((l) => l !== listener);
    };
  }

  /**
   * Updates the connection state and notifies listeners
   *
   * @param state - New connection state
   * @param error - Optional error
   */
  private updateConnectionState(state: ConnectionState, error?: Error): void {
    const prevState = this.connectionState;
    this.connectionState = state;

    if (error) {
      this.lastError = error;
    } else if (state !== ConnectionState.ERROR) {
      this.lastError = null;
    }

    this.logger.info(
      `Connection state changed: ${prevState} -> ${state}`,
      error ? { error } : undefined,
    );

    this.connectionListeners.forEach((listener) => {
      try {
        listener(state, error);
      } catch (err) {
        this.logger.error('Error in connection listener', err);
      }
    });
  }

  /**
   * Connects to the WebSocket server
   *
   * @returns Promise that resolves when connected
   */
  public connect(): Promise<void> {
    if (typeof window === 'undefined') {
      this.logger.debug('Window not defined, skipping connection');
      return Promise.resolve();
    }

    if (this.connectionState === ConnectionState.CONNECTED) {
      this.logger.debug('Already connected, skipping connection');
      return Promise.resolve();
    }

    if (this.connectionPromise) {
      this.logger.debug('Connection already in progress, returning existing promise');
      return this.connectionPromise;
    }

    this.logger.info('Initiating WebSocket connection');
    this.updateConnectionState(ConnectionState.CONNECTING);

    this.connectionPromise = new Promise<void>((resolve, reject) => {
      try {
        this.logger.debug('Creating Echo instance', { key: this.apiKey, cluster: this.cluster });
        this.echo = new Echo({
          broadcaster: 'pusher',
          key: this.apiKey,
          cluster: this.cluster,
          authorizer: (channel: Channel) => {
            this.logger.debug(`Creating authorizer for channel: ${channel.name}`);
            const apiService = this.api as ApiService;

            return {
              authorize: async (socketId: string, callback: (b: boolean, d: unknown) => void) => {
                this.logger.debug(`Authorizing channel ${channel.name} with socket ID ${socketId}`);
                try {
                  const data = await apiService.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name,
                  });

                  this.logger.debug(`Authorization successful for channel ${channel.name}`, data);
                  return callback(false, data);
                } catch (error) {
                  this.logger.error(`Authorization failed for channel ${channel.name}`, error);
                  const wsError = new WebSocketError(
                    `Authorization failed for channel ${channel.name}`,
                    'AUTH_ERROR',
                    error,
                  );
                  this.updateConnectionState(ConnectionState.ERROR, wsError);
                  return callback(true, null);
                }
              },
            };
          },
        });

        // Set up connection event handlers
        this.echo.connector.pusher.connection.bind('connected', () => {
          this.logger.info('WebSocket connection established');
          this.updateConnectionState(ConnectionState.CONNECTED);
          resolve();
        });

        this.echo.connector.pusher.connection.bind('connecting', () => {
          this.logger.debug('WebSocket connection in progress');
          this.updateConnectionState(ConnectionState.CONNECTING);
        });

        this.echo.connector.pusher.connection.bind('disconnected', () => {
          this.logger.info('WebSocket connection disconnected');
          this.updateConnectionState(ConnectionState.DISCONNECTED);
        });

        this.echo.connector.pusher.connection.bind('failed', () => {
          this.logger.error('WebSocket connection failed');
          const error = new WebSocketError('Connection failed');
          this.updateConnectionState(ConnectionState.ERROR, error);
          reject(error);
        });

        this.echo.connector.pusher.connection.bind('error', (err: unknown) => {
          const error = new WebSocketError('WebSocket connection error', 'CONNECTION_ERROR', err);
          this.logger.error('WebSocket connection error', error);
          this.updateConnectionState(ConnectionState.ERROR, error);
          reject(error);
        });
      } catch (err) {
        const error = new WebSocketError(
          'Failed to initialize WebSocket connection',
          'INIT_ERROR',
          err,
        );
        this.logger.error('Failed to initialize WebSocket connection', error);
        this.updateConnectionState(ConnectionState.ERROR, error);
        reject(error);
      }
    }).catch((error) => {
      // Reset connection promise on error so we can try again
      this.logger.debug('Resetting connection promise after error');
      this.connectionPromise = null;
      throw error;
    });

    return this.connectionPromise;
  }

  /**
   * Ensures that a connection is established before proceeding
   *
   * @returns Promise that resolves when connected
   */
  private async ensureConnected(): Promise<void> {
    return this.connect();
  }

  /**
   * Subscribes to a public channel
   *
   * @param channelName - Channel name
   * @param event - Event name
   * @param callback - Callback function
   */
  public async subscribe<T>(
    channelName: string,
    event: string,
    callback: (data: T) => unknown,
  ): Promise<void> {
    this.logger.debug(`Subscribing to public channel: ${channelName}, event: ${event}`);
    await this.ensureConnected();
    this.echo?.channel(channelName).listen(event, (data: unknown) => {
      this.logger.debug(`Received event ${event} on channel ${channelName}`, data);
      callback(data as T);
    });
    this.logger.info(`Subscribed to public channel: ${channelName}, event: ${event}`);
  }

  /**
   * Subscribes to a private channel
   *
   * @param channelName - Channel name
   * @param event - Event name
   * @param callback - Callback function
   */
  public async subscribePrivate<T>(
    channelName: string,
    event: string,
    callback: (data: T) => unknown,
  ): Promise<void> {
    this.logger.debug(`Subscribing to private channel: ${channelName}, event: ${event}`);
    await this.ensureConnected();
    this.echo?.private(channelName).listen(event, (data: unknown) => {
      this.logger.debug(`Received event ${event} on private channel ${channelName}`, data);
      callback(data as T);
    });
    this.logger.info(`Subscribed to private channel: ${channelName}, event: ${event}`);
  }

  /**
   * Subscribes to a specific event on a presence channel
   *
   * @param channelName - Channel name
   * @param event - Event name
   * @param callback - Callback function
   */
  public async subscribePresence<T>(
    channelName: string,
    event: string,
    callback: (data: T) => unknown,
  ): Promise<void> {
    this.logger.debug(`Subscribing to presence channel: ${channelName}, event: ${event}`);
    await this.ensureConnected();
    this.echo?.join(channelName).listen(event, (data: unknown) => {
      this.logger.debug(`Received event ${event} on presence channel ${channelName}`, data);
      callback(data as T);
    });
    this.logger.info(`Subscribed to presence channel: ${channelName}, event: ${event}`);
  }

  /**
   * Subscribes to presence channel membership events
   *
   * @param channelName - Channel name
   * @param handlers - Presence event handlers
   */
  public async subscribePresenceHandlers(
    channelName: string,
    handlers: PresenceHandlers,
  ): Promise<void> {
    this.logger.debug(`Subscribing to presence channel with handlers: ${channelName}`);
    await this.ensureConnected();
    const channel = this.echo?.join(channelName);

    if (handlers.onListening) {
      this.logger.debug(
        `Setting up onListening handler for ${channelName}, event: ${handlers.onListening.event}`,
      );
      channel?.listen(handlers.onListening.event, (data: unknown) => {
        this.logger.debug(
          `Received event ${handlers.onListening?.event} on presence channel ${channelName}`,
          data,
        );
        handlers.onListening?.callback();
      });
    }

    if (handlers.onHere) {
      this.logger.debug(`Setting up onHere handler for ${channelName}`);
      channel?.here((members: Record<string, unknown>) => {
        this.logger.debug(`Received here event on presence channel ${channelName}`, members);
        handlers.onHere?.(members);
      });
    }

    if (handlers.onJoining) {
      this.logger.debug(`Setting up onJoining handler for ${channelName}`);
      channel?.joining((member: Record<string, unknown>) => {
        this.logger.debug(`Member joined presence channel ${channelName}`, member);
        handlers.onJoining?.(member);
      });
    }

    if (handlers.onLeaving) {
      this.logger.debug(`Setting up onLeaving handler for ${channelName}`);
      channel?.leaving((member: Record<string, unknown>) => {
        this.logger.debug(`Member left presence channel ${channelName}`, member);
        handlers.onLeaving?.(member);
      });
    }

    this.logger.info(`Subscribed to presence channel with handlers: ${channelName}`);
  }

  /**
   * Subscribes to an encrypted private channel
   *
   * @param channelName - Channel name
   * @param event - Event name
   * @param callback - Callback function
   */
  public async subscribeEncrypted<T>(
    channelName: string,
    event: string,
    callback: (data: T) => unknown,
  ): Promise<void> {
    this.logger.debug(`Subscribing to encrypted channel: ${channelName}, event: ${event}`);
    await this.ensureConnected();
    this.echo?.encryptedPrivate(channelName).listen(event, (data: unknown) => {
      this.logger.debug(`Received event ${event} on encrypted channel ${channelName}`, data);
      callback(data as T);
    });
    this.logger.info(`Subscribed to encrypted channel: ${channelName}, event: ${event}`);
  }

  /**
   * Subscribes to notifications on a private channel
   *
   * @param channelName - Channel name
   * @param callback - Callback function
   */
  public async subscribePrivateNotification<T>(
    channelName: string,
    callback: (data: T) => unknown,
  ): Promise<void> {
    this.logger.debug(`Subscribing to private notification channel: ${channelName}`);
    await this.ensureConnected();
    this.echo?.private(channelName).notification((data: unknown) => {
      this.logger.debug(`Received notification on private channel ${channelName}`, data);
      callback(data as T);
    });
    this.logger.info(`Subscribed to private notification channel: ${channelName}`);
  }

  /**
   * Subscribes to notifications on a public channel
   *
   * @param channelName - Channel name
   * @param callback - Callback function
   */
  public async subscribePublicNotification<T>(
    channelName: string,
    callback: (data: T) => unknown,
  ): Promise<void> {
    this.logger.debug(`Subscribing to public notification channel: ${channelName}`);
    await this.ensureConnected();
    this.echo?.channel(channelName).notification((data: unknown) => {
      this.logger.debug(`Received notification on public channel ${channelName}`, data);
      callback(data as T);
    });
    this.logger.info(`Subscribed to public notification channel: ${channelName}`);
  }

  /**
   * Unsubscribes from a channel
   *
   * @param channelName - Channel name
   */
  public unsubscribe(channelName: string): void {
    if (!this.echo || !channelName) {
      this.logger.debug(
        `Cannot unsubscribe from ${channelName}: Echo not initialized or channel name empty`,
      );
      return;
    }

    this.logger.debug(`Unsubscribing from channel: ${channelName}`);
    try {
      this.echo.leave(channelName);
      this.logger.info(`Unsubscribed from channel: ${channelName}`);
    } catch (error) {
      this.logger.error(`Error unsubscribing from ${channelName}`, error);
    }
  }

  /**
   * Unsubscribes from all channels
   */
  public unsubscribeAll(): void {
    if (!this.echo) {
      this.logger.debug('Cannot unsubscribe from all channels: Echo not initialized');
      return;
    }

    this.logger.debug('Unsubscribing from all channels');
    try {
      this.echo.leaveAllChannels();
      this.logger.info('Unsubscribed from all channels');
    } catch (error) {
      this.logger.error('Error unsubscribing from all channels', error);
    }
  }

  /**
   * Disconnects from the WebSocket server
   */
  public disconnect(): void {
    if (!this.echo) {
      this.logger.debug('Cannot disconnect: Echo not initialized');
      return;
    }

    this.logger.debug('Disconnecting WebSocket');
    try {
      this.echo.disconnect();
      this.echo = null;
      this.connectionPromise = null;
      this.updateConnectionState(ConnectionState.DISCONNECTED);
      this.logger.info('WebSocket disconnected');
    } catch (error) {
      this.logger.error('Error disconnecting WebSocket', error);
      this.echo = null;
      this.connectionPromise = null;
      this.updateConnectionState(ConnectionState.DISCONNECTED);
    }
  }
}
