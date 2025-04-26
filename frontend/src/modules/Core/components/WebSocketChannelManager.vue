<script lang="ts">
import { defineComponent, ref, computed, onBeforeUnmount, nextTick, onMounted } from 'vue';
import { useWebSockets } from '../composables/useWebSockets';
import { WebSocketChannelType, SubscribeOptions } from '../types/websocket.types';
import { PublicChannelType, PrivateChannelType, PresenceChannelType, EncryptedChannelType } from '../types/websocket.types';
import { QScrollArea } from 'quasar';

// Extend Window interface to avoid TypeScript errors
declare global {
  interface Window {
    showWebSocketManager: () => void;
    hideWebSocketManager: () => void;
  }
}

type AnyChannel = PublicChannelType | PrivateChannelType | PresenceChannelType | EncryptedChannelType;

interface ChannelConfig {
  name: string;
  type: WebSocketChannelType;
  event?: string | undefined;
  channel: AnyChannel;
}

interface MessageLog {
  channelName: string;
  channelType: WebSocketChannelType;
  event: string;
  data: unknown;
  timestamp: Date;
}

export default defineComponent({
  name: 'WebSocketChannelManager',

  setup() {
    // WebSocket connection management
    const { subscribe, unsubscribe } = useWebSockets();

    // Dialog visibility control
    const isDialogOpen = ref(false);
    const isVisible = computed(() => isDialogOpen.value);

    // Selected channel for filtering messages
    const selectedChannelIndex = ref<number | null>(null);

    // Register global methods to show/hide the component
    onMounted(() => {
      window.showWebSocketManager = () => {
        isDialogOpen.value = true;
        console.log('WebSocket Inspector is now open');
      };

      window.hideWebSocketManager = () => {
        hideManager();
      };

      // Log instructions to console
      console.info(
        '%cWebSocket Inspector available!',
        'color: #4CAF50; font-weight: bold; font-size: 14px;'
      );
      console.info(
        '%cUse window.showWebSocketManager() to show the inspector',
        'color: #2196F3; font-size: 12px;'
      );
    });

    // Hide the manager and clean up
    const hideManager = () => {
      isDialogOpen.value = false;
      console.log('WebSocket Inspector is now closed');
    };

    // Select a channel to filter messages
    const selectChannel = (index: number) => {
      selectedChannelIndex.value = selectedChannelIndex.value === index ? null : index;
    };

    // Active channels
    const activeChannels = ref<ChannelConfig[]>([]);

    // Connection status
    const connectionStatus = computed(() => {
      const isConnected = activeChannels.value.length > 0;
      return {
        label: isConnected ? 'Connected' : 'Disconnected',
        color: isConnected ? 'positive' : 'negative'
      };
    });

    // Channel form
    // Channel form with partial type to allow initialization without a channel
    const newChannel = ref<Omit<ChannelConfig, 'channel'> & { channel?: AnyChannel }>({
      name: '',
      type: 'public',
      event: ''
    });

    const isAddingChannel = ref(false);

    // Available channel types
    const channelTypes = [
      { label: 'Public', value: 'public' },
      { label: 'Private', value: 'private' },
      { label: 'Presence', value: 'presence' },
      { label: 'Encrypted', value: 'encrypted' },
      { label: 'Public Notification', value: 'publicNotification' },
      { label: 'Private Notification', value: 'privateNotification' }
    ];

    // Message log
    const messages = ref<MessageLog[]>([]);
    const messageLogRef = ref<QScrollArea | null>(null);

    // Filtered messages based on selected channel
    const filteredMessages = computed(() => {
      if (selectedChannelIndex.value === null) {
        return messages.value;
      }

      const selectedChannel = activeChannels.value[selectedChannelIndex.value];
      return messages.value.filter(msg =>
        msg.channelName === selectedChannel?.name &&
        msg.channelType === selectedChannel?.type
      );
    });

    // Add a new channel
    const addChannel = async () => {
      try {
        isAddingChannel.value = true;

        // Create channel options
        const options: SubscribeOptions<unknown> = {
          channelName: newChannel.value.name,
          type: newChannel.value.type,
          // Only include event if it's defined
          ...(newChannel.value.event ? { event: newChannel.value.event } : {}),
          callback: (data: unknown) => {
            // Log the message
            messages.value.push({
              channelName: newChannel.value.name,
              channelType: newChannel.value.type,
              event: newChannel.value.event || 'notification',
              data,
              timestamp: new Date(),
            });

            // Scroll to bottom of message log
            nextTick(() => {
              if (messageLogRef.value) {
                messageLogRef.value.setScrollPosition('vertical', 999999);
              }
            });
          },
          presenceHandlers: {
            onListening: {
              event: 'listening',
              callback: () => {
                messages.value.push({
                  channelName: newChannel.value.name,
                  channelType: newChannel.value.type,
                  event: newChannel.value.event || 'notification',
                  data: 'Listening',
                  timestamp: new Date(),
                });
              },
            },
            onHere: (members: Record<string, unknown>) => {
              messages.value.push({
                channelName: newChannel.value.name,
                channelType: newChannel.value.type,
                event: newChannel.value.event || 'notification',
                data: members,
                timestamp: new Date(),
              });
            },
            onJoining: (member: Record<string, unknown>) => {
              messages.value.push({
                channelName: newChannel.value.name,
                channelType: newChannel.value.type,
                event: newChannel.value.event || 'notification',
                data: member,
                timestamp: new Date(),
              });
            },
            onLeaving: (member: Record<string, unknown>) => {
              messages.value.push({
                channelName: newChannel.value.name,
                channelType: newChannel.value.type,
                event: newChannel.value.event || 'notification',
                data: member,
                timestamp: new Date(),
              });
            },
          },
        };

        // Subscribe to the channel
        const channel = await subscribe(options);

        // Add to active channels
        activeChannels.value.push({
          name: newChannel.value.name,
          type: newChannel.value.type,
          event: newChannel.value.event || undefined,
          channel
        });

        // Reset form
        newChannel.value = {
          name: '',
          type: 'public',
          event: ''
        };
      } catch (error) {
        console.error('Failed to add channel:', error);
      } finally {
        isAddingChannel.value = false;
      }
    };

    // Remove a channel
    const removeChannel = (index: number) => {
      const channel = activeChannels.value[index];
      if (channel?.channel) {
        unsubscribe(channel.channel as AnyChannel);
      }

      activeChannels.value.splice(index, 1);
    };

    // Clear message log
    const clearMessages = () => {
      messages.value = [];
    };

    // Download logs as JSON
    const downloadLogs = () => {
      const data = JSON.stringify(messages.value, null, 2);
      const blob = new Blob([data], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `websocket-logs-${new Date().toISOString()}.json`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    };

    // Format timestamp
    const formatTimestamp = (date: Date) => {
      return date.toLocaleTimeString();
    };

    // Format message data for display
    const formatMessageData = (data: unknown) => {
      try {
        return typeof data === 'object' ? JSON.stringify(data, null, 2) : String(data);
      } catch {
        return String(data);
      }
    };

    // Get color for channel type
    const getChannelTypeColor = (type: WebSocketChannelType) => {
      switch (type) {
        case 'public':
        case 'publicNotification':
          return 'teal';
        case 'private':
        case 'privateNotification':
          return 'blue';
        case 'presence':
          return 'purple';
        case 'encrypted':
          return 'deep-orange';
        default:
          return 'grey';
      }
    };

    // Clean up all channels when component unmounts
    onBeforeUnmount(() => {
      activeChannels.value.forEach(channelConfig => {
        unsubscribe(channelConfig.channel as AnyChannel);
      });

      activeChannels.value = [];
    });

    return {
      // Visibility control
      isVisible,
      isDialogOpen,
      hideManager,

      // Connection
      connectionStatus,

      // Channel form
      newChannel,
      channelTypes,
      isAddingChannel,
      addChannel,

      // Active channels
      activeChannels,
      removeChannel,
      getChannelTypeColor,
      selectedChannelIndex,
      selectChannel,

      // Message log
      messages,
      filteredMessages,
      messageLogRef,
      clearMessages,
      downloadLogs,
      formatTimestamp,
      formatMessageData
    };
  }
});
</script>

<template>
  <q-dialog
    v-model="isDialogOpen"
    persistent
    maximized
    transition-show="slide-up"
    transition-hide="slide-down"
  >
    <q-card class="websocket-channel-manager">
      <q-card-section class="bg-dark text-white q-py-sm">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <q-icon
              name="eva-wifi-outline"
              size="md"
              class="q-mr-sm"
            />
            <h5 class="text-h6 q-my-none">WebSocket Inspector</h5>
          </div>
          <q-chip
            :color="connectionStatus.color"
            text-color="white"
            size="sm"
            outline
          >
            {{ connectionStatus.label }}
          </q-chip>

          <q-btn
            flat
            round
            color="grey-5"
            icon="eva-close-outline"
            @click="hideManager"
          />
        </div>
      </q-card-section>

      <q-card-section class="q-pa-md bg-dark-subtle">
        <q-form
          @submit="addChannel"
          class="row q-col-gutter-sm items-end"
        >
          <div class="col-12 col-sm-4">
            <q-input
              v-model="newChannel.name"
              label="Channel Name"
              dense
              filled
              dark
              class="bg-dark-page"
              :rules="[val => !!val || 'Required']"
            >
              <template v-slot:prepend>
                <q-icon name="eva-hash-outline" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-sm-3">
            <q-select
              v-model="newChannel.type"
              :options="channelTypes"
              :emit-value="true"
              label="Channel Type"
              dense
              filled
              dark
              class="bg-dark-page"
              :rules="[val => !!val || 'Required']"
            >
              <template v-slot:prepend>
                <q-icon name="eva-eye-outline" />
              </template>
            </q-select>
          </div>
          <div class="col-12 col-sm-3">
            <q-input
              v-model="newChannel.event"
              label="Event Name"
              dense
              filled
              dark
              class="bg-dark-page"
              :rules="[val => newChannel.type !== 'presence' ? !!val || 'Required' : true]"
              :disable="newChannel.type === 'presence'"
            >
              <template v-slot:prepend>
                <q-icon name="eva-bell-outline" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-sm-2">
            <q-btn
              type="submit"
              color="primary"
              icon="eva-plus-outline"
              label="Subscribe"
              no-caps
              unelevated
              class="full-width"
              :loading="isAddingChannel"
            />
          </div>
        </q-form>
      </q-card-section>

      <q-separator dark />

      <q-card-section class="q-pa-none">
        <div class="row">
          <div class="col-12 col-md-3 bg-dark-subtle">
            <q-card-section class="q-py-sm">
              <div class="text-subtitle1 text-weight-medium flex items-center">
                <q-icon
                  name="eva-hash-outline"
                  class="q-mr-xs"
                />
                Active Channels
                <q-badge
                  color="primary"
                  class="q-ml-sm"
                >{{ activeChannels.length }}</q-badge>
              </div>
            </q-card-section>

            <q-separator dark />

            <q-list
              dark
              dense
              class="channel-list"
            >
              <q-item
                v-if="activeChannels.length === 0"
                class="text-grey-6"
              >
                <q-item-section>
                  <q-item-label>No active channels</q-item-label>
                  <q-item-label caption>Add a channel to start listening</q-item-label>
                </q-item-section>
              </q-item>

              <q-item
                v-for="(channel, index) in activeChannels"
                :key="index"
                clickable
                active-class="bg-primary text-white"
                :active="selectedChannelIndex === index"
                @click="selectChannel(index)"
              >
                <q-item-section avatar>
                  <q-icon
                    :color="getChannelTypeColor(channel.type)"
                    name="eva-radio-outline"
                  />
                </q-item-section>

                <q-item-section>
                  <q-item-label lines="1">{{ channel.name }}</q-item-label>
                  <q-item-label caption>
                    <q-badge
                      :color="getChannelTypeColor(channel.type)"
                      text-color="white"
                      size="xs"
                    >
                      {{ channel.type }}
                    </q-badge>
                    <span
                      v-if="channel.event"
                      class="q-ml-xs"
                    >{{ channel.event }}</span>
                  </q-item-label>
                </q-item-section>

                <q-item-section side>
                  <q-btn
                    flat
                    round
                    dense
                    color="negative"
                    icon="eva-close-outline"
                    @click.stop="removeChannel(index)"
                  />
                </q-item-section>
              </q-item>
            </q-list>
          </div>

          <div class="col-12 col-md-9">
            <q-card-section class="q-py-sm bg-dark-subtle">
              <div class="row items-center justify-between">
                <div class="text-subtitle1 text-weight-medium flex items-center">
                  <q-icon
                    name="eva-message-square-outline"
                    class="q-mr-xs"
                  />
                  Message Log
                  <q-badge
                    color="secondary"
                    class="q-ml-sm"
                  >{{ filteredMessages.length }}</q-badge>
                </div>

                <div>
                  <q-btn
                    flat
                    round
                    dense
                    color="grey"
                    icon="eva-refresh-outline"
                    @click="clearMessages"
                    class="q-mr-xs"
                  >
                    <q-tooltip>Clear Messages</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    round
                    dense
                    color="grey"
                    icon="eva-download-outline"
                    @click="downloadLogs"
                  >
                    <q-tooltip>Download Logs</q-tooltip>
                  </q-btn>
                </div>
              </div>
            </q-card-section>

            <q-separator dark />

            <q-scroll-area
              style="height: calc(100vh - 280px);"
              ref="messageLogRef"
              class="bg-dark-page"
            >
              <div
                v-if="filteredMessages.length === 0"
                class="text-center q-pa-md text-grey-7"
              >
                <q-icon
                  name="eva-message-square-outline"
                  size="2rem"
                />
                <div class="q-mt-sm">No messages received yet.</div>
              </div>

              <div
                v-for="(message, index) in filteredMessages"
                :key="index"
                class="message-item q-pa-md q-my-sm"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <q-badge
                      :color="getChannelTypeColor(message.channelType)"
                      class="q-mr-sm"
                    >{{ message.channelType
                    }}</q-badge>
                    <div class="text-weight-medium">{{ message.channelName }}</div>
                  </div>
                  <div class="text-grey-7 text-caption">{{ formatTimestamp(message.timestamp) }}</div>
                </div>

                <div class="q-mt-xs">
                  <q-chip
                    size="sm"
                    outline
                    color="secondary"
                    class="q-mr-sm"
                  >{{ message.event }}</q-chip>
                </div>

                <q-card
                  flat
                  bordered
                  class="q-mt-sm bg-dark-subtle"
                >
                  <q-card-section class="q-pa-sm">
                    <pre class="message-content text-grey-4">{{ formatMessageData(message.data) }}</pre>
                  </q-card-section>
                </q-card>
              </div>
            </q-scroll-area>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<style lang="scss">
.websocket-channel-manager {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  background-color: #1e1e1e;
  color: #e0e0e0;

  .channel-list {
    max-height: 300px;
    overflow-y: auto;
  }

  .message-content {
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-word;
  }
}
</style>
