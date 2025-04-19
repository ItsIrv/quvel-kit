<script lang="ts" setup>
/**
 * This component was used to create the WebSocket helpers.
 *
 * It allows you to send tenant notifications and view them in real-time.
 */
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { useWebSocketChannelListener } from '../composables/useWebSocketChannelListener';
import { useContainer } from '../composables/useContainer';
import { ConnectionState } from '../types/websocket.types';

declare global {
  interface Window {
    __showTenantLogger?: () => void;
  }
}

// This is a dev-only helper. You can set the tenantId manually or inject from context/store as needed.
const container = useContainer();
const tenantId = computed(() => container.config.get('tenant_id') ?? null);
const channel = computed(() => tenantId.value ? `tenant.${tenantId.value}` : '');
const messages = ref<{
  timestamp: Date;
  data: unknown;
}[]>([]);
const showMessages = ref(true);
const isVisible = ref(false);
const isPaused = ref(false);
const maxMessages = ref(100); // Limit stored messages to prevent memory issues
const connectionStatus = ref<ConnectionState>(ConnectionState.DISCONNECTED);
const lastMessageTime = ref<Date | null>(null);
const filterText = ref('');

// Draggable functionality
const isDragging = ref(false);
const position = ref({ x: 20, y: 20 }); // Initial position
const dragOffset = ref({ x: 0, y: 0 });

// Handle drag start
function startDrag(event: MouseEvent) {
  // Only allow dragging from the header
  if (!(event.target as HTMLElement).closest('.dev-websocket-logger-header')) {
    return;
  }

  isDragging.value = true;

  // Calculate the offset from the mouse position to the component's top-left corner
  const componentRect = (event.currentTarget as HTMLElement).getBoundingClientRect();
  dragOffset.value = {
    x: event.clientX - componentRect.left,
    y: event.clientY - componentRect.top
  };

  // Prevent text selection during drag
  event.preventDefault();
}

// Handle dragging
function onDrag(event: MouseEvent) {
  if (!isDragging.value) return;

  // Calculate new position based on mouse position and offset
  position.value = {
    x: event.clientX - dragOffset.value.x,
    y: event.clientY - dragOffset.value.y
  };
}

// Handle drag end
function endDrag() {
  isDragging.value = false;
}

// Set up global event listeners for dragging
onMounted(() => {
  window.addEventListener('mousemove', onDrag);
  window.addEventListener('mouseup', endDrag);
});

onUnmounted(() => {
  window.removeEventListener('mousemove', onDrag);
  window.removeEventListener('mouseup', endDrag);
});

// Filter messages based on search text
const filteredMessages = computed(() => {
  if (!filterText.value) return messages.value;

  return messages.value.filter(msg => {
    const msgStr = JSON.stringify(msg).toLowerCase();
    return msgStr.includes(filterText.value.toLowerCase());
  });
});

function handleMessage(data: unknown) {
  if (isPaused.value) return; // Don't process messages when paused

  // Add timestamp to message
  const timestampedData = {
    timestamp: new Date(),
    data
  };

  // Add to messages array with limit
  messages.value.push(timestampedData);
  if (messages.value.length > maxMessages.value) {
    messages.value = messages.value.slice(-maxMessages.value);
  }

  lastMessageTime.value = new Date();
}

function clearMessages() {
  messages.value = [];
}

function togglePause() {
  isPaused.value = !isPaused.value;
}

// Register global console commands
onMounted(() => {
  window.__showTenantLogger = () => {
    isVisible.value = true;

    return 'WebSocket Logger is now visible';
  };

  console.log(
    '%c🔌 Tenant WebSocket Logger available',
    'font-weight: bold; color: #42b883;',
    '\n- __showTenantLogger() - Show the logger\n',
  );
});

// Clean up on unmount
onUnmounted(() => {
  delete window.__showTenantLogger;
});

// Use our enterprise-grade WebSocket listener with explicit connection management
const {
  connectionState,
  lastError,
  reconnectAttempts,
  connect,
  disconnect,
  reconnect,
} = useWebSocketChannelListener({
  channel,
  event: '*',
  callback: handleMessage,
  type: 'publicNotification',
  autoConnect: false, // Don't connect automatically, we'll manage this based on visibility
  onConnectionStateChange: (state) => {
    connectionStatus.value = state;
  },
  onError: (error) => {
    lastError.value = error;
  },
});

// Watch visibility changes to manage connection
watch(
  () => isVisible.value,
  (visible) => {
    if (visible && connectionState.value === ConnectionState.DISCONNECTED) {
      void connect();
    } else if (!visible && connectionState.value !== ConnectionState.DISCONNECTED) {
      disconnect();
    }
  },
  { immediate: true }
);

</script>

<style scoped>
.dev-websocket-logger {
  font-size: 0.95em;
  color: #333;
  background: #f6f8fa;
  border: 1px solid #ddd;
  padding: 0.5em 1em;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  position: fixed;
  z-index: 9999;
  width: 500px;
  height: 400px;
  display: flex;
  flex-direction: column;
  resize: both;
  overflow: hidden;
  cursor: default;
}

.dev-websocket-logger-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #ddd;
  padding-bottom: 8px;
  margin-bottom: 8px;
  flex-shrink: 0;
  cursor: move;
  /* Indicate draggable */
  user-select: none;
  /* Prevent text selection during drag */
}

.dev-websocket-logger-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: bold;
}

.connection-status {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  margin-right: 5px;
}

.connection-status.connected {
  background-color: #42b883;
  box-shadow: 0 0 5px #42b883;
}

.connection-status.disconnected {
  background-color: #f56c6c;
}

.connection-status.connecting,
.connection-status.reconnecting {
  background-color: #e6a23c;
  animation: pulse 1.5s infinite;
}

.connection-status.error {
  background-color: #f56c6c;
  animation: pulse 0.8s infinite;
}

.paused-indicator,
.reconnect-indicator {
  font-size: 0.7em;
  background: #e6a23c;
  color: white;
  padding: 2px 5px;
  border-radius: 3px;
  margin-left: 5px;
}

.reconnect-indicator {
  background: #f56c6c;
}

.dev-websocket-logger-info {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 0.85em;
  margin-bottom: 8px;
  background: #fff;
  padding: 5px 8px;
  border-radius: 4px;
  border: 1px solid #eee;
  flex-shrink: 0;
}

.dev-websocket-logger-filter {
  margin-bottom: 8px;
  flex-shrink: 0;
}

.dev-websocket-logger-filter-input {
  width: 100%;
  padding: 5px 8px;
  border-radius: 4px;
  border: 1px solid #ddd;
  font-size: 0.9em;
}

.dev-websocket-logger-actions {
  display: flex;
  gap: 5px;
}

.dev-websocket-logger-button {
  user-select: none;
  cursor: pointer;
  padding: 3px 8px;
  border-radius: 4px;
  background: #42b883;
  color: white;
  border: none;
  font-size: 0.8em;
  transition: background-color 0.2s;
}

.dev-websocket-logger-button:hover {
  background: #369a6e;
}

.dev-websocket-logger-connect {
  background: #42b883;
}

.dev-websocket-logger-connect:hover {
  background: #369a6e;
}

.dev-websocket-logger-disconnect {
  background: #909399;
}

.dev-websocket-logger-disconnect:hover {
  background: #606266;
}

.dev-websocket-logger-reconnect {
  background: #e6a23c;
}

.dev-websocket-logger-reconnect:hover {
  background: #cf9236;
}

.dev-websocket-logger-close {
  background: #f56c6c;
  font-weight: bold;
  font-size: 1em;
  line-height: 1;
  padding: 3px 8px;
}

.dev-websocket-logger-close:hover {
  background: #e04545;
}

.dev-websocket-logger-error {
  background: #fef0f0;
  color: #f56c6c;
  padding: 8px;
  border-radius: 4px;
  margin-bottom: 8px;
  font-size: 0.9em;
  border: 1px solid #fde2e2;
  flex-shrink: 0;
}

.dev-websocket-logger-messages-container {
  background: #fff;
  border-radius: 4px;
  border: 1px solid #eee;
  overflow-y: auto;
  flex-grow: 1;
  display: flex;
  flex-direction: column-reverse;
  /* Show newest messages at the top */
}

.dev-websocket-logger-empty {
  padding: 10px;
  color: #909399;
  text-align: center;
  font-style: italic;
}

.dev-websocket-logger-message {
  padding: 8px;
  border-bottom: 1px solid #f0f0f0;
}

.dev-websocket-logger-message:last-child {
  border-bottom: none;
}

.dev-websocket-logger-message-time {
  font-size: 0.8em;
  color: #909399;
  margin-bottom: 3px;
}

.dev-websocket-logger-message-content {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 0.9em;
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

<template>
  <div
    v-if="tenantId && isVisible"
    class="dev-websocket-logger"
    :style="{
      left: `${position.x}px`,
      top: `${position.y}px`
    }"
    @mousedown="startDrag"
  >
    <div class="dev-websocket-logger-header">
      <div class="dev-websocket-logger-title">
        <div
          class="connection-status"
          :class="connectionStatus"
          :title="`Connection status: ${connectionStatus}`"
        ></div>
        <span>Tenant WebSocket Logger</span>
        <span
          v-if="isPaused"
          class="paused-indicator"
        >PAUSED</span>
        <span
          v-if="reconnectAttempts > 0"
          class="reconnect-indicator"
        >Reconnect: {{ reconnectAttempts }}</span>
      </div>
      <div class="dev-websocket-logger-actions">
        <button
          class="dev-websocket-logger-button"
          @click="togglePause"
          :title="isPaused ? 'Resume logging' : 'Pause logging'"
        >
          {{ isPaused ? 'Resume' : 'Pause' }}
        </button>
        <button
          class="dev-websocket-logger-button"
          @click="clearMessages"
          title="Clear messages"
        >
          Clear
        </button>
        <button
          v-if="connectionState === 'disconnected'"
          class="dev-websocket-logger-button dev-websocket-logger-connect"
          @click="connect"
          title="Connect to WebSocket"
        >
          Connect
        </button>
        <button
          v-else-if="connectionState === 'connected'"
          class="dev-websocket-logger-button dev-websocket-logger-disconnect"
          @click="disconnect"
          title="Disconnect from WebSocket"
        >
          Disconnect
        </button>
        <button
          v-else
          class="dev-websocket-logger-button dev-websocket-logger-reconnect"
          @click="reconnect"
          title="Reconnect to WebSocket"
        >
          Reconnect
        </button>
        <button
          class="dev-websocket-logger-button dev-websocket-logger-close"
          @click="isVisible = false"
          title="Close logger"
        >
          ×
        </button>
      </div>
    </div>

    <div class="dev-websocket-logger-info">
      <div>Channel: <strong>{{ channel }}</strong></div>
      <div>Status: <strong>{{ connectionStatus }}</strong></div>
      <div v-if="lastMessageTime">Last message: <strong>{{ lastMessageTime.toLocaleTimeString() }}</strong></div>
      <div>Messages: <strong>{{ messages.length }}</strong></div>
    </div>

    <div class="dev-websocket-logger-filter">
      <input
        type="text"
        v-model="filterText"
        placeholder="Filter messages..."
        class="dev-websocket-logger-filter-input"
      />
    </div>

    <div
      v-if="lastError"
      class="dev-websocket-logger-error"
    >
      Error: {{ lastError.message }}
    </div>

    <div
      v-if="showMessages"
      class="dev-websocket-logger-messages-container"
    >
      <div
        v-if="!filteredMessages.length"
        class="dev-websocket-logger-empty"
      >
        No messages yet
      </div>
      <div
        v-for="(message, index) in filteredMessages"
        :key="index"
        class="dev-websocket-logger-message"
      >
        <div class="dev-websocket-logger-message-time">
          {{ message.timestamp?.toLocaleTimeString() }}
        </div>
        <pre class="dev-websocket-logger-message-content">{{ message.data }}</pre>
      </div>
    </div>
  </div>
</template>
