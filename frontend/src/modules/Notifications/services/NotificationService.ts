import { Service } from 'src/modules/Core/services/Service';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { RegisterService } from 'src/modules/Core/types/service.types';
import type { INotification } from 'src/modules/Notifications/types/notification.types';
import type { UnsubscribeFn } from 'src/modules/Core/types/websocket.types';
import { WebSocketService } from 'src/modules/Core/services/WebSocketService';
import { Notification } from 'src/modules/Notifications/models/Notification';
import { ConfigService } from 'src/modules/Core/services/ConfigService';

export class NotificationService extends Service implements RegisterService {
  private api!: ApiService;
  private ws!: WebSocketService;
  private config!: ConfigService;
  private isSsr = typeof window === 'undefined';

  register({
    api,
    ws,
    config,
  }: {
    api: ApiService;
    ws: WebSocketService;
    config: ConfigService;
  }): void {
    this.api = api;
    this.ws = ws;
    this.config = config;
  }

  async getNotifications() {
    return this.api.get<{ data: INotification[] }>('/notifications');
  }

  async markAllAsRead() {
    await this.api.post('/notifications/mark-all-read');
  }

  async subscribeToSocket(
    userId: number,
    push: (n: Notification) => void,
  ): Promise<UnsubscribeFn | null> {
    if (this.isSsr) {
      return null;
    }

    const channelName = `tenant.${this.config.get('tenantId')}.User.${userId}`;

    const channel = await this.ws.subscribe({
      channelName,
      type: 'privateNotification',
      callback: (raw: INotification) => {
        push(Notification.fromApi(raw));
      },
    });

    return {
      unsubscribe: () => channel.unsubscribe(),
    };
  }
}
