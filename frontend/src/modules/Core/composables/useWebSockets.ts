import { SubscribeOptions } from '../types/websocket.types';
import { AnyChannel } from '../types/websocket.types';
import { useContainer } from './useContainer';

/**
 * Composable for WebSocket operations
 */
export function useWebSockets() {
  // Get the WebSocketService from the container
  const container = useContainer();
  const wsService = container.ws;

  /**
   * Subscribe to a WebSocket channel
   *
   * @param options - Channel subscription options
   * @returns The channel object
   */
  async function subscribe<T = unknown>(options: SubscribeOptions<T>): Promise<AnyChannel> {
    // Handle different channel types with proper type casting
    let channel: AnyChannel;

    if (options.type === 'public' || options.type === 'publicNotification') {
      channel = await wsService.subscribe(
        options as SubscribeOptions<T> & { type: 'public' | 'publicNotification' },
      );
    } else if (options.type === 'private' || options.type === 'privateNotification') {
      channel = await wsService.subscribe(
        options as SubscribeOptions<T> & { type: 'private' | 'privateNotification' },
      );
    } else if (options.type === 'presence') {
      channel = await wsService.subscribe(options as SubscribeOptions<T> & { type: 'presence' });
    } else if (options.type === 'encrypted') {
      channel = await wsService.subscribe(options as SubscribeOptions<T> & { type: 'encrypted' });
    } else {
      console.log(options.type);
      throw new Error(`Unsupported channel type: ${String(options.type)}`);
    }

    return channel;
  }

  /**
   * Unsubscribe from a channel
   *
   * @param channel - The channel to unsubscribe from
   */
  function unsubscribe(channel: AnyChannel): void {
    if (channel) {
      channel.unsubscribe();
    }
  }

  return {
    subscribe,
    unsubscribe,
  };
}
