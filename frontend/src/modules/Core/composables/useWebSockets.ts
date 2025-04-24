import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import {
  ConnectionState,
  WebSocketListenerOptions,
  WebSocketListenerReturn,
} from '../types/websocket.types';

/**
 * Composable for managing WebSocket connections with manual control
 *
 * @param options - WebSocket listener options
 * @returns WebSocket listener control object
 */
export function useWebSocketChannelListener<T = unknown>(
  options: WebSocketListenerOptions<T>,
): WebSocketListenerReturn {
  const container = useContainer();
  const ws = container.ws;

  // Reactive state
  const connectionState = ref<ConnectionState>(ConnectionState.DISCONNECTED);
  const lastError = ref<Error | null>(null);

  // Track cleanup functions
  let removeConnectionListener: (() => void) | null = null;

  /**
   * Updates the connection state
   *
   * @param state - New connection state
   * @param error - Optional error
   */
  const updateState = (state: ConnectionState, error?: Error): void => {
    connectionState.value = state;

    if (error) {
      lastError.value = error;
    } else if (state !== ConnectionState.ERROR) {
      lastError.value = null;
    }

    // Notify callback if provided
    if (options.onConnectionStateChange) {
      options.onConnectionStateChange(state);
    }

    // Notify error callback if provided and there is an error
    if (error && options.onError) {
      options.onError(error);
    }
  };

  /**
   * Gets the current channel name
   *
   * @returns The channel name
   */
  const getChannelName = (): string => {
    return typeof options.channel === 'string' ? options.channel : options.channel.value;
  };

  /**
   * Gets the current event name
   *
   * @returns The event name
   */
  const getEventName = (): string => {
    return typeof options.event === 'string' ? options.event : options.event.value;
  };

  /**
   * Connects to the WebSocket channel
   */
  const connect = async (): Promise<void> => {
    if (
      connectionState.value === ConnectionState.CONNECTED ||
      connectionState.value === ConnectionState.CONNECTING
    ) {
      return;
    }

    updateState(ConnectionState.CONNECTING);

    try {
      const channelName = getChannelName();
      const eventName = getEventName();

      if (options.debugMode) {
        console.log(
          `[WebSocket] Connecting to ${options.type || 'public'} channel: ${channelName}, event: ${eventName}`,
        );
      }

      // Setup connection state listener
      if (!removeConnectionListener) {
        removeConnectionListener = ws.addConnectionListener((state, error) => {
          if (state === ConnectionState.CONNECTED) {
            // When the global connection is established, we need to subscribe to our channel
            void subscribeToChannel();
          } else if (state === ConnectionState.ERROR) {
            updateState(ConnectionState.ERROR, error);
          }
        });
      }

      // Ensure the WebSocket service is connected
      await ws.connect();

      // Subscribe to the channel
      await subscribeToChannel();

      updateState(ConnectionState.CONNECTED);
    } catch (error) {
      updateState(ConnectionState.ERROR, error instanceof Error ? error : new Error(String(error)));
    }
  };

  /**
   * Subscribes to the channel based on the channel type
   */
  const subscribeToChannel = async (): Promise<void> => {
    const channelName = getChannelName();
    const eventName = getEventName();

    try {
      switch (options.type) {
        case 'private':
          await ws.subscribePrivate(
            channelName,
            eventName,
            options.callback as (data: unknown) => void,
          );
          break;
        case 'presence':
          if (options.presenceHandlers) {
            await ws.subscribePresenceHandlers(channelName, options.presenceHandlers);
          } else {
            await ws.subscribePresence(
              channelName,
              eventName,
              options.callback as (data: unknown) => void,
            );
          }
          break;
        case 'encrypted':
          await ws.subscribeEncrypted(
            channelName,
            eventName,
            options.callback as (data: unknown) => void,
          );
          break;
        case 'privateNotification':
          await ws.subscribePrivateNotification(
            channelName,
            options.callback as (data: unknown) => void,
          );
          break;
        case 'publicNotification':
          await ws.subscribePublicNotification(
            channelName,
            options.callback as (data: unknown) => void,
          );
          break;
        default: // public
          await ws.subscribe(channelName, eventName, options.callback as (data: unknown) => void);
          break;
      }

      if (options.debugMode) {
        console.log(
          `[WebSocket] Successfully subscribed to ${options.type || 'public'} channel: ${channelName}`,
        );
      }
    } catch (error) {
      if (options.debugMode) {
        console.error(`[WebSocket] Error subscribing to channel ${channelName}:`, error);
      }
      throw error;
    }
  };

  /**
   * Disconnects from the WebSocket channel
   */
  const disconnect = (): void => {
    const channelName = getChannelName();

    // Remove connection listener
    if (removeConnectionListener) {
      removeConnectionListener();
      removeConnectionListener = null;
    }

    // Unsubscribe from the channel
    ws.unsubscribe(channelName);

    updateState(ConnectionState.DISCONNECTED);

    if (options.debugMode) {
      console.log(`[WebSocket] Disconnected from channel: ${channelName}`);
    }
  };

  /**
   * Reconnects to the WebSocket channel
   */
  const reconnect = async (): Promise<void> => {
    disconnect();
    await connect();
  };

  // Watch for changes in channel name or event name
  if (typeof options.channel !== 'string' || typeof options.event !== 'string') {
    watch(
      () => [
        typeof options.channel === 'string' ? options.channel : options.channel.value,
        typeof options.event === 'string' ? options.event : options.event.value,
      ],
      () => {
        if (connectionState.value === ConnectionState.CONNECTED) {
          void reconnect();
        }
      },
    );
  }

  // Setup lifecycle hooks
  onMounted(() => {
    if (options.autoConnect !== false) {
      void connect();
    }
  });

  onBeforeUnmount(() => {
    disconnect();
  });

  return {
    connectionState,
    lastError,
    connect,
    disconnect,
    reconnect,
  };
}

/**
 * Composable for managing multiple WebSocket connections
 *
 * @param options - Array of WebSocket listener options
 * @returns Array of WebSocket listener control objects
 */
export function useMultipleWebSocketChannels<T = unknown>(
  options: WebSocketListenerOptions<T>[],
): WebSocketListenerReturn[] {
  return options.map((opt) => useWebSocketChannelListener(opt));
}
