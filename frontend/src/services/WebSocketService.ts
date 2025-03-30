import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

export class WebSocketService {
  private echo: Echo<'pusher'> | null = null;
  private isConnected = false;

  private readonly apiKey: string;
  private readonly cluster: string;
  private readonly apiUrl: string;

  constructor({ apiKey, cluster, apiUrl }: { apiKey: string; cluster: string; apiUrl: string }) {
    this.apiKey = apiKey;
    this.cluster = cluster;
    this.apiUrl = apiUrl;

    if (typeof window !== 'undefined' && !window.Pusher) {
      window.Pusher = Pusher;
    }
  }

  /**
   * Manually connect to WebSockets.
   */
  public connect(): void {
    if (this.isConnected || this.echo) return;

    this.echo = new Echo({
      broadcaster: 'pusher',
      key: this.apiKey,
      cluster: this.cluster,
      forceTLS: true,
      encrypted: true,
      disableStats: true,
      authEndpoint: `${this.apiUrl}/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('access_token') || ''}`,
        },
      },
    });

    this.isConnected = true;
  }

  /**
   * Automatically connect if not already.
   */
  private ensureConnected(): void {
    if (!this.isConnected) {
      this.connect();
    }
  }

  /**
   * Subscribe to an event on a public channel.
   */
  public subscribe<T>(channelName: string, event: string, callback: (data: T) => unknown) {
    this.ensureConnected();
    return this.echo?.channel(channelName).listen(event, callback) || null;
  }

  public subscribePrivate(
    channelName: string,
    event: string,
    callback: (data: unknown) => unknown,
  ) {
    this.ensureConnected();
    return this.echo?.private(channelName).listen(event, callback) || null;
  }

  public subscribePresence(channelName: string, callback: (data: unknown) => unknown) {
    this.ensureConnected();
    return this.echo?.join(channelName).listen('.presence', callback) || null;
  }

  public subscribeEncrypted(
    channelName: string,
    event: string,
    callback: (data: unknown) => unknown,
  ) {
    this.ensureConnected();
    return this.echo?.encryptedPrivate(channelName).listen(event, callback) || null;
  }

  public getSocketId(): string | undefined {
    return this.echo?.socketId();
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
    this.isConnected = false;
  }
}
