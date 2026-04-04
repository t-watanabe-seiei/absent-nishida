<template>
  <div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h1 class="text-2xl font-bold">クラス一覧</h1>
      <router-link to="/admin/classes/create">
        <Button variant="primary">新規登録</Button>
      </router-link>
    </div>
    
    <!-- 検索フィルター -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Select
          v-model="filters.class_name"
          :options="classNameOptions"
          placeholder="クラス名を選択"
        />
        <Input
          v-model="filters.teacher_name"
          placeholder="教員名で検索"
          @keyup.enter="fetchData"
        />
        <Input
          v-model="filters.year_id"
          type="number"
          placeholder="年度"
          @keyup.enter="fetchData"
        />
        <div class="flex gap-2">
          <Button variant="primary" @click="fetchData">検索</Button>
          <Button variant="secondary" @click="resetFilters">クリア</Button>
        </div>
      </div>
    </div>
    
    <!-- データテーブル -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500">
        読み込み中...
      </div>
      
      <div v-else-if="classes.length === 0" class="p-8 text-center text-gray-500">
        データがありません
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">クラスID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">クラス名</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">担任</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">メール</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">年度</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in classes" :key="item.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.class_id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ item.class_name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.teacher_name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.teacher_email }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.year_id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <router-link
                  :to="`/admin/classes/${item.id}`"
                  class="text-green-600 hover:text-green-900 mr-4"
                >
                  詳細
                </router-link>
                <router-link
                  :to="`/admin/classes/${item.id}/edit`"
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
      <p>「{{ deleteTarget?.class_name }}」を削除してもよろしいですか？</p>
      <p class="text-sm text-red-600 mt-2">※ この操作は取り消せません</p>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useAdminStore } from '../../../stores/admin';
import Button from '../../../components/Button.vue';
import Input from '../../../components/Input.vue';
import Select from '../../../components/Select.vue';
import Modal from '../../../components/Modal.vue';

const adminStore = useAdminStore();

const classes = ref([]);
const loading = ref(false);
const showDeleteModal = ref(false);
const deleteTarget = ref(null);

// クラス名の選択肢
const classNameOptions = [
  { value: '1情会', label: '1情会' },
  { value: '1特進', label: '1特進' },
  { value: '1福祉', label: '1福祉' },
  { value: '1総合１', label: '1総合１' },
  { value: '1総合２', label: '1総合２' },
  { value: '1総合３', label: '1総合３' },
  { value: '1調理', label: '1調理' },
  { value: '1進学', label: '1進学' },
  { value: '2情会', label: '2情会' },
  { value: '2特進', label: '2特進' },
  { value: '2福祉', label: '2福祉' },
  { value: '2総合１', label: '2総合１' },
  { value: '2総合２', label: '2総合２' },
  { value: '2総合３', label: '2総合３' },
  { value: '2調理', label: '2調理' },
  { value: '2進学', label: '2進学' },
  { value: '3情会', label: '3情会' },
  { value: '3特進', label: '3特進' },
  { value: '3福祉', label: '3福祉' },
  { value: '3総合１', label: '3総合１' },
  { value: '3総合２', label: '3総合２' },
  { value: '3総合３', label: '3総合３' },
  { value: '3調理', label: '3調理' },
  { value: '3進学', label: '3進学' }
];

const filters = reactive({
  class_name: '',
  teacher_name: '',
  year_id: ''
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
      per_page: 50,
      class_name: filters.class_name || undefined,
      teacher_name: filters.teacher_name || undefined,
      year_id: filters.year_id || undefined
    };
    
    const response = await adminStore.fetchClasses(params);
    classes.value = response.data || response;
    
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
  filters.class_name = '';
  filters.teacher_name = '';
  filters.year_id = '';
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
    await adminStore.deleteClass(deleteTarget.value.id);
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
