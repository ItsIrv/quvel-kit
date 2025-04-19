<script lang="ts" setup>
/**
 * MultiChannelWebSocketManager
 *
 * A component that demonstrates managing multiple WebSocket connections
 * simultaneously in a real-world application scenario.
 *
 * This component:
 * - Manages multiple channel connections with different types
 * - Handles connection state for each channel independently
 * - Demonstrates proper connection lifecycle management
 * - Shows how to handle channel-specific events and data
 */
import { ref, computed, reactive, onMounted, onBeforeUnmount, watch, ComputedRef, Ref } from 'vue';
import { useWebSocketChannelListener } from '../composables/useWebSocketChannelListener';
import { useContainer } from '../composables/useContainer';
import { ChannelConfig, ConnectionState } from '../types/websocket.types';

// Container for accessing services
const container = useContainer();

// Component state
const isVisible = ref(true);
const tenantId = computed(() => container.config.get('tenant_id') ?? '1');

// Track connection states for each channel
const connectionStates = reactive<Record<string, string>>({});

// Track all channels in a reactive object
const channels = reactive<Record<string, ChannelConfig>>({
  notifications: {
    id: 'notifications',
    name: `tenant.${tenantId.value}`,
    type: 'publicNotification',
    event: '*',
    autoConnect: false,
    isExpanded: false,
    messages: [],
  },
  users: {
    id: 'users',
    name: `tenant.${tenantId.value}.users`,
    type: 'privateNotification',
    event: '*',
    autoConnect: false,
    isExpanded: false,
    messages: [],
  },
  chat: {
    id: 'chat',
    name: `presence-tenant.${tenantId.value}.chat`,
    type: 'presence',
    event: 'message', // Listen for custom message events
    autoConnect: true,
    isExpanded: true,
    messages: [],
    members: undefined, // Track connected members
    presenceHandlers: {
      onListening: {
        event: 'pusher:subscription_succeeded',
        callback: () => {
          console.log('Presence channel connected');
        },
      },
      onHere: (members: Record<string, unknown>) => {
        console.log('Members here:', members);
      },
      onJoining: (member: Record<string, unknown>) => {
        console.log('Member joining:', member);
      },
      onLeaving: (member: Record<string, unknown>) => {
        console.log('Member leaving:', member);
      },
    },
  },
  chatTenantOne: {
    id: 'chatTenantOne',
    name: `presence-tenant.01JRHCBGTQFXC07NF1QAJ76KCY.chat`,
    type: 'presence',
    event: 'message', // Listen for custom message events
    autoConnect: true,
    isExpanded: true,
    messages: [],
    members: undefined, // Track connected members
  },
});

// Connection managers for each channel
const connectionManagers = reactive<Record<string, {
  isConnected: ComputedRef<boolean>,
  connect: () => void,
  disconnect: () => void,
  reconnect: () => Promise<void>,
  connectionState: Ref<ConnectionState>
}>>({});

// Initialize connection managers for each channel
Object.values(channels).forEach(channel => {
  // Initialize connection state
  connectionStates[channel.id] = ConnectionState.DISCONNECTED;

  // Create message handler for this specific channel
  const handleMessage = (data: unknown) => {
    console.log(`[${channel.id}] Received message:`, data);

    // Add message to channel's message list
    channel.messages.unshift({
      timestamp: new Date(),
      data
    });

    // Limit message count to prevent memory issues
    if (channel.messages.length > 50) {
      channel.messages.pop();
    }
  };

  // Create connection manager for this channel
  const { isConnected, connect, disconnect, reconnect, connectionState } = useWebSocketChannelListener({
    channel: channel.name,
    event: channel.event,
    callback: handleMessage,
    type: channel.type,
    autoConnect: channel.autoConnect && isVisible.value,
    onConnectionStateChange: (state) => {
      connectionStates[channel.id] = state;
      console.debug(`[${channel.id}] Connection state: ${state}`);
    },
    onError: (error) => {
      console.error(`[${channel.id}] Error:`, error);
    },
    // Add presence channel handlers for presence channels
    presenceHandlers: channel.type === 'presence' ? channel.presenceHandlers ?? {
      onHere: (members) => {
        console.log(`[${channel.id}] Members here:`, members);
        channel.members = members;
        // Add a message to show the current members
        channel.messages.unshift({
          timestamp: new Date(),
          data: { event: 'members_here', members }
        });
      },
      onJoining: (member) => {
        console.log(`[${channel.id}] Member joined:`, member);
        // Add a message when a member joins
        channel.messages.unshift({
          timestamp: new Date(),
          data: { event: 'member_joined', member }
        });
      },
      onLeaving: (member) => {
        console.log(`[${channel.id}] Member left:`, member);
        // Add a message when a member leaves
        channel.messages.unshift({
          timestamp: new Date(),
          data: { event: 'member_left', member }
        });
      }
    } : undefined
  });

  // Store connection manager in reactive object
  connectionManagers[channel.id] = { isConnected, connect, disconnect, reconnect, connectionState };

  // Watch for connection state changes
  watch(
    () => connectionManagers[channel.id]?.connectionState?.value,
    (newState) => {
      if (newState) {
        connectionStates[channel.id] = newState;
      }
    },
    { immediate: true }
  );
});

// Methods for managing channels
function toggleChannel(channelId: string) {
  if (!channels[channelId]) return;
  channels[channelId].isExpanded = !channels[channelId].isExpanded;
}

function connectChannel(channelId: string) {
  void connectionManagers[channelId]?.connect();
}

function disconnectChannel(channelId: string) {
  connectionManagers[channelId]?.disconnect();
}

function reconnectChannel(channelId: string) {
  void connectionManagers[channelId]?.reconnect();
}

function clearMessages(channelId: string) {
  if (!channels[channelId]) return;
  channels[channelId].messages = [];
}

// Connect all channels that should auto-connect
onMounted(() => {
  // Additional initialization if needed
});

// Disconnect all channels on unmount
onBeforeUnmount(() => {
  Object.keys(connectionManagers).forEach(channelId => {
    connectionManagers[channelId]?.disconnect();
  });
});

// Helper to get connection state display text
function getConnectionStateText(channelId: string): string {
  const state = connectionStates[channelId] || 'unknown';
  return state.charAt(0).toUpperCase() + state.slice(1);
}

// Helper to check if a channel is connected
function isChannelConnected(channelId: string): boolean {
  return connectionStates[channelId] === ConnectionState.CONNECTED;
}

// Helper to check if a channel is disconnected
function isChannelDisconnected(channelId: string): boolean {
  return connectionStates[channelId] === ConnectionState.DISCONNECTED;
}

// Helper to check if a channel is in an intermediate state
function isChannelTransitioning(channelId: string): boolean {
  return connectionStates[channelId] === ConnectionState.CONNECTING ||
    connectionStates[channelId] === ConnectionState.RECONNECTING;
}

// Helper to check if a channel is in error state
function isChannelError(channelId: string): boolean {
  return connectionStates[channelId] === ConnectionState.ERROR;
}
</script>

<template>
  <div class="multi-channel-manager">
    <div class="manager-header">
      <h2>Multi-Channel WebSocket Manager</h2>
      <div class="manager-actions">
        <button
          class="manager-button"
          @click="isVisible = !isVisible"
        >
          {{ isVisible ? 'Hide' : 'Show' }}
        </button>
      </div>
    </div>

    <div
      v-if="isVisible"
      class="channels-container"
    >
      <div
        v-for="channel in Object.values(channels)"
        :key="channel.id"
        class="channel-card"
      >
        <div
          class="channel-header"
          @click="toggleChannel(channel.id)"
        >
          <div class="channel-title">
            <div
              class="connection-indicator"
              :class="{
                'connected': isChannelConnected(channel.id),
                'disconnected': isChannelDisconnected(channel.id),
                'transitioning': isChannelTransitioning(channel.id),
                'error': isChannelError(channel.id)
              }"
              :title="getConnectionStateText(channel.id)"
            ></div>
            <span>{{ channel.id }}</span>
            <span class="channel-name">{{ channel.name }}</span>
            <span class="channel-type">{{ channel.type }}</span>
          </div>
          <div class="channel-actions">
            <button
              v-if="isChannelDisconnected(channel.id)"
              class="channel-button connect"
              @click.stop="connectChannel(channel.id)"
              title="Connect"
            >
              Connect
            </button>
            <button
              v-else-if="isChannelConnected(channel.id)"
              class="channel-button disconnect"
              @click.stop="disconnectChannel(channel.id)"
              title="Disconnect"
            >
              Disconnect
            </button>
            <button
              v-else
              class="channel-button reconnect"
              @click.stop="reconnectChannel(channel.id)"
              title="Reconnect"
            >
              Reconnect
            </button>
            <button
              class="channel-button clear"
              @click.stop="clearMessages(channel.id)"
              title="Clear Messages"
            >
              Clear
            </button>
            <span class="expand-indicator">
              {{ channel.isExpanded ? '▼' : '▶' }}
            </span>
          </div>
        </div>

        <div
          v-if="channel.isExpanded"
          class="channel-content"
        >
          <div class="channel-info">
            <div>Channel: <strong>{{ channel.name }}</strong></div>
            <div>Event: <strong>{{ channel.event }}</strong></div>
            <div>Status: <strong>{{ getConnectionStateText(channel.id) }}</strong></div>
            <div>Messages: <strong>{{ channel.messages.length }}</strong></div>
          </div>

          <div class="channel-messages">
            <div
              v-if="channel.messages.length === 0"
              class="no-messages"
            >
              No messages received
            </div>
            <div
              v-for="(message, index) in channel.messages"
              :key="`${channel.id}-${index}`"
              class="message-item"
            >
              <div class="message-time">
                {{ message.timestamp.toLocaleTimeString() }}
              </div>
              <pre
                class="message-data">{{ typeof message.data === 'object' ? JSON.stringify(message.data, null, 2) : message.data }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.multi-channel-manager {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  background-color: #f8f9fa;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  margin: 20px;
  overflow: hidden;
  max-width: 100%;
}

.manager-header {
  background-color: #42b883;
  color: white;
  padding: 12px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.manager-header h2 {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 500;
}

.manager-actions {
  display: flex;
  gap: 8px;
}

.manager-button {
  background-color: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background-color 0.2s;
}

.manager-button:hover {
  background-color: rgba(255, 255, 255, 0.3);
}

.channels-container {
  padding: 16px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
  gap: 16px;
}

.channel-card {
  background-color: white;
  border-radius: 6px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.channel-header {
  padding: 12px 16px;
  background-color: #f1f3f5;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  user-select: none;
  border-bottom: 1px solid #e9ecef;
}

.channel-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}

.connection-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.connection-indicator.connected {
  background-color: #40c057;
  box-shadow: 0 0 5px #40c057;
}

.connection-indicator.disconnected {
  background-color: #868e96;
}

.connection-indicator.transitioning {
  background-color: #fd7e14;
  animation: pulse 1.5s infinite;
}

.connection-indicator.error {
  background-color: #fa5252;
  animation: pulse 0.8s infinite;
}

.channel-name {
  font-size: 0.8rem;
  color: #495057;
  margin-left: 4px;
}

.channel-type {
  font-size: 0.7rem;
  background-color: #e9ecef;
  color: #495057;
  padding: 2px 6px;
  border-radius: 3px;
}

.channel-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.channel-button {
  border: none;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.channel-button.connect {
  background-color: #40c057;
  color: white;
}

.channel-button.connect:hover {
  background-color: #37b24d;
}

.channel-button.disconnect {
  background-color: #868e96;
  color: white;
}

.channel-button.disconnect:hover {
  background-color: #495057;
}

.channel-button.reconnect {
  background-color: #fd7e14;
  color: white;
}

.channel-button.reconnect:hover {
  background-color: #f76707;
}

.channel-button.clear {
  background-color: #dee2e6;
  color: #495057;
}

.channel-button.clear:hover {
  background-color: #ced4da;
}

.expand-indicator {
  font-size: 0.8rem;
  color: #868e96;
  margin-left: 4px;
}

.channel-content {
  padding: 16px;
}

.channel-info {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 12px;
  font-size: 0.9rem;
  color: #495057;
  background-color: #f8f9fa;
  padding: 8px 12px;
  border-radius: 4px;
}

.channel-messages {
  background-color: #f8f9fa;
  border-radius: 4px;
  max-height: 300px;
  overflow-y: auto;
}

.no-messages {
  padding: 16px;
  text-align: center;
  color: #868e96;
  font-style: italic;
}

.message-item {
  padding: 8px 12px;
  border-bottom: 1px solid #e9ecef;
}

.message-item:last-child {
  border-bottom: none;
}

.message-time {
  font-size: 0.8rem;
  color: #868e96;
  margin-bottom: 4px;
}

.message-data {
  margin: 0;
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  font-size: 0.85rem;
  white-space: pre-wrap;
  word-break: break-word;
  background-color: #f1f3f5;
  color: #495057;
  padding: 8px;
  border-radius: 4px;
  overflow-x: auto;
}

@keyframes pulse {
  0% {
    opacity: 1;
  }

  50% {
    opacity: 0.5;
  }

  100% {
    opacity: 1;
  }
}
</style>
