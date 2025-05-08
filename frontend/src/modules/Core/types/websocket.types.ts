import Echo from 'laravel-echo';

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

export type WebSocketChannelType =
  | 'public'
  | 'private'
  | 'presence'
  | 'encrypted'
  | 'privateNotification'
  | 'publicNotification';

export interface SubscribeOptions<T = unknown> {
  channelName: string;
  type: WebSocketChannelType;
  event?: string;
  callback: (data: T) => unknown;
  presenceHandlers?: PresenceHandlers;
}

export type PublicChannelType = ReturnType<Echo<'pusher'>['channel']>;
export type PrivateChannelType = ReturnType<Echo<'pusher'>['private']>;
export type PresenceChannelType = ReturnType<Echo<'pusher'>['join']>;
export type EncryptedChannelType = ReturnType<Echo<'pusher'>['encryptedPrivate']>;
export type AnyChannel =
  | PublicChannelType
  | PrivateChannelType
  | PresenceChannelType
  | EncryptedChannelType;

export type UnsubscribeFn = {
  unsubscribe: () => void;
};
