import { onMounted, onUnmounted, ref, computed, watch } from 'vue';
import { useContainer } from '../composables/useContainer';
import {
  WebSocketListenerOptions,
  ConnectionState,
  WebSocketListenerReturn,
  PresenceHandlers,
} from '../types/websocket.types';

/**
 * Vue composable for WebSocket channel listening with enterprise-grade features
 */
export function useWebSocketChannelListener<T = unknown>({
  channel,
  event,
  callback,
  onConnectionStateChange,
  onError,
  presenceHandlers,
  type = 'private',
  autoConnect = true,
  reconnectOnError = true,
  maxReconnectAttempts = 5,
  reconnectBaseDelay = 2000,
  debugMode = false,
  debounceTime = 300,
}: WebSocketListenerOptions<T>): WebSocketListenerReturn {
  const container = useContainer();
  const connectionState = ref<ConnectionState>(ConnectionState.DISCONNECTED);
  const lastError = ref<Error | null>(null);
  const reconnectAttempts = ref(0);
  const connectionTimer = ref<ReturnType<typeof setTimeout> | null>(null);
  const isConnecting = ref(false);

  // Computed properties
  const isConnected = computed(() => connectionState.value === ConnectionState.CONNECTED);

  // Get reactive values safely
  const getChannel = (): string => (typeof channel === 'object' ? channel.value : channel);
  const getEvent = (): string => (typeof event === 'object' ? event.value : event);

  // Update connection state with debouncing to prevent rapid changes
  const updateConnectionState = (newState: ConnectionState): void => {
    // Clear any pending state change
    if (connectionTimer.value) {
      clearTimeout(connectionTimer.value);
      connectionTimer.value = null;
    }

    // Debounce state changes to prevent flickering
    connectionTimer.value = setTimeout(() => {
      if (connectionState.value !== newState) {
        if (debugMode) {
          console.log(`[WebSocketListener] State change: ${connectionState.value} → ${newState}`);
        }

        connectionState.value = newState;

        if (onConnectionStateChange) {
          onConnectionStateChange(newState);
        }
      }
    }, debounceTime);
  };

  // Wrapped callback with error handling
  const safeCallback = (data: unknown): void => {
    try {
      callback(data as T);
    } catch (error) {
      handleError(error instanceof Error ? error : new Error(String(error)));
    }
  };

  // Error handler
  const handleError = (error: Error): void => {
    lastError.value = error;
    updateConnectionState(ConnectionState.ERROR);

    if (debugMode) {
      console.error(`[WebSocketListener] Error:`, error);
    }

    if (onError) {
      onError(error);
    }

    // Auto-reconnect logic
    if (reconnectOnError && reconnectAttempts.value < maxReconnectAttempts) {
      updateConnectionState(ConnectionState.RECONNECTING);
      reconnectAttempts.value++;

      // Exponential backoff for reconnection attempts
      const delay = reconnectBaseDelay * Math.pow(1.5, reconnectAttempts.value - 1);

      setTimeout(() => {
        if (debugMode) {
          console.log(
            `[WebSocketListener] Reconnect attempt ${reconnectAttempts.value}/${maxReconnectAttempts} (delay: ${delay}ms)`,
          );
        }

        void connect();
      }, delay);
    }
  };

  // Connect to channel
  const connect = (): void => {
    const channelName = getChannel();
    if (!channelName || isConnecting.value || connectionState.value === ConnectionState.CONNECTED) {
      return;
    }

    try {
      isConnecting.value = true;
      updateConnectionState(ConnectionState.CONNECTING);

      if (debugMode) {
        console.log(`[WebSocketListener] Connecting to ${channelName} (${type})`);
      }

      // Subscribe to the appropriate channel type
      switch (type) {
        case 'presence':
          // For presence channels, we need to handle membership events separately
          container.ws.subscribePresence(channelName, getEvent(), safeCallback);

          // Set up presence handlers if provided
          if (presenceHandlers) {
            // Create a new object with only the defined handlers
            const handlers: PresenceHandlers = {};

            if (presenceHandlers.onHere) {
              handlers.onHere = presenceHandlers.onHere;
            }

            if (presenceHandlers.onJoining) {
              handlers.onJoining = presenceHandlers.onJoining;
            }

            if (presenceHandlers.onLeaving) {
              handlers.onLeaving = presenceHandlers.onLeaving;
            }

            container.ws.subscribePresenceHandlers(channelName, handlers);
          }
          break;
        case 'encrypted':
          container.ws.subscribeEncrypted(channelName, getEvent(), safeCallback);
          break;
        case 'public':
          container.ws.subscribe(channelName, getEvent(), safeCallback);
          break;
        case 'publicNotification':
          container.ws.subscribePublicNotification(channelName, safeCallback);
          break;
        case 'privateNotification':
          container.ws.subscribePrivateNotification(channelName, safeCallback);
          break;
        default:
          container.ws.subscribePrivate(channelName, getEvent(), safeCallback);
      }

      updateConnectionState(ConnectionState.CONNECTED);
    } catch (error) {
      handleError(error instanceof Error ? error : new Error(String(error)));
    } finally {
      isConnecting.value = false;
    }
  };

  // Disconnect from channel
  const disconnect = (): void => {
    const channelName = getChannel();
    if (!channelName || connectionState.value === ConnectionState.DISCONNECTED) {
      return;
    }

    try {
      if (debugMode) {
        console.log(`[WebSocketListener] Disconnecting from ${channelName}`);
      }

      // Properly unsubscribe from the specific channel
      container.ws?.unsubscribe(channelName);
      updateConnectionState(ConnectionState.DISCONNECTED);
    } catch (error) {
      if (debugMode) {
        console.error(`[WebSocketListener] Error disconnecting:`, error);
      }
    }
  };

  // Reconnect (disconnect and then connect)
  const reconnect = async (): Promise<void> => {
    disconnect();
    // Small delay to ensure disconnect completes
    await new Promise((resolve) => setTimeout(resolve, 100));
    return connect();
  };

  // Reset error state and reconnection counter
  const reset = (): void => {
    reconnectAttempts.value = 0;
    lastError.value = null;

    if (connectionState.value === ConnectionState.ERROR) {
      updateConnectionState(ConnectionState.DISCONNECTED);
    }
  };

  // Watch for changes in channel
  if (typeof channel === 'object') {
    watch(
      () => getChannel(),
      (newChannel, oldChannel) => {
        if (newChannel !== oldChannel) {
          if (connectionState.value === ConnectionState.CONNECTED) {
            // Channel changed while connected, reconnect to new channel
            void reconnect();
          } else if (autoConnect && connectionState.value === ConnectionState.DISCONNECTED) {
            // Not connected but should auto-connect to new channel
            void connect();
          }
        }
      },
    );
  }

  // Auto-connect on mount if specified
  onMounted(() => {
    if (autoConnect) {
      void connect();
    }
  });

  // Clean up on unmount
  onUnmounted(() => {
    disconnect();

    // Clear any pending timers
    if (connectionTimer.value) {
      clearTimeout(connectionTimer.value);
    }
  });

  // Return public API
  return {
    connectionState,
    isConnected,
    lastError,
    reconnectAttempts,
    connect,
    disconnect,
    reconnect,
    reset,
  };
}
