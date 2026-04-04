<template>
  <div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h1 class="text-2xl font-bold">生徒一覧</h1>
      <router-link to="/admin/students/create">
        <Button variant="primary">新規登録</Button>
      </router-link>
    </div>
    
    <!-- 検索フィルター -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid gap-4" :class="isSuperAdmin ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-5' : 'grid-cols-1 sm:grid-cols-3'">
        <Input
          v-model="filters.search"
          placeholder="生徒ID・氏名で検索"
          @keyup.enter="fetchData"
        />
        <Select
          v-if="isSuperAdmin"
          v-model="filters.grade"
          :options="gradeOptions"
          placeholder="学年で絞り込み"
          @change="onGradeChange"
        />
        <Select
          v-if="isSuperAdmin"
          v-model="filters.class_type"
          :options="classTypeOptions"
          placeholder="クラスで絞り込み"
          :disabled="!filters.grade"
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
      
      <div v-else-if="students.length === 0" class="p-8 text-center text-gray-500">
        データがありません
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">生徒ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">氏名</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">出席番号</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">クラス</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in students" :key="item.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.seito_id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ item.seito_name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.seito_number }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.class_model?.class_name || '-' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <router-link
                  :to="`/admin/students/${item.id}/edit`"
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
      <p>「{{ deleteTarget?.seito_name }}」を削除してもよろしいですか？</p>
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
import Select from '../../../components/Select.vue';
import Modal from '../../../components/Modal.vue';

const adminStore = useAdminStore();
const authStore = useAuthStore();

const students = ref([]);
const classes = ref([]);
const loading = ref(false);
const showDeleteModal = ref(false);
const deleteTarget = ref(null);

const isSuperAdmin = computed(() => {
  return authStore.user?.is_super_admin ?? false;
});

const filters = reactive({
  search: '',
  grade: '',
  class_type: ''
});

const gradeOptions = [
  { value: '1', label: '1年' },
  { value: '2', label: '2年' },
  { value: '3', label: '3年' }
];

const classTypes = [
  { value: '特進', label: '特進' },
  { value: '進学', label: '進学' },
  { value: '調理', label: '調理' },
  { value: '情会', label: '情会' },
  { value: '福祉', label: '福祉' },
  { value: '総合１', label: '総合１' },
  { value: '総合２', label: '総合２' },
  { value: '総合３', label: '総合３' }
];

const classTypeOptions = computed(() => {
  if (!filters.grade) {
    return [];
  }
  return classTypes.map(ct => ({
    value: ct.value,
    label: `${filters.grade}${ct.label}`
  }));
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
});

const onGradeChange = () => {
  // 学年が変更されたらクラス選択をリセット
  filters.class_type = '';
};

const classOptions = computed(() => {
  return classes.value.map(c => ({
    value: c.class_id,
    label: c.class_name
  }));
});

const fetchClasses = async () => {
  try {
    const response = await adminStore.fetchClasses({ per_page: 100 });
    classes.value = response.data || response;
  } catch (error) {
    console.error('クラス取得エラー:', error);
  }
};

const fetchData = async (page = 1) => {
  loading.value = true;
  try {
    // 学年とクラスタイプからclass_nameを構築
    let className = '';
    if (filters.grade && filters.class_type) {
      className = `${filters.grade}${filters.class_type}`;
    }
    
    const params = {
      page,
      search: filters.search || undefined,
      class_name: className || undefined
    };
    
    const response = await adminStore.fetchStudents(params);
    students.value = response.data || response;
    
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
  filters.grade = '';
  filters.class_type = '';
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
    await adminStore.deleteStudent(deleteTarget.value.id);
    showDeleteModal.value = false;
    fetchData(pagination.current_page);
  } catch (error) {
    alert(error.response?.data?.message || '削除に失敗しました');
  }
};

onMounted(() => {
  fetchClasses();
  fetchData();
});
</script>
