import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

export class WebSocketService {
  private echo!: Echo<'pusher'>;

  constructor({ apiKey, cluster, apiUrl }: { apiKey: string; cluster: string; apiUrl: string }) {
    if (typeof window !== 'undefined') {
      this.setUpEcho(apiKey, cluster, apiUrl);
    }
  }

  private setUpEcho(apiKey: string, cluster: string, apiUrl: string) {
    if (!window.Pusher) {
      window.Pusher = Pusher;
    }

    this.echo = new Echo({
      broadcaster: 'pusher',
      key: apiKey,
      cluster,
      forceTLS: true,
      encrypted: true,
      disableStats: true,
      authEndpoint: `${apiUrl}/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('access_token') || ''}`,
        },
      },
    });
  }

  /**
   * Subscribe to an event on a given public channel.
   */
  subscribe(channelName: string, event: string, callback: (data: unknown) => unknown) {
    return this.echo?.channel(channelName).listen(event, callback) || null;
  }

  /**
   * Subscribe to a private channel.
   */
  subscribePrivate(channelName: string, event: string, callback: (data: unknown) => unknown) {
    return this.echo?.private(channelName).listen(event, callback) || null;
  }

  /**
   * Subscribe to a presence channel.
   */
  subscribePresence(channelName: string, callback: (data: unknown) => unknown) {
    return this.echo?.join(channelName).listen('.presence', callback) || null;
  }

  /**
   * Subscribe to an encrypted private channel.
   */
  subscribeEncrypted(channelName: string, event: string, callback: (data: unknown) => unknown) {
    return this.echo?.encryptedPrivate(channelName).listen(event, callback) || null;
  }

  /**
   * Get the socket ID.
   */
  getSocketId(): string | undefined {
    return this.echo?.socketId();
  }

  /**
   * Unsubscribe from a channel.
   */
  unsubscribe(channelName: string) {
    this.echo?.leave(channelName);
  }

  /**
   * Unsubscribe from all channels.
   */
  unsubscribeAll() {
    this.echo?.leaveAllChannels();
  }

  /**
   * Disconnect from WebSockets.
   */
  disconnect() {
    this.echo?.disconnect();
  }
}
