<template>
  <div>
    <h2 class="text-2xl font-bold text-center mb-6">メールアドレスの登録</h2>

    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
      <p class="text-sm text-blue-800">
        📧 初回ログインです。<strong>2段階認証</strong>に使用するメールアドレスを登録してください。<br>
        登録したメールアドレスに認証コードが送信されます。
      </p>
    </div>

    <form @submit.prevent="handleSubmit">
      <Input
        id="parent_email"
        v-model="form.parent_email"
        type="email"
        label="メールアドレス"
        placeholder="email@example.com"
        required
        :error="errors.parent_email"
      />

      <p v-if="errors.general" class="mb-4 text-sm text-red-600">{{ errors.general }}</p>

      <Button
        type="submit"
        variant="primary"
        class="w-full"
        :disabled="loading"
      >
        {{ loading ? '送信中...' : '認証コードを送信する' }}
      </Button>
    </form>

    <div class="mt-4 text-center">
      <button
        @click="goBack"
        class="text-sm text-gray-600 hover:text-gray-800 underline"
      >
        ← ログイン画面に戻る
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Input from '../../components/Input.vue';
import Button from '../../components/Button.vue';

const router = useRouter();

const form = reactive({
  parent_email: ''
});

const errors = reactive({
  parent_email: '',
  general: ''
});

const loading = ref(false);

const handleSubmit = async () => {
  errors.parent_email = '';
  errors.general = '';
  loading.value = true;

  try {
    const response = await axios.post('/api/parent/register-email', {
      parent_email: form.parent_email
    });

    // 2段階認証画面へ遷移
    router.push({
      name: 'parent.verify2fa',
      query: { email: response.data.email }
    });
  } catch (error) {
    if (error.response?.data?.errors) {
      const apiErrors = error.response.data.errors;
      errors.parent_email = apiErrors.parent_email?.[0] || '';
    } else {
      errors.general = error.response?.data?.message || 'エラーが発生しました。再度お試しください。';
    }
  } finally {
    loading.value = false;
  }
};

const goBack = () => {
  router.push({ name: 'parent.login' });
};
</script>
