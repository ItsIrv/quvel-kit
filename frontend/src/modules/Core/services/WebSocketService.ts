import type { Channel } from 'pusher-js';
import Echo from 'laravel-echo';
import { RegisterService, SsrAwareService } from 'src/modules/Core/types/service.types';
import {
  EncryptedChannelType,
  PresenceChannelType,
  PrivateChannelType,
  PublicChannelType,
  SubscribeOptions,
} from '../types/websocket.types';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { ApiService } from 'src/modules/Core/services/ApiService';

import { Service } from './Service';

/**
 * Service responsible for handling WebSocket connections.
 */
export class WebSocketService extends Service implements SsrAwareService, RegisterService {
  #echo: Echo<'pusher'> | null = null;
  private api!: ApiService;
  private connectionPromise: Promise<void> | null = null;
  private isConnected = false;
  private apiKey!: string;
  private cluster!: string;
  private readonly isSsr: boolean = typeof window === 'undefined';
  get echo() {
    return this.#echo;
  }

  /**
   * Boot method to initialize with config.
   */
  public boot(): void {}

  public register(container: ServiceContainer): void {
    this.api = container.api;
    this.apiKey = container.config.get('pusherAppKey') ?? import.meta.env.VITE_PUSHER_APP_KEY;
    this.cluster =
      container.config.get('pusherAppCluster') ?? import.meta.env.VITE_PUSHER_APP_CLUSTER;
  }

  public connect(): Promise<void> {
    if (this.isConnected || this.isSsr) return Promise.resolve();
    if (this.connectionPromise) return this.connectionPromise;

    this.connectionPromise = new Promise<void>((resolve, reject) => {
      void import('pusher-js').then((Pusher) => {
        if (!this.isSsr && !(window as unknown as { Pusher: typeof Pusher }).Pusher) {
          (window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher;
        }

        this.#echo = new Echo({
          broadcaster: 'pusher',
          key: this.apiKey,
          cluster: this.cluster,
          authorizer: (channel: Channel) => {
            const apiService = this.api;
            return {
              authorize: async (socketId: string, callback: (b: boolean, d: unknown) => void) => {
                try {
                  const data = await apiService.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name,
                  });

                  return callback(false, data);
                } catch {
                  return callback(true, null);
                }
              },
            };
          },
        });

        this.#echo.connector.pusher.connection.bind('connected', () => {
          this.isConnected = true;
          this.connectionPromise = null;
          resolve();
        });

        this.#echo.connector.pusher.connection.bind('disconnected', () => {
          this.isConnected = false;
          this.connectionPromise = null;
        });

        this.#echo.connector.pusher.connection.bind('error', (err: unknown) => {
          console.error('[WebSocket Error]', err);
          reject(new Error('WebSocket connection error'));
        });
      });
    });
    return this.connectionPromise;
  }

  /**
   * Unified subscription method for all channel types
   */
  public async subscribe<T = unknown>(
    options: SubscribeOptions<T> & { type: 'public' | 'publicNotification' },
  ): Promise<PublicChannelType>;
  public async subscribe<T = unknown>(
    options: SubscribeOptions<T> & { type: 'private' | 'privateNotification' },
  ): Promise<PrivateChannelType>;
  public async subscribe<T = unknown>(
    options: SubscribeOptions<T> & { type: 'presence' },
  ): Promise<PresenceChannelType>;
  public async subscribe<T = unknown>(
    options: SubscribeOptions<T> & { type: 'encrypted' },
  ): Promise<EncryptedChannelType>;
  public async subscribe<T = unknown>(
    options: SubscribeOptions<T>,
  ): Promise<
    false | PublicChannelType | PrivateChannelType | PresenceChannelType | EncryptedChannelType
  > {
    if (this.isSsr) return false;

    await this.connect();

    if (!this.#echo) return false;

    const { channelName, type, event, callback, presenceHandlers } = options;

    let channel:
      | PublicChannelType
      | PrivateChannelType
      | PresenceChannelType
      | EncryptedChannelType;

    switch (type) {
      case 'public':
      case 'publicNotification':
        channel = this.#echo.channel(channelName);
        break;
      case 'private':
      case 'privateNotification':
        channel = this.#echo.private(channelName);
        break;
      case 'presence':
        channel = this.#echo.join(channelName);
        break;
      case 'encrypted':
        channel = this.#echo.encryptedPrivate(channelName);
        break;
      default:
        throw new Error('Unknown channel type');
    }

    if (type.endsWith('Notification')) {
      return channel.notification(callback);
    }

    if (event) {
      channel.listen(event, callback);
    }

    if (type === 'presence' && presenceHandlers) {
      if (presenceHandlers.onListening) {
        channel.listen(presenceHandlers.onListening.event, presenceHandlers.onListening.callback);
      }
      if (presenceHandlers.onHere) {
        (channel as PresenceChannelType).here((members: Record<string, unknown>) =>
          presenceHandlers.onHere?.(members),
        );
      }
      if (presenceHandlers.onJoining) {
        (channel as PresenceChannelType).joining((member: Record<string, unknown>) =>
          presenceHandlers.onJoining?.(member),
        );
      }
      if (presenceHandlers.onLeaving) {
        (channel as PresenceChannelType).leaving((member: Record<string, unknown>) =>
          presenceHandlers.onLeaving?.(member),
        );
      }
    }

    return channel;
  }

  public disconnect() {
    this.#echo?.disconnect();
    this.#echo = null;
    this.connectionPromise = null;
    this.isConnected = false;
  }
}
