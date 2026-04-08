<template>
  <div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h1 class="text-2xl font-bold">保護者一覧</h1>
      <router-link v-if="isSuperAdmin" to="/admin/parents/create">
        <Button variant="primary">新規登録</Button>
      </router-link>
    </div>
    
    <!-- 検索フィルター -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <Input
          v-model="filters.search"
          placeholder="氏名・メールで検索"
          @keyup.enter="fetchData"
        />
        <Button variant="primary" @click="fetchData">検索</Button>
        <Button variant="secondary" @click="resetFilters">クリア</Button>
      </div>
    </div>
    
    <!-- データテーブル -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500">
        読み込み中...
      </div>
      
      <div v-else-if="parents.length === 0" class="p-8 text-center text-gray-500">
        データがありません
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">氏名</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">続柄</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">電話番号</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">メール</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">生徒ID</th>
              <th v-if="isSuperAdmin" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in parents" :key="item.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ item.parent_name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.parent_relationship }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.parent_tel }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.parent_email }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.seito_id }}</td>
              <td v-if="isSuperAdmin" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <router-link
                  :to="`/admin/parents/${item.id}/edit`"
                  class="text-blue-600 hover:text-blue-900 mr-4"
                >
                  編集
                </router-link>
                <button
                  @click="confirmDelete(item)"
                  class="text-red-600 hover:text-red-900"
                >
                  削除
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- ページネーション -->
      <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
        <div class="flex justify-between items-center">
          <div class="text-sm text-gray-700">
            全{{ pagination.total }}件中 {{ pagination.from }}-{{ pagination.to }}件を表示
          </div>
          <div class="flex gap-2">
            <Button
              variant="secondary"
              size="sm"
              :disabled="pagination.current_page === 1"
              @click="changePage(pagination.current_page - 1)"
            >
              前へ
            </Button>
            <Button
              variant="secondary"
              size="sm"
              :disabled="pagination.current_page === pagination.last_page"
              @click="changePage(pagination.current_page + 1)"
            >
              次へ
            </Button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- 削除確認モーダル -->
    <Modal
      :show="showDeleteModal"
      title="削除確認"
      @close="showDeleteModal = false"
      @confirm="handleDelete"
    >
      <p>「{{ deleteTarget?.parent_name }}」を削除してもよろしいですか？</p>
      <p class="text-sm text-red-600 mt-2">※ この操作は取り消せません</p>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAdminStore } from '../../../stores/admin';
import { useAuthStore } from '../../../stores/auth';
import Button from '../../../components/Button.vue';
import Input from '../../../components/Input.vue';
import Modal from '../../../components/Modal.vue';

const adminStore = useAdminStore();
const authStore = useAuthStore();

const isSuperAdmin = computed(() => {
  return authStore.user?.is_super_admin ?? false;
});

const parents = ref([]);
const loading = ref(false);
const showDeleteModal = ref(false);
const deleteTarget = ref(null);

const filters = reactive({
  search: ''
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
});

const fetchData = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      search: filters.search || undefined
    };
    
    const response = await adminStore.fetchParents(params);
    parents.value = response.data || response;
    
    if (response.current_page) {
      Object.assign(pagination, {
        current_page: response.current_page,
        last_page: response.last_page,
        from: response.from,
        to: response.to,
        total: response.total
      });
    }
  } catch (error) {
    console.error('データ取得エラー:', error);
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.search = '';
  fetchData();
};

const changePage = (page) => {
  fetchData(page);
};

const confirmDelete = (item) => {
  deleteTarget.value = item;
  showDeleteModal.value = true;
};

const handleDelete = async () => {
  try {
    await adminStore.deleteParent(deleteTarget.value.id);
    showDeleteModal.value = false;
    fetchData(pagination.current_page);
  } catch (error) {
    alert(error.response?.data?.message || '削除に失敗しました');
  }
};

onMounted(() => {
  fetchData();
});
</script>
