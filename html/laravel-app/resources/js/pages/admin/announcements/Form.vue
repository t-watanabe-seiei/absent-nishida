<template>
  <div>
    <h1 class="text-3xl font-bold mb-6">{{ isEdit ? 'お知らせ編集' : 'お知らせ作成' }}</h1>

    <div v-if="loadingInit" class="text-center py-8 text-gray-500">読み込み中...</div>

    <form v-else @submit.prevent="submit" class="space-y-6 max-w-2xl">
      <!-- 件名 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">件名 <span class="text-red-500">*</span></label>
        <input
          v-model="form.title"
          type="text"
          required
          maxlength="255"
          class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <p v-if="errors.title" class="mt-1 text-xs text-red-600">{{ errors.title }}</p>
      </div>

      <!-- 本文 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">本文 <span class="text-red-500">*</span></label>
        <textarea
          v-model="form.body"
          required
          rows="6"
          class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        ></textarea>
        <p v-if="errors.body" class="mt-1 text-xs text-red-600">{{ errors.body }}</p>
      </div>

      <!-- 有効期限 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">有効期限 <span class="text-red-500">*</span></label>
        <input
          v-model="form.expires_at"
          type="date"
          required
          class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <p v-if="errors.expires_at" class="mt-1 text-xs text-red-600">{{ errors.expires_at }}</p>
      </div>

      <!-- 対象クラス -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">対象クラス <span class="text-red-500">*</span></label>
        <div v-if="isSuperAdmin" class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto border rounded p-3">
          <label
            v-for="cls in classList"
            :key="cls.class_id"
            class="flex items-center gap-2 text-sm cursor-pointer"
          >
            <input
              type="checkbox"
              :value="cls.class_id"
              v-model="form.target_class_ids"
              class="rounded"
            />
            {{ cls.class_name }}
          </label>
        </div>
        <div v-else class="p-3 bg-gray-100 rounded text-sm text-gray-700">
          {{ authStore.user?.class_name ?? authStore.user?.class_id ?? '担当クラス' }}（固定）
        </div>
        <p v-if="errors.target_class_ids" class="mt-1 text-xs text-red-600">{{ errors.target_class_ids }}</p>
      </div>

      <!-- 個別保護者指定 -->
      <div v-if="form.target_class_ids.length > 0">
        <label class="block text-sm font-medium text-gray-700 mb-1">個別保護者指定（任意）</label>
        <p class="text-xs text-gray-500 mb-2">選択しない場合は対象クラス全員に送信されます</p>
        <div v-if="loadingParents" class="text-xs text-gray-400">保護者一覧を読み込み中...</div>
        <div v-else class="max-h-40 overflow-y-auto border rounded p-3 space-y-1">
          <label
            v-for="parent in filteredParents"
            :key="parent.id"
            class="flex items-center gap-2 text-sm cursor-pointer"
          >
            <input
              type="checkbox"
              :value="parent.id"
              v-model="form.target_parent_ids"
              class="rounded"
            />
            {{ parent.parent_name }}
            <span class="text-xs text-gray-400">（{{ parent.student?.class_model?.class_name ?? parent.student?.seito_id }}）</span>
          </label>
          <p v-if="filteredParents.length === 0" class="text-xs text-gray-400">該当する保護者がいません</p>
        </div>
      </div>

      <!-- メール通知 -->
      <div>
        <label class="flex items-center gap-2 text-sm cursor-pointer">
          <input
            type="checkbox"
            v-model="form.notify_by_email"
            class="rounded"
          />
          <span>対象保護者にメールでも通知する</span>
        </label>
      </div>

      <!-- PDF添付（新規作成時） -->
      <div v-if="!isEdit">
        <label class="block text-sm font-medium text-gray-700 mb-1">PDF添付（最大5件・各10MBまで）</label>
        <input
          type="file"
          ref="fileInput"
          accept="application/pdf"
          multiple
          @change="onFileChange"
          class="text-sm"
        />
        <ul v-if="selectedFiles.length > 0" class="mt-2 space-y-1">
          <li v-for="(f, i) in selectedFiles" :key="i" class="flex items-center gap-2 text-xs text-gray-600">
            📎 {{ f.name }}
            <button type="button" @click="removeFile(i)" class="text-red-500 hover:underline">削除</button>
          </li>
        </ul>
        <p v-if="fileError" class="mt-1 text-xs text-red-600">{{ fileError }}</p>
      </div>

      <!-- 編集時: 既存添付ファイル -->
      <div v-if="isEdit && existingAttachments.length > 0">
        <label class="block text-sm font-medium text-gray-700 mb-1">添付ファイル</label>
        <ul class="space-y-1">
          <li v-for="att in existingAttachments" :key="att.id" class="flex items-center gap-2 text-sm">
            📎 {{ att.original_name }}
            <button
              type="button"
              @click="removeExistingAttachment(att)"
              class="text-xs text-red-500 hover:underline"
            >削除</button>
          </li>
        </ul>
        <div v-if="existingAttachments.length < 5" class="mt-3">
          <label class="block text-xs font-medium text-gray-600 mb-1">ファイルを追加</label>
          <input
            type="file"
            ref="addFileInput"
            accept="application/pdf"
            @change="uploadNewAttachment"
            class="text-sm"
          />
        </div>
      </div>

      <!-- エラー -->
      <div v-if="submitError" class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
        {{ submitError }}
      </div>

      <!-- ボタン -->
      <div class="flex gap-3">
        <button
          type="submit"
          :disabled="submitting"
          class="px-6 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 disabled:opacity-50"
        >
          {{ submitting ? '保存中...' : (isEdit ? '更新する' : '作成する') }}
        </button>
        <router-link
          to="/admin/announcements"
          class="px-6 py-2 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300"
        >キャンセル</router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../../stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const isEdit = computed(() => !!route.params.id);
const isSuperAdmin = computed(() => authStore.user?.is_super_admin ?? false);

const loadingInit = ref(true);
const loadingParents = ref(false);
const submitting = ref(false);
const submitError = ref(null);
const errors = ref({});
const fileError = ref(null);
const fileInput = ref(null);
const addFileInput = ref(null);
const selectedFiles = ref([]);
const classList = ref([]);
const allParents = ref([]);
const existingAttachments = ref([]);

const form = ref({
  title: '',
  body: '',
  expires_at: '',
  target_class_ids: [],
  target_parent_ids: [],
  notify_by_email: false,
});

const filteredParents = computed(() => {
  if (form.value.target_class_ids.length === 0) return [];
  return allParents.value.filter(p => {
    const classId = p.student?.class_id;
    return classId && form.value.target_class_ids.includes(classId);
  });
});

async function loadClasses() {
  if (!isSuperAdmin.value) return;
  try {
    const res = await axios.get('/api/admin/classes', { params: { per_page: 200 } });
    classList.value = res.data.data ?? [];
  } catch {}
}

async function loadParents() {
  loadingParents.value = true;
  try {
    const res = await axios.get('/api/admin/parents', { params: { per_page: 500 } });
    allParents.value = res.data.data ?? [];
  } catch {} finally {
    loadingParents.value = false;
  }
}

async function loadAnnouncement(id) {
  try {
    const res = await axios.get(`/api/admin/announcements/${id}`);
    const a = res.data;
    form.value.title = a.title;
    form.value.body = a.body;
    form.value.expires_at = a.expires_at ? a.expires_at.substring(0, 10) : '';
    form.value.target_class_ids = a.target_class_ids ?? [];
    form.value.target_parent_ids = a.target_parent_ids ?? [];
    form.value.notify_by_email = a.notify_by_email ?? false;
    existingAttachments.value = a.attachments ?? [];
  } catch {
    submitError.value = 'お知らせの読み込みに失敗しました';
  }
}

function onFileChange(e) {
  fileError.value = null;
  const files = Array.from(e.target.files);
  const total = selectedFiles.value.length + files.length;
  if (total > 5) {
    fileError.value = '添付ファイルは最大5件です';
    if (fileInput.value) fileInput.value.value = '';
    return;
  }
  for (const f of files) {
    if (f.size > 10 * 1024 * 1024) {
      fileError.value = `${f.name} のサイズが10MBを超えています`;
      if (fileInput.value) fileInput.value.value = '';
      return;
    }
    if (f.type !== 'application/pdf') {
      fileError.value = 'PDFファイルのみ添付できます';
      if (fileInput.value) fileInput.value.value = '';
      return;
    }
  }
  selectedFiles.value = [...selectedFiles.value, ...files];
  if (fileInput.value) fileInput.value.value = '';
}

function removeFile(index) {
  selectedFiles.value = selectedFiles.value.filter((_, i) => i !== index);
}

async function removeExistingAttachment(att) {
  if (!confirm(`「${att.original_name}」を削除しますか？`)) return;
  try {
    await axios.delete(`/api/admin/announcements/${route.params.id}/attachments/${att.id}`);
    existingAttachments.value = existingAttachments.value.filter(a => a.id !== att.id);
  } catch {
    alert('削除に失敗しました');
  }
}

async function uploadNewAttachment(e) {
  const file = e.target.files[0];
  if (!file) return;
  const formData = new FormData();
  formData.append('file', file);
  try {
    const res = await axios.post(
      `/api/admin/announcements/${route.params.id}/attachments`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    );
    existingAttachments.value.push(res.data);
  } catch (err) {
    alert(err.response?.data?.error ?? 'アップロードに失敗しました');
  } finally {
    if (addFileInput.value) addFileInput.value.value = '';
  }
}

async function submit() {
  submitting.value = true;
  submitError.value = null;
  errors.value = {};

  // 担任の場合は自分の class_id を自動セット
  if (!isSuperAdmin.value) {
    const classId = authStore.user?.class_id;
    if (classId) {
      form.value.target_class_ids = [classId];
    }
  }

  try {
    if (isEdit.value) {
      await axios.put(`/api/admin/announcements/${route.params.id}`, {
        title: form.value.title,
        body: form.value.body,
        expires_at: form.value.expires_at,
        target_class_ids: form.value.target_class_ids,
        target_parent_ids: form.value.target_parent_ids.length > 0 ? form.value.target_parent_ids : null,
        notify_by_email: form.value.notify_by_email,
      });
    } else {
      const formData = new FormData();
      formData.append('title', form.value.title);
      formData.append('body', form.value.body);
      formData.append('expires_at', form.value.expires_at);
      form.value.target_class_ids.forEach(c => formData.append('target_class_ids[]', c));
      if (form.value.target_parent_ids.length > 0) {
        form.value.target_parent_ids.forEach(id => formData.append('target_parent_ids[]', id));
      }
      formData.append('notify_by_email', form.value.notify_by_email ? '1' : '0');
      selectedFiles.value.forEach(f => formData.append('files[]', f));

      await axios.post('/api/admin/announcements', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }

    router.push('/admin/announcements');
  } catch (err) {
    const data = err.response?.data;
    if (data?.errors) {
      errors.value = data.errors;
    } else {
      submitError.value = data?.error ?? 'エラーが発生しました';
    }
  } finally {
    submitting.value = false;
  }
}

onMounted(async () => {
  await Promise.all([loadClasses(), loadParents()]);
  if (isEdit.value) {
    await loadAnnouncement(route.params.id);
  }
  loadingInit.value = false;
});
</script>
