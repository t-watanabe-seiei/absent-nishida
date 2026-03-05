<template>
  <div>
    <h1 class="text-2xl font-bold mb-6">{{ isEdit ? '欠席連絡編集' : '欠席連絡登録' }}</h1>
    
    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
      <form @submit.prevent="handleSubmit">
        <Select
          id="division"
          v-model="form.division"
          label="区分"
          :options="divisionOptions"
          placeholder="選択してください"
          required
          :error="errors.division"
        />
        
        <Input
          id="absence_date"
          v-model="form.absence_date"
          type="date"
          :label="getDateLabel()"
          required
          :error="errors.absence_date"
        />
        
        <Input
          v-if="form.division === '遅刻' || form.division === '早退'"
          id="scheduled_time"
          v-model="form.scheduled_time"
          type="text"
          :label="form.division === '遅刻' ? '登校予定時刻' : '早退予定時刻'"
          placeholder="例: 3限目, 午後から"
          :error="errors.scheduled_time"
        />
        
        <div class="mb-4">
          <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">
            理由
            <span class="text-red-500">*</span>
          </label>
          <textarea
            id="reason"
            v-model="form.reason"
            rows="4"
            required
            :class="[
              'block w-full rounded border px-3 py-2 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-0',
              errors.reason
                ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
            ]"
            placeholder="例: 体調不良のため"
          ></textarea>
          <p v-if="errors.reason" class="mt-1 text-sm text-red-600">{{ errors.reason }}</p>
        </div>
        
        <p v-if="errors.general" class="mb-4 text-sm text-red-600">{{ errors.general }}</p>
        
        <div class="flex gap-4">
          <Button
            type="submit"
            variant="success"
            :disabled="loading"
          >
            {{ loading ? (isEdit ? '保存中...' : '登録中...') : (isEdit ? '保存' : '登録') }}
          </Button>
          <router-link to="/parent/absences">
            <Button variant="secondary">キャンセル</Button>
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useParentStore } from '../../../stores/parent';
import { useAuthStore } from '../../../stores/auth';
import Input from '../../../components/Input.vue';
import Select from '../../../components/Select.vue';
import Button from '../../../components/Button.vue';

const route = useRoute();
const router = useRouter();
const parentStore = useParentStore();
const authStore = useAuthStore();

const isEdit = computed(() => !!route.params.id);

const form = reactive({
  seito_id: authStore.user?.seito_id || '',
  division: '',
  absence_date: '',
  scheduled_time: '',
  reason: ''
});

const errors = reactive({
  division: '',
  absence_date: '',
  scheduled_time: '',
  reason: '',
  general: ''
});

const loading = ref(false);

const divisionOptions = [
  { value: '欠席', label: '欠席' },
  { value: '遅刻', label: '遅刻' },
  { value: '早退', label: '早退' }
];

// 区分に応じて日付のラベルを変更
const getDateLabel = () => {
  if (form.division === '遅刻') {
    return '遅刻日';
  } else if (form.division === '早退') {
    return '早退日';
  } else {
    return '欠席日';
  }
};

// 区分が変更されたら登校予定時刻をクリア
watch(() => form.division, (newValue) => {
  if (newValue === '欠席') {
    form.scheduled_time = '';
  }
});

const fetchData = async () => {
  if (!isEdit.value) return;
  
  loading.value = true;
  try {
    const data = await parentStore.fetchAbsence(route.params.id);
    Object.assign(form, {
      seito_id: data.seito_id,
      division: data.division,
      absence_date: data.absence_date,
      scheduled_time: data.scheduled_time || '',
      reason: data.reason
    });
  } catch (error) {
    errors.general = 'データの取得に失敗しました';
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  Object.keys(errors).forEach(key => errors[key] = '');
  loading.value = true;
  
  console.log('Form data before submit:', form);
  
  // 欠席の場合は登校予定時刻をnullにする
  const submitData = {
    seito_id: form.seito_id,
    division: form.division,
    absence_date: form.absence_date,
    scheduled_time: (form.division === '遅刻' || form.division === '早退') ? form.scheduled_time : null,
    reason: form.reason
  };
  
  console.log('Submit data:', submitData);
  
  try {
    if (isEdit.value) {
      await parentStore.updateAbsence(route.params.id, submitData);
    } else {
      await parentStore.createAbsence(submitData);
    }
    router.push('/parent/absences');
  } catch (error) {
    console.error('Submit error:', error);
    console.error('Error response:', error.response?.data);
    console.error('Validation errors:', error.response?.data?.errors);
    if (error.response?.data?.errors) {
      Object.assign(errors, error.response.data.errors);
    } else {
      errors.general = error.response?.data?.message || '保存に失敗しました';
    }
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  // ユーザー情報がない場合は取得
  if (!authStore.user || !authStore.user.seito_id) {
    try {
      await authStore.fetchUser();
    } catch (error) {
      console.error('Failed to fetch user:', error);
      errors.general = 'ユーザー情報の取得に失敗しました';
      return;
    }
  }
  
  // 生徒IDを設定
  form.seito_id = authStore.user?.seito_id || '';
  
  console.log('onMounted - authStore.user:', authStore.user);
  console.log('onMounted - form.seito_id:', form.seito_id);
  
  // デフォルトの日付を今日に設定（新規作成時のみ）
  if (!isEdit.value) {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    form.absence_date = `${year}-${month}-${day}`;
  }
  
  fetchData();
});
</script>
