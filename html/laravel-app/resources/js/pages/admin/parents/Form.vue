<template>
  <div>
    <h1 class="text-2xl font-bold mb-6">{{ isEdit ? '保護者編集' : '保護者登録' }}</h1>
    
    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
      <form @submit.prevent="handleSubmit">
        <Select
          id="seito_id"
          v-model="form.seito_id"
          label="生徒"
          :options="studentOptions"
          placeholder="生徒を選択"
          required
          :error="errors.seito_id"
        />
        
        <Input
          id="parent_name"
          v-model="form.parent_name"
          label="保護者氏名"
          required
          :error="errors.parent_name"
        />
        
        <Input
          id="parent_relationship"
          v-model="form.parent_relationship"
          label="続柄"
          placeholder="例: 父、母"
          required
          :error="errors.parent_relationship"
        />
        
        <Input
          id="parent_tel"
          v-model="form.parent_tel"
          label="電話番号"
          required
          :error="errors.parent_tel"
        />
        
        <Input
          id="parent_email"
          v-model="form.parent_email"
          type="email"
          label="メールアドレス"
          required
          :error="errors.parent_email"
        />
        
        <Input
          v-if="!isEdit"
          id="parent_password"
          v-model="form.parent_password"
          type="password"
          label="パスワード"
          :required="!isEdit"
          :error="errors.parent_password"
        />
        
        <div v-if="initialPassword" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
          <p class="text-sm font-medium text-yellow-800 mb-2">初期パスワード（保護者に伝えてください）</p>
          <p class="text-lg font-mono">{{ initialPassword }}</p>
        </div>
        
        <p v-if="errors.general" class="mb-4 text-sm text-red-600">{{ errors.general }}</p>
        
        <div class="flex gap-4">
          <Button
            type="submit"
            variant="primary"
            :disabled="loading"
          >
            {{ loading ? '保存中...' : '保存' }}
          </Button>
          <router-link to="/admin/parents">
            <Button variant="secondary">キャンセル</Button>
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminStore } from '../../../stores/admin';
import Input from '../../../components/Input.vue';
import Select from '../../../components/Select.vue';
import Button from '../../../components/Button.vue';

const route = useRoute();
const router = useRouter();
const adminStore = useAdminStore();

const isEdit = computed(() => !!route.params.id);

const form = reactive({
  seito_id: '',
  parent_name: '',
  parent_relationship: '',
  parent_tel: '',
  parent_email: '',
  parent_password: ''
});

const errors = reactive({
  seito_id: '',
  parent_name: '',
  parent_relationship: '',
  parent_tel: '',
  parent_email: '',
  parent_password: '',
  general: ''
});

const loading = ref(false);
const students = ref([]);
const initialPassword = ref('');

const studentOptions = computed(() => {
  return students.value.map(s => ({
    value: s.seito_id,
    label: `${s.seito_name} (ID: ${s.seito_id})`
  }));
});

const fetchStudents = async () => {
  try {
    const response = await adminStore.fetchStudents({ per_page: 1000 });
    students.value = response.data || response;
  } catch (error) {
    console.error('生徒取得エラー:', error);
  }
};

const fetchData = async () => {
  if (!isEdit.value) return;
  
  loading.value = true;
  try {
    const data = await adminStore.fetchParent(route.params.id);
    Object.assign(form, {
      seito_id: data.seito_id,
      parent_name: data.parent_name,
      parent_relationship: data.parent_relationship,
      parent_tel: data.parent_tel,
      parent_email: data.parent_initial_email
    });
  } catch (error) {
    errors.general = 'データの取得に失敗しました';
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  Object.keys(errors).forEach(key => errors[key] = '');
  initialPassword.value = '';
  loading.value = true;
  
  try {
    if (isEdit.value) {
      await adminStore.updateParent(route.params.id, form);
      router.push('/admin/parents');
    } else {
      const response = await adminStore.createParent(form);
      // 初期パスワードを表示
      if (response.initial_password) {
        initialPassword.value = response.initial_password;
      } else {
        router.push('/admin/parents');
      }
    }
  } catch (error) {
    if (error.response?.data?.errors) {
      Object.assign(errors, error.response.data.errors);
    } else {
      errors.general = error.response?.data?.message || '保存に失敗しました';
    }
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchStudents();
  fetchData();
});
</script>
