<template>
  <div>
    <h2 class="text-2xl font-bold text-center mb-6">保護者ログイン</h2>
    
    <form @submit.prevent="handleSubmit">
      <Input
        id="email"
        v-model="form.email"
        type="email"
        label="メールアドレス"
        placeholder="email@example.com"
        required
        :error="errors.email"
      />
      
      <Input
        id="password"
        v-model="form.password"
        type="password"
        label="パスワード"
        required
        :error="errors.password"
      />
      
      <!-- パスワード保存チェックボックス -->
      <div class="mb-4 flex items-center">
        <input
          id="remember"
          v-model="rememberMe"
          type="checkbox"
          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
        />
        <label for="remember" class="ml-2 block text-sm text-gray-700">
          パスワードを保存する
        </label>
      </div>
      
      <p v-if="errors.general" class="mb-4 text-sm text-red-600">{{ errors.general }}</p>
      <p v-if="successMessage" class="mb-4 text-sm text-green-600">{{ successMessage }}</p>
      
      <Button
        type="submit"
        variant="primary"
        class="w-full"
        :disabled="loading"
      >
        {{ loading ? 'ログイン中...' : 'ログイン' }}
      </Button>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import Input from '../../components/Input.vue';
import Button from '../../components/Button.vue';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  email: '',
  password: ''
});

const errors = reactive({
  email: '',
  password: '',
  general: ''
});

const loading = ref(false);
const rememberMe = ref(false);
const successMessage = ref('');

// ローカルストレージのキー
const STORAGE_KEY_EMAIL = 'parent_saved_email';
const STORAGE_KEY_PASSWORD = 'parent_saved_password';

// ページ読み込み時に保存された情報を復元
onMounted(() => {
  const savedEmail = localStorage.getItem(STORAGE_KEY_EMAIL);
  const savedPassword = localStorage.getItem(STORAGE_KEY_PASSWORD);
  
  if (savedEmail && savedPassword) {
    form.email = savedEmail;
    form.password = savedPassword;
    rememberMe.value = true;
  }
});

const handleSubmit = async () => {
  errors.email = '';
  errors.password = '';
  errors.general = '';
  loading.value = true;
  
  console.log('🔐 保護者ログイン開始:', { email: form.email });
  
  try {
    const response = await authStore.parentLogin(form);

    // パスワード保存の処理
    if (rememberMe.value) {
      localStorage.setItem(STORAGE_KEY_EMAIL, form.email);
      localStorage.setItem(STORAGE_KEY_PASSWORD, form.password);
    } else {
      localStorage.removeItem(STORAGE_KEY_EMAIL);
      localStorage.removeItem(STORAGE_KEY_PASSWORD);
    }

    // メールアドレス未登録（初回ログイン）
    if (response.requires_email_registration) {
      router.push({ name: 'parent.registerEmail' });
      return;
    }

    // 2段階認証が必要な場合
    if (response.requires_2fa) {
      router.push({
        name: 'parent.verify2fa',
        query: { email: response.email }
      });
      return;
    }

    // 直接ログイン成功（後方互換性）
    router.push({ name: 'parent.dashboard' });
  } catch (error) {
    console.error('Login error:', error);
    console.error('Error response:', error.response?.data);
    
    if (error.response?.data?.errors) {
      Object.assign(errors, error.response.data.errors);
    } else {
      errors.general = error.response?.data?.message || 'ログインに失敗しました';
    }
  } finally {
    loading.value = false;
  }
};
</script>
