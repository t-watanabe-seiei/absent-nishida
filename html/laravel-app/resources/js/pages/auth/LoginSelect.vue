<template>
  <div>


    <h2 class="text-2xl font-bold text-center mb-6">誠英高校</h2>
  <!--
    <p class="text-center text-gray-600 mb-8">ログインするユーザーを選択してください</p>
  -->

    <div class="space-y-4">
      <Button
        variant="primary"
        class="w-full py-4 text-lg"
        @click="goToParentLogin"
      >
        保護者ログイン
      </Button>


<!-- 
      <Button
        variant="secondary"
        class="w-full py-4 text-lg"
        @click="goToRegister"
      >
        初回登録
      </Button>
-->

      <div class="text-center mt-6">
        <button
          @click="goToAdminLogin"
          class="text-sm text-gray-500 hover:text-gray-700 underline"
        >
          管理者ログインはこちら
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import Button from '../../components/Button.vue';

const router = useRouter();
const authStore = useAuthStore();

const goToParentLogin = () => {
  router.push({ name: 'parent.login' });
};

const goToRegister = () => {
  router.push({ name: 'register' });
};

const goToAdminLogin = () => {
  router.push({ name: 'admin.login' });
};
</script>

