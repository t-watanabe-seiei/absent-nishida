<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold">お知らせ管理</h1>
      <router-link
        to="/admin/announcements/create"
        class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
      >
        ＋ 新規作成
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-8 text-gray-500">読み込み中...</div>
    <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded text-red-700">{{ error }}</div>

    <div v-else>
      <div v-if="announcements.length === 0" class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
        お知らせはありません
      </div>

      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-600">件名</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600">対象クラス</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600">有効期限</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600">既読</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600">作成者</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="item in announcements"
              :key="item.id"
              :class="isExpired(item.expires_at) ? 'bg-gray-50 text-gray-400' : ''"
            >
              <td class="px-4 py-3">
                <span class="font-medium">{{ item.title }}</span>
                <span v-if="item.attachments && item.attachments.length > 0" class="ml-1 text-xs text-blue-500">
                  📎{{ item.attachments.length }}
                </span>
                <span v-if="isExpired(item.expires_at)" class="ml-2 text-xs bg-gray-200 text-gray-500 px-1 rounded">期限切れ</span>
              </td>
              <td class="px-4 py-3 text-xs">
                <span
                  v-for="cls in (item.target_class_ids || [])"
                  :key="cls"
                  class="inline-block bg-blue-100 text-blue-700 px-1 rounded mr-1"
                >{{ cls }}</span>
              </td>
              <td class="px-4 py-3 text-xs">{{ formatDate(item.expires_at) }}</td>
              <td class="px-4 py-3 text-xs">
                <span :class="item.reads_count > 0 ? 'text-green-700 font-medium' : 'text-gray-400'">
                  {{ item.reads_count }} / {{ item.total_targets_count ?? '?' }}
                </span>
              </td>
              <td class="px-4 py-3 text-xs text-gray-500">{{ item.admin?.name ?? '-' }}</td>
              <td class="px-4 py-3">
                <div class="flex gap-2">
                  <router-link
                    :to="`/admin/announcements/${item.id}`"
                    class="text-xs text-blue-600 hover:underline"
                  >詳細</router-link>
                  <router-link
                    v-if="canEdit(item)"
                    :to="`/admin/announcements/${item.id}/edit`"
                    class="text-xs text-green-600 hover:underline"
                  >編集</router-link>
                  <button
                    v-if="canEdit(item)"
                    @click="deleteAnnouncement(item)"
                    class="text-xs text-red-500 hover:underline"
                  >削除</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- ページネーション -->
        <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2 p-4 border-t">
          <button
            v-for="page in pagination.last_page"
            :key="page"
            @click="fetchPage(page)"
            class="px-3 py-1 text-sm rounded border"
            :class="page === pagination.current_page ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-gray-100'"
          >{{ page }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const loading = ref(true);
const error = ref(null);
const announcements = ref([]);
const pagination = ref(null);

async function fetchPage(page = 1) {
  loading.value = true;
  error.value = null;
  try {
    const res = await axios.get('/api/admin/announcements', { params: { page, per_page: 20 } });
    const data = res.data;
    announcements.value = data.data ?? [];
    pagination.value = { current_page: data.current_page, last_page: data.last_page };
  } catch (e) {
    error.value = 'お知らせの取得に失敗しました';
  } finally {
    loading.value = false;
  }
}

function isExpired(expiresAt) {
  return expiresAt && new Date(expiresAt) < new Date();
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

function canEdit(item) {
  const user = authStore.user;
  if (!user) return false;
  return user.is_super_admin || item.admin_id === user.id;
}

async function deleteAnnouncement(item) {
  if (!confirm(`「${item.title}」を削除しますか？`)) return;
  try {
    await axios.delete(`/api/admin/announcements/${item.id}`);
    announcements.value = announcements.value.filter(a => a.id !== item.id);
  } catch (e) {
    alert('削除に失敗しました');
  }
}

onMounted(() => fetchPage());
</script>
