import { Ref } from 'vue';

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

  /** Whether to log debug information */
  debugMode?: boolean;
}

/**
 * Return type for useWebSockets composable
 */
export interface WebSocketListenerReturn {
  /** Current connection state */
  connectionState: Ref<ConnectionState>;

  /** Last error that occurred */
  lastError: Ref<Error | null>;

  /** Explicitly connect to the channel */
  connect: () => Promise<void>;

  /** Explicitly disconnect from the channel */
  disconnect: () => void;

  /** Reconnect to the channel (disconnect then connect) */
  reconnect: () => Promise<void>;
}
