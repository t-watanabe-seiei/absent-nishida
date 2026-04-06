<template>
  <div>
    <div class="flex items-center gap-3 mb-6">
      <router-link to="/admin/announcements" class="text-blue-600 hover:underline text-sm">← 一覧に戻る</router-link>
      <h1 class="text-3xl font-bold">お知らせ詳細</h1>
    </div>

    <div v-if="loading" class="text-center py-8 text-gray-500">読み込み中...</div>
    <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded text-red-700">{{ error }}</div>

    <div v-else>
      <!-- お知らせ内容 -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h2 class="text-xl font-bold">{{ announcement.title }}</h2>
            <div class="flex gap-3 mt-2 text-sm text-gray-500">
              <span>作成者: {{ announcement.admin?.name ?? '-' }}</span>
              <span>有効期限: {{ formatDate(announcement.expires_at) }}</span>
              <span
                :class="isExpired ? 'text-red-500' : 'text-green-600'"
                class="font-medium"
              >{{ isExpired ? '【期限切れ】' : '【有効】' }}</span>
            </div>
          </div>
          <router-link
            v-if="canEdit"
            :to="`/admin/announcements/${announcement.id}/edit`"
            class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded hover:bg-green-200"
          >編集</router-link>
        </div>

        <div class="bg-gray-50 rounded p-4 text-sm whitespace-pre-wrap mb-4">{{ announcement.body }}</div>

        <!-- 対象クラス -->
        <div class="mb-3">
          <span class="text-sm font-medium text-gray-600">対象クラス: </span>
          <span
            v-for="cls in (announcement.target_class_ids ?? [])"
            :key="cls"
            class="inline-block bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded mr-1"
          >{{ cls }}</span>
        </div>

        <!-- 添付ファイル -->
        <div v-if="announcement.attachments && announcement.attachments.length > 0">
          <p class="text-sm font-medium text-gray-600 mb-1">添付ファイル:</p>
          <ul class="space-y-1">
            <li v-for="att in announcement.attachments" :key="att.id">
              <span class="text-sm">📎 {{ att.original_name }}</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- 既読状況 -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold">既読状況</h2>
          <span class="text-lg font-bold text-blue-600">
            {{ readStatus?.read_count ?? 0 }} / {{ readStatus?.total ?? 0 }} 名
          </span>
        </div>

        <div v-if="loadingStatus" class="text-center py-4 text-gray-400">読み込み中...</div>
        <div v-else>
          <!-- 既読一覧 -->
          <div v-if="readStatus?.read_list?.length > 0" class="mb-6">
            <h3 class="text-sm font-medium text-gray-600 mb-2">✅ 既読（{{ readStatus.read_list.length }}名）</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm border rounded">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">保護者名</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">生徒名</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">クラス</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">既読日時</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="r in readStatus.read_list" :key="r.parent_id">
                    <td class="px-3 py-2">{{ r.parent_name }}</td>
                    <td class="px-3 py-2">{{ r.seito_name ?? '-' }}</td>
                    <td class="px-3 py-2">{{ r.class_name ?? '-' }}</td>
                    <td class="px-3 py-2 text-gray-500">{{ formatDateTime(r.read_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- 未読一覧 -->
          <div v-if="readStatus?.unread_list?.length > 0">
            <h3 class="text-sm font-medium text-gray-600 mb-2">⬜ 未読（{{ readStatus.unread_list.length }}名）</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm border rounded">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">保護者名</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">生徒名</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">クラス</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="r in readStatus.unread_list" :key="r.parent_id" class="text-gray-400">
                    <td class="px-3 py-2">{{ r.parent_name }}</td>
                    <td class="px-3 py-2">{{ r.seito_name ?? '-' }}</td>
                    <td class="px-3 py-2">{{ r.class_name ?? '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div
            v-if="!readStatus?.read_list?.length && !readStatus?.unread_list?.length"
            class="text-center text-gray-400 py-4"
          >送信対象の保護者が見つかりません</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../../stores/auth';

const route = useRoute();
const authStore = useAuthStore();

const loading = ref(true);
const loadingStatus = ref(true);
const error = ref(null);
const announcement = ref(null);
const readStatus = ref(null);

const isExpired = computed(() => {
  if (!announcement.value?.expires_at) return false;
  return new Date(announcement.value.expires_at) < new Date();
});

const canEdit = computed(() => {
  const user = authStore.user;
  if (!user || !announcement.value) return false;
  return user.is_super_admin || announcement.value.admin_id === user.id;
});

function formatDate(dateStr) {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('ja-JP', {
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit',
  });
}

async function fetchAnnouncement() {
  loading.value = true;
  try {
    const res = await axios.get(`/api/admin/announcements/${route.params.id}`);
    announcement.value = res.data;
  } catch {
    error.value = 'お知らせの取得に失敗しました';
  } finally {
    loading.value = false;
  }
}

async function fetchReadStatus() {
  loadingStatus.value = true;
  try {
    const res = await axios.get(`/api/admin/announcements/${route.params.id}/reads`);
    readStatus.value = res.data;
  } catch {} finally {
    loadingStatus.value = false;
  }
}

onMounted(() => {
  fetchAnnouncement();
  fetchReadStatus();
});
</script>
