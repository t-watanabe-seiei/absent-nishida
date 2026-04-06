<template>
  <div>
    <h1 class="text-3xl font-bold mb-6">システム設定</h1>

    <div v-if="!isSuperAdmin" class="p-4 bg-red-50 border border-red-200 rounded text-red-700">
      この設定はスーパー管理者のみアクセスできます。
    </div>

    <div v-else class="bg-white rounded-lg shadow p-6 max-w-lg">
      <h2 class="text-lg font-semibold mb-4">お知らせ機能</h2>

      <div v-if="loading" class="text-gray-400">読み込み中...</div>
      <div v-else>
        <div class="flex items-center justify-between py-3 border-b">
          <div>
            <p class="font-medium">お知らせ機能の有効化</p>
            <p class="text-sm text-gray-500">ONにすると保護者ダッシュボードにお知らせが表示されます</p>
          </div>
          <button
            @click="toggleAnnouncement"
            :class="settings.announcement_enabled === '1'
              ? 'bg-blue-600 text-white'
              : 'bg-gray-200 text-gray-600'"
            class="relative inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors"
            :disabled="saving"
          >
            {{ settings.announcement_enabled === '1' ? 'ON' : 'OFF' }}
          </button>
        </div>

        <div v-if="message" :class="messageType === 'success' ? 'text-green-600' : 'text-red-600'" class="mt-4 text-sm">
          {{ message }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const isSuperAdmin = computed(() => authStore.user?.is_super_admin);

const loading = ref(true);
const saving = ref(false);
const message = ref('');
const messageType = ref('success');
const settings = ref({ announcement_enabled: '0' });

async function fetchSettings() {
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/settings');
    settings.value = res.data;
  } catch {
    message.value = '設定の取得に失敗しました';
    messageType.value = 'error';
  } finally {
    loading.value = false;
  }
}

async function toggleAnnouncement() {
  const newVal = settings.value.announcement_enabled === '1' ? '0' : '1';
  saving.value = true;
  try {
    await axios.put('/api/admin/settings', { announcement_enabled: newVal });
    settings.value.announcement_enabled = newVal;
    message.value = '設定を保存しました';
    messageType.value = 'success';
  } catch {
    message.value = '設定の保存に失敗しました';
    messageType.value = 'error';
  } finally {
    saving.value = false;
    setTimeout(() => { message.value = ''; }, 3000);
  }
}

onMounted(() => {
  if (isSuperAdmin.value) fetchSettings();
});
</script>
