import { ComputedRef, Ref } from 'vue';

// Connection states for WebSocket channels
export enum ConnectionState {
  DISCONNECTED = 'disconnected',
  CONNECTING = 'connecting',
  CONNECTED = 'connected',
  RECONNECTING = 'reconnecting',
  ERROR = 'error',
}

/**
 * Handlers for presence channel events
 */
export interface PresenceHandlers {
  /**
   * Callback when the channel is first connected
   */
  onListening?: {
    event: string;
    callback: () => void;
  };

  /**
   * Callback when the channel is first connected
   */
  onHere?: (members: Record<string, unknown>) => void;
  /**
   * Callback when a new member joins the channel
   */
  onJoining?: (member: Record<string, unknown>) => void;
  /**
   * Callback when a member leaves the channel
   */
  onLeaving?: (member: Record<string, unknown>) => void;
}

/**
 * Configuration options for WebSocket channel listener
 */
/**
 * Presence channel member data
 */
export interface PresenceMember {
  id: string | number;
  info: Record<string, unknown>;
}

/**
 * Configuration options for WebSocket channel listener
 */
export interface WebSocketListenerOptions<T = unknown> {
  /** Channel name or ref to channel name */
  channel: string | Ref<string>;

  /** Event name or ref to event name */
  event: string | Ref<string>;

  /** Callback function when data is received */
  callback: (data: T) => void;

  /** Channel type */
  type?:
    | 'private'
    | 'presence'
    | 'public'
    | 'encrypted'
    | 'publicNotification'
    | 'privateNotification';

  /** Initial connection state (does not auto-connect if false) */
  autoConnect?: boolean;

  /** Callback when connection state changes */
  onConnectionStateChange?: (state: ConnectionState) => void;

  /** Callback when an error occurs */
  onError?: (error: Error) => void;

  /** Handlers for presence channel events */
  presenceHandlers?: PresenceHandlers | undefined;

  /** Whether to automatically attempt reconnection after error */
  reconnectOnError?: boolean;

  /** Maximum number of reconnection attempts */
  maxReconnectAttempts?: number;

  /** Base delay between reconnection attempts (ms) */
  reconnectBaseDelay?: number;

  /** Whether to log debug information */
  debugMode?: boolean;

  /** Debounce time for connection state changes (ms) */
  debounceTime?: number;
}

/**
 * Return type for useWebSocketChannelListener composable
 */
export interface WebSocketListenerReturn {
  /** Current connection state */
  connectionState: Ref<ConnectionState>;

  /** Whether the channel is currently connected */
  isConnected: ComputedRef<boolean>;

  /** Last error that occurred */
  lastError: Ref<Error | null>;

  /** Number of reconnection attempts made */
  reconnectAttempts: Ref<number>;

  /** Explicitly connect to the channel */
  connect: () => void;

  /** Explicitly disconnect from the channel */
  disconnect: () => void;

  /** Reconnect to the channel (disconnect then connect) */
  reconnect: () => Promise<void>;

  /** Reset error state and reconnection counter */
  reset: () => void;
}

/**
 * Configuration for a WebSocket channel
 */
export interface ChannelConfig {
  id: string;
  name: string;
  type: 'private' | 'presence' | 'public' | 'publicNotification' | 'privateNotification';
  event: string;
  autoConnect: boolean;
  isExpanded: boolean;
  messages: Array<{ timestamp: Date; data: unknown }>;
  members?: Record<string, unknown> | undefined; // For tracking presence channel members
  presenceHandlers?: PresenceHandlers | undefined;
}
