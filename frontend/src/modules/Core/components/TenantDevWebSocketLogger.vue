<script lang="ts" setup>
import { computed } from 'vue';
import { useWebSocketChannelListener } from '../composables/useWebSocketChannelListener';
import { useContainer } from '../composables/useContainer';

// This is a dev-only helper. You can set the tenantId manually or inject from context/store as needed.
const container = useContainer();
const tenantId = computed(() => container.config.get('tenant_id') ?? null);
const channel = computed(() => tenantId.value ? `tenant.${tenantId.value}` : '');

function handleMessage(data: unknown) {
  console.log(`[WebSocket][DevHelper] Message on channel ${channel.value}:`, data);
}

useWebSocketChannelListener({
  channel: channel.value,
  event: '*',
  callback: handleMessage,
  type: 'public',
});
</script>

<style scoped>
.dev-websocket-logger {
  font-size: 0.95em;
  color: #888;
  background: #f6f8fa;
  border: 1px dashed #bbb;
  padding: 0.5em 1em;
  margin: 1em 0;
  border-radius: 6px;
}
</style>

<template>
  <div
    v-if="tenantId"
    class="dev-websocket-logger"
  >
    <p>Listening to tenant channel: <strong>{{ channel }}</strong></p>
  </div>
</template>
