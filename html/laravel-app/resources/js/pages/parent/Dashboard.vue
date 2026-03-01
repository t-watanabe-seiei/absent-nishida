<template>
  <div>
    <h1 class="text-3xl font-bold mb-6">保護者ダッシュボード</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-2">生徒名</h3>
        <p class="text-xl font-medium">{{ authStore.user?.seito_id || '-' }}</p>
      </div>
      
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-2">欠席連絡数</h3>
        <p class="text-3xl font-bold text-green-600">-</p>
      </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">クイックアクション</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <router-link
          to="/parent/absences/create"
          class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 text-center"
        >
          <p class="font-medium">欠席・遅刻連絡を登録</p>
        </router-link>
        <router-link
          to="/parent/absences"
          class="p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 text-center"
        >
          <p class="font-medium">連絡履歴を見る</p>
        </router-link>
      </div>
    </div>

    <!-- 2FA用メールアドレス変更 -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h2 class="text-xl font-semibold mb-1">2FA用メールアドレス変更</h2>
      <p class="text-sm text-gray-500 mb-4">
        現在のメールアドレス：
        <span class="font-medium text-gray-700">{{ authStore.user?.email || '未設定' }}</span>
      </p>

      <!-- 完了メッセージ -->
      <div v-if="emailChangeSuccess" class="p-4 bg-green-50 border border-green-200 rounded text-green-800 text-sm">
        ✅ メールアドレスを変更しました。次回ログインから新しいアドレスに認証コードが届きます。
      </div>

      <!-- Step 1: 新メールアドレス入力 -->
      <div v-else-if="!codeSent">
        <form @submit.prevent="requestEmailChange">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="new_email">
              新しいメールアドレス
            </label>
            <input
              id="new_email"
              v-model="newEmail"
              type="email"
              required
              placeholder="new@example.com"
              class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p v-if="emailError" class="mt-1 text-sm text-red-600">{{ emailError }}</p>
          </div>
          <button
            type="submit"
            :disabled="emailChanging"
            class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 disabled:opacity-50"
          >
            {{ emailChanging ? '送信中...' : '確認コードを送信' }}
          </button>
        </form>
      </div>

      <!-- Step 2: 確認コード入力 -->
      <div v-else>
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
          📧 <strong>{{ pendingEmail }}</strong> に確認コードを送信しました。
        </div>
        <form @submit.prevent="confirmEmailChange">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="confirm_code">
              確認コード（6桁）
            </label>
            <input
              id="confirm_code"
              v-model="confirmCode"
              type="text"
              required
              maxlength="6"
              placeholder="000000"
              class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p v-if="codeError" class="mt-1 text-sm text-red-600">{{ codeError }}</p>
          </div>
          <div class="flex gap-3">
            <button
              type="submit"
              :disabled="confirming || confirmCode.length !== 6"
              class="px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700 disabled:opacity-50"
            >
              {{ confirming ? '確認中...' : '変更する' }}
            </button>
            <button
              type="button"
              @click="resetEmailChange"
              class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300"
            >
              戻る
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="authStore.needsPasswordChange" class="mt-6 bg-yellow-100 border-l-4 border-yellow-500 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-yellow-700">
            セキュリティのため、パスワードを変更してください。
            <router-link to="/parent/change-password" class="font-medium underline">
              こちらから変更
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();

// メール変更 state
const newEmail          = ref('');
const pendingEmail      = ref('');
const confirmCode       = ref('');
const codeSent          = ref(false);
const emailChanging     = ref(false);
const confirming        = ref(false);
const emailChangeSuccess = ref(false);
const emailError        = ref('');
const codeError         = ref('');

const requestEmailChange = async () => {
  emailError.value = '';
  emailChanging.value = true;
  try {
    const res = await axios.post('/api/parent/request-email-change', { new_email: newEmail.value });
    pendingEmail.value = res.data.email;
    codeSent.value = true;
  } catch (err) {
    emailError.value = err.response?.data?.errors?.new_email?.[0]
      || err.response?.data?.message
      || 'エラーが発生しました。';
  } finally {
    emailChanging.value = false;
  }
};

const confirmEmailChange = async () => {
  codeError.value = '';
  confirming.value = true;
  try {
    const res = await axios.post('/api/parent/confirm-email-change', { code: confirmCode.value });
    // ストアのユーザー情報を更新
    if (authStore.user) {
      authStore.user.email = res.data.email;
    }
    emailChangeSuccess.value = true;
    resetEmailChange();
  } catch (err) {
    codeError.value = err.response?.data?.errors?.code?.[0]
      || err.response?.data?.message
      || '認証コードが正しくありません。';
  } finally {
    confirming.value = false;
  }
};

const resetEmailChange = () => {
  newEmail.value      = '';
  pendingEmail.value  = '';
  confirmCode.value   = '';
  codeSent.value      = false;
  emailError.value    = '';
  codeError.value     = '';
};
</script>
