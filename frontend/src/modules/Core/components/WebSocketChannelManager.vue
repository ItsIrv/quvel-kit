<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useWebSocketChannelListener } from 'src/modules/Core/composables/useWebSockets';
import { ConnectionState, WebSocketListenerOptions, PresenceHandlers } from 'src/modules/Core/types/websocket.types';
import { date } from 'quasar';
import { useContainer } from '../composables/useContainer';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';

const container = useContainer();
const sessionStore = useSessionStore();

interface ChannelConfig {
  name: string;
  type: 'public' | 'private' | 'presence' | 'encrypted';
  channel: string;
  event: string;
  autoConnect?: boolean;
  debugMode?: boolean;
  presenceHandlers?: PresenceHandlers;
}

interface ChannelState {
  connectionState: ConnectionState;
  lastError: Error | null;
  connect: () => Promise<void>;
  disconnect: () => void;
  reconnect: () => Promise<void>;
}

interface ChannelMessage {
  timestamp: Date;
  data: unknown;
}

// Channel configurations
const channels = ref<ChannelConfig[]>([]);
const channelStates = ref<(ChannelState | null)[]>([]);
const channelMessages = ref<ChannelMessage[][]>([]);
const expandedChannels = ref<Record<number, boolean>>({});

// Add channel dialog
const showAddChannelDialog = ref(false);
const newChannel = ref<ChannelConfig>({
  name: '',
  type: 'public',
  channel: '',
  event: 'message',
  autoConnect: true,
  debugMode: true,
});

/**
 * Set up a channel listener
 *
 * @param index - Channel index
 */
const setupChannel = (index: number): void => {
  const channel = channels.value[index];

  if (!channel) return;

  const options: WebSocketListenerOptions = {
    channel: channel.channel,
    event: channel.event,
    type: channel.type ?? 'public',
    autoConnect: channel.autoConnect ?? false,
    debugMode: channel.debugMode ?? false,
    presenceHandlers: channel.presenceHandlers,
    callback: (data: unknown) => {
      // Store received messages
      if (!channelMessages.value[index]) {
        channelMessages.value[index] = [];
      }

      channelMessages.value[index].unshift({
        timestamp: new Date(),
        data,
      });

      // Limit message history to 50 messages per channel
      if (channelMessages.value[index].length > 50) {
        channelMessages.value[index] = channelMessages.value[index].slice(0, 50);
      }
    },
  };

  const listener = useWebSocketChannelListener(options);

  // Create a simplified state object that doesn't rely on refs
  // This avoids TypeScript errors with the connectionState ref
  channelStates.value[index] = {
    connectionState: listener.connectionState.value,
    lastError: listener.lastError.value,
    connect: listener.connect,
    disconnect: listener.disconnect,
    reconnect: listener.reconnect,
  };

  // Set up a watcher to update our simplified state when the listener state changes
  watch(listener.connectionState, (newState) => {
    if (channelStates.value[index]) {
      channelStates.value[index]!.connectionState = newState;
    }
  });

  // Set up a watcher to update our simplified state when the error state changes
  watch(listener.lastError, (newError) => {
    if (channelStates.value[index]) {
      channelStates.value[index]!.lastError = newError;
    }
  });
};

/**
 * Add a new channel
 */
const addChannel = (): void => {
  channels.value.push({ ...newChannel.value });
  channelMessages.value.push([]);
  setupChannel(channels.value.length - 1);

  // Reset form
  newChannel.value = {
    name: '',
    type: 'public',
    channel: '',
    event: 'message',
    autoConnect: true,
    debugMode: true,
  };

  showAddChannelDialog.value = false;
};

/**
 * Remove a channel
 *
 * @param index - Channel index
 */
const removeChannel = (index: number): void => {
  // Disconnect if connected
  if (channelStates.value[index]) {
    channelStates.value[index]?.disconnect();
  }

  // Remove channel
  channels.value.splice(index, 1);
  channelStates.value.splice(index, 1);
  channelMessages.value.splice(index, 1);
};

/**
 * Connect to a channel
 *
 * @param index - Channel index
 */
const connect = (index: number): void => {
  if (channelStates.value[index]) {
    void channelStates.value[index]?.connect();
  }
};

/**
 * Disconnect from a channel
 *
 * @param index - Channel index
 */
const disconnect = (index: number): void => {
  if (channelStates.value[index]) {
    channelStates.value[index]?.disconnect();
  }
};

/**
 * Reconnect to a channel
 *
 * @param index - Channel index
 */
const reconnect = (index: number): void => {
  if (channelStates.value[index]) {
    void channelStates.value[index]?.reconnect();
  }
};

/**
 * Connect to all channels
 */
const connectAll = (): void => {
  channelStates.value.forEach((state) => {
    if (state && (state.connectionState !== ConnectionState.CONNECTED &&
      state.connectionState !== ConnectionState.CONNECTING)) {
      void state.connect();
    }
  });
};

/**
 * Disconnect from all channels
 */
const disconnectAll = (): void => {
  channelStates.value.forEach((state) => {
    if (state) {
      state.disconnect();
    }
  });
};

/**
 * Get color for connection state
 *
 * @param state - Connection state
 * @returns Color for connection state
 */
const getConnectionStateColor = (state: ConnectionState | undefined): string => {
  if (!state) return 'grey';

  switch (state) {
    case ConnectionState.CONNECTED:
      return 'positive';
    case ConnectionState.CONNECTING:
      return 'info';
    case ConnectionState.RECONNECTING:
      return 'warning';
    case ConnectionState.ERROR:
      return 'negative';
    case ConnectionState.DISCONNECTED:
      return 'grey';
    default:
      return 'grey';
  }
};

/**
 * Format date for display
 *
 * @param timestamp - Date to format
 * @returns Formatted date string
 */
const formatDate = (timestamp: Date): string => {
  return date.formatDate(timestamp, 'YYYY-MM-DD HH:mm:ss.SSS');
};

/**
 * Format message data for display
 *
 * @param data - Message data
 * @returns Formatted message data
 */
const formatMessageData = (data: unknown): string => {
  try {
    return JSON.stringify(data, null, 2);
  } catch (error) {
    return String(data);
  }
};

// Initialize predefined channels for testing
onMounted(() => {
  // Add predefined channels
  const predefinedChannels: ChannelConfig[] = [
    // {
    //   name: 'User Private Channel',
    //   type: 'private',
    //   channel: `tenant.${container.config.get('tenant_id')}.User.${sessionStore.user?.id}`,
    //   event: '*',
    //   autoConnect: false,
    //   debugMode: true,
    // },
    {
      name: 'Tenant Chat (Presence)',
      type: 'presence',
      channel: `tenant.${container.config.get('tenant_id')}.chat`,
      event: 'message',
      autoConnect: false,
      debugMode: true,
      presenceHandlers: {
        onListening: {
          event: 'listening',
          callback: () => {
            console.log('Listening to chat channel');
          },
        },
        onHere: (members) => {
          console.log('Current members:', members);
        },
        onJoining: (member) => {
          console.log('New member joined:', member);
        },
        onLeaving: (member) => {
          console.log('Member left:', member);
        },
      }
    },
    {
      name: 'Tenant Chat (Presence)',
      type: 'presence',
      channel: `tenant.01JRHCBGW9E92ER8FVQV55MZRX.chat`,
      event: 'message',
      autoConnect: false,
      debugMode: true,
    },
  ];

  // Add predefined channels
  predefinedChannels.forEach((channel) => {
    channels.value.push(channel);
    channelMessages.value.push([]);
    setupChannel(channels.value.length - 1);
  });
});
</script>

<template>
  <div class="WebSocketChannelManager q-pa-md">
    <div class="text-h6 q-mb-md">WebSocket Channel Manager</div>

    <div class="q-mb-md">
      <q-btn
        color="primary"
        label="Add Channel"
        icon="eva-plus-outline"
        @click="showAddChannelDialog = true"
        class="q-mr-md"
      />
      <q-btn
        color="secondary"
        label="Connect All"
        icon="eva-link-2-outline"
        @click="connectAll"
        :disable="channels.length === 0"
        class="q-mr-md"
      />
      <q-btn
        color="negative"
        label="Disconnect All"
        icon="eva-slash-outline"
        @click="disconnectAll"
        :disable="channels.length === 0"
      />
    </div>

    <div
      v-if="channels.length === 0"
      class="text-center q-pa-lg text-grey-6"
    >
      <div class="text-h5 q-mb-md">
        <q-icon
          name="eva-radio-outline"
          size="2rem"
          class="q-mr-sm"
        />
        No Channels
      </div>
      <div>Add a channel to start listening for WebSocket events</div>
    </div>

    <div v-else>
      <q-card
        v-for="(channel, index) in channels"
        :key="index"
        class="q-mb-md"
        flat
        bordered
      >
        <q-card-section>
          <div class="row justify-between items-center">
            <div>
              <div class="text-subtitle1 text-weight-medium">
                {{ channel.name }}
                <q-badge
                  :color="getConnectionStateColor(channelStates[index]?.connectionState)"
                  class="q-ml-sm"
                >
                  {{ channelStates[index]?.connectionState || 'Not initialized' }}
                </q-badge>
              </div>
              <div class="text-caption text-grey-8">
                {{ channel.type || 'public' }}: {{ channel.channel }}
              </div>
              <div class="text-caption text-grey-8">
                Event: {{ channel.event }}
              </div>
            </div>
            <div>
              <q-btn
                flat
                round
                size="sm"
                color="primary"
                icon="eva-link-2-outline"
                @click="connect(index)"
                :disable="channelStates[index]?.connectionState === ConnectionState.CONNECTED ||
                  channelStates[index]?.connectionState === ConnectionState.CONNECTING"
                :loading="channelStates[index]?.connectionState === ConnectionState.CONNECTING"
              >
                <q-tooltip>Connect</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="negative"
                icon="eva-slash-outline"
                @click="disconnect(index)"
                :disable="channelStates[index]?.connectionState === ConnectionState.DISCONNECTED"
              >
                <q-tooltip>Disconnect</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="warning"
                icon="eva-sync-outline"
                @click="reconnect(index)"
                :disable="channelStates[index]?.connectionState === ConnectionState.CONNECTING"
                :loading="channelStates[index]?.connectionState === ConnectionState.RECONNECTING"
              >
                <q-tooltip>Reconnect</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="grey"
                icon="eva-close-outline"
                @click="removeChannel(index)"
              >
                <q-tooltip>Remove</q-tooltip>
              </q-btn>
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-expansion-item
            group="channelDetails"
            :label="`Channel Details ${channelMessages[index]?.length ? `(${channelMessages[index].length} messages)` : ''}`"
            header-class="text-primary"
            :value="expandedChannels[index] || false"
            @update:model-value="(val) => (expandedChannels[index] = val)"
          >
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-list dense>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Connection State</q-item-label>
                      <q-item-label>{{ channelStates[index]?.connectionState || 'Not initialized' }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>
              <div class="col-12 col-md-6">
                <q-list dense>
                  <q-item v-if="channelStates[index]?.lastError">
                    <q-item-section>
                      <q-item-label caption>Last Error</q-item-label>
                      <q-item-label class="text-negative">
                        {{ channelStates[index]?.lastError?.message }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>
            </div>

            <q-separator class="q-my-md" />

            <div class="text-subtitle2 q-mb-sm">Messages</div>
            <div
              v-if="!channelMessages[index]?.length"
              class="text-center q-pa-md text-grey-6"
            >
              No messages received yet
            </div>
            <div
              v-else
              class="message-container q-pa-sm"
              style="max-height: 300px; overflow-y: auto"
            >
              <q-list separator>
                <q-item
                  v-for="(message, msgIndex) in channelMessages[index]"
                  :key="msgIndex"
                >
                  <q-item-section>
                    <q-item-label caption>
                      {{ formatDate(message.timestamp) }}
                    </q-item-label>
                    <q-item-label>
                      <pre class="message-data">{{ formatMessageData(message.data) }}</pre>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </q-expansion-item>
        </q-card-section>
      </q-card>
    </div>

    <!-- Add Channel Dialog -->
    <q-dialog
      v-model="showAddChannelDialog"
      persistent
    >
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Add WebSocket Channel</div>
        </q-card-section>

        <q-card-section>
          <q-form @submit="addChannel">
            <q-input
              v-model="newChannel.name"
              label="Channel Name"
              :rules="[(val) => !!val || 'Name is required']"
              class="q-mb-md"
            />

            <q-select
              v-model="newChannel.type"
              :options="['public', 'private', 'presence', 'encrypted']"
              label="Channel Type"
              class="q-mb-md"
            />

            <q-input
              v-model="newChannel.channel"
              label="Channel"
              :rules="[(val) => !!val || 'Channel is required']"
              class="q-mb-md"
              :hint="`Will be prefixed with ${newChannel.type}- if needed`"
            />

            <q-input
              v-model="newChannel.event"
              label="Event"
              :rules="[(val) => !!val || 'Event is required']"
              class="q-mb-md"
            />

            <div class="row q-mb-md">
              <q-toggle
                v-model="newChannel.autoConnect"
                label="Auto Connect"
              />
            </div>

            <div class="row q-mb-md">
              <q-toggle
                v-model="newChannel.debugMode"
                label="Debug Mode"
              />
            </div>
          </q-form>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancel"
            color="primary"
            v-close-popup
          />
          <q-btn
            flat
            label="Add"
            color="primary"
            @click="addChannel"
            :disable="!newChannel.name || !newChannel.channel || !newChannel.event"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<style scoped>
.message-data {
  white-space: pre-wrap;
  word-break: break-word;
  font-family: monospace;
  font-size: 0.8rem;
  margin: 0;
  padding: 8px;
  background-color: #f5f5f5;
  border-radius: 4px;
  max-height: 200px;
  overflow-y: auto;
}
</style>
