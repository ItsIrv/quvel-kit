import { onMounted, onUnmounted } from 'vue';
import { useContainer } from '../composables/useContainer';

interface WebSocketListenerOptions {
  channel: string;
  event: string;
  callback: (data: unknown) => void;
  type?: 'private' | 'presence' | 'public' | 'encrypted';
}

export function useWebSocketChannelListener({
  channel,
  event,
  callback,
  type = 'private',
}: WebSocketListenerOptions) {
  const container = useContainer();

  onMounted(() => {
    if (!container.ws) return;

    switch (type) {
      case 'presence':
        container.ws.subscribePresence(channel, callback);
        break;
      case 'encrypted':
        container.ws.subscribeEncrypted(channel, event, callback);
        break;
      case 'public':
        container.ws.subscribe(channel, event, callback);
        break;
      case 'private':
      default:
        container.ws.subscribePrivate(channel, event, callback);
    }
  });

  onUnmounted(() => {
    container.ws?.unsubscribe(channel);
  });
}
