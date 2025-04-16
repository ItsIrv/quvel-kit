import Echo from 'laravel-echo';
import Pusher, { Channel } from 'pusher-js';
import { BootableService } from 'src/modules/Core/types/service.types';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { ApiService } from 'src/modules/Core/services/ApiService';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

export class WebSocketService implements BootableService {
  private api: ApiService | null = null;
  private echo: Echo<'pusher'> | null = null;
  private connectionPromise: Promise<void> | null = null;
  private isConnected = false;

  private readonly apiKey: string;
  private readonly cluster: string;

  private queuedSubscriptions: (() => void)[] = [];

  constructor({ apiKey, cluster }: { apiKey: string; cluster: string; apiUrl: string }) {
    this.apiKey = apiKey;
    this.cluster = cluster;

    if (typeof window !== 'undefined' && !window.Pusher) {
      window.Pusher = Pusher;
    }
  }

  public register(container: ServiceContainer): void {
    this.api = container.api;
  }

  public boot(): void {}

  public connect(): Promise<void> {
    if (typeof window === 'undefined') {
      return Promise.resolve();
    }

    if (this.isConnected) return Promise.resolve();

    if (this.connectionPromise) return this.connectionPromise;

    this.connectionPromise = new Promise<void>((resolve, reject) => {
      this.echo = new Echo({
        broadcaster: 'pusher',
        key: this.apiKey,
        cluster: this.cluster,
        authorizer: (channel: Channel) => {
          const apiService = this.api as ApiService;

          return {
            authorize: async (socketId: string, callback: (b: boolean, d: unknown) => void) => {
              const data = await apiService.post('/broadcasting/auth', {
                socket_id: socketId,
                channel_name: channel.name,
              });

              return callback(false, data);
            },
          };
        },
      });

      this.echo.connector.pusher.connection.bind('connected', () => {
        this.isConnected = true;
        resolve();

        this.queuedSubscriptions.forEach((fn) => fn());
        this.queuedSubscriptions = [];
      });

      this.echo.connector.pusher.connection.bind('error', (err: unknown) => {
        console.error('[WebSocket Error]', err);
        reject(new Error('WebSocket connection error'));
      });
    });

    return this.connectionPromise;
  }

  private ensureConnected(): Promise<void> {
    return this.connect();
  }

  private queueOrExecute(subscriptionFn: () => void) {
    if (!this.isConnected) {
      this.queuedSubscriptions.push(subscriptionFn);
    } else {
      subscriptionFn();
    }
  }

  public subscribe<T>(channelName: string, event: string, callback: (data: T) => unknown) {
    void this.ensureConnected();
    this.queueOrExecute(() => {
      this.echo?.channel(channelName).listen(event, callback);
    });
  }

  public subscribePrivate<T>(channelName: string, event: string, callback: (data: T) => unknown) {
    void this.ensureConnected();
    this.queueOrExecute(() => {
      this.echo?.private(channelName).listen(event, callback);
    });
  }

  public subscribePresence<T>(channelName: string, callback: (data: T) => unknown) {
    void this.ensureConnected();
    this.queueOrExecute(() => {
      this.echo?.join(channelName).listen('.presence', callback);
    });
  }

  public subscribeEncrypted<T>(channelName: string, event: string, callback: (data: T) => unknown) {
    void this.ensureConnected();
    this.queueOrExecute(() => {
      this.echo?.encryptedPrivate(channelName).listen(event, callback);
    });
  }

  public subscribePrivateNotification<T>(channelName: string, callback: (data: T) => unknown) {
    void this.ensureConnected();
    this.queueOrExecute(() => {
      this.echo?.private(channelName).notification(callback);
    });
  }

  public unsubscribe(channelName: string) {
    this.echo?.leave(channelName);
  }

  public unsubscribeAll() {
    this.echo?.leaveAllChannels();
  }

  public disconnect() {
    this.echo?.disconnect();
    this.echo = null;
    this.connectionPromise = null;
    this.isConnected = false;
    this.queuedSubscriptions = [];
  }
}
