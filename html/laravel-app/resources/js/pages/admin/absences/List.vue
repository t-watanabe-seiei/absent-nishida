<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">欠席記録</h1>
      
      <!-- 担任の場合のみ表示：全クラス表示切り替えボタン -->
      <div v-if="!isSuperAdmin && hasClassId" class="flex items-center gap-2">
        <span class="text-sm text-gray-600">
          {{ showAllClasses ? '全クラス表示中' : '担当クラスのみ表示中' }}
        </span>
        <Button
          :variant="showAllClasses ? 'secondary' : 'primary'"
          size="sm"
          @click="toggleClassFilter"
        >
          {{ showAllClasses ? '🔒 担当クラスのみ表示' : '🔓 全クラスを表示' }}
        </Button>
      </div>
    </div>
    
    <!-- 統計情報カード -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div 
        @click="filterByToday"
        class="bg-white rounded-lg shadow p-4 cursor-pointer hover:shadow-lg transition-shadow"
        :class="{ 'ring-2 ring-red-500': isFilteredByToday }"
      >
        <h3 class="text-sm font-semibold text-gray-600 mb-1">本日の欠席</h3>
        <p class="text-2xl font-bold text-red-600">{{ stats.today }}人</p>
        <p v-if="isFilteredByToday" class="text-xs text-red-500 mt-1">📌 フィルター中</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-semibold text-gray-600 mb-1">今週の欠席</h3>
        <p class="text-2xl font-bold text-orange-600">{{ stats.week }}人</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-semibold text-gray-600 mb-1">今月の欠席</h3>
        <p class="text-2xl font-bold text-blue-600">{{ stats.month }}人</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-semibold text-gray-600 mb-1">総欠席数</h3>
        <p class="text-2xl font-bold text-purple-600">{{ stats.total }}人</p>
      </div>
    </div>

    <!-- 月別欠席者数グラフ -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">月別欠席者数</h2>
      
      <!-- 直近3ヶ月（常に表示） -->
      <div class="space-y-2 mb-4">
        <div v-for="(item, index) in recentMonths" :key="index" class="flex items-center">
          <div class="w-24 text-sm text-gray-600 font-semibold">{{ item.month }}</div>
          <div class="flex-1">
            <div class="h-8 bg-gray-200 rounded overflow-hidden">
              <div 
                class="h-full bg-blue-500 flex items-center justify-end pr-2 text-white text-sm font-semibold"
                :style="{ width: getBarWidth(item.count) }"
              >
                <span v-if="item.count > 0">{{ item.count }}人</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- 過去のデータ（開閉可能） -->
      <div v-if="olderMonths.length > 0">
        <button
          @click="showOlderMonths = !showOlderMonths"
          class="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 mb-2"
        >
          <svg 
            xmlns="http://www.w3.org/2000/svg" 
            class="h-4 w-4 transition-transform"
            :class="{ 'rotate-180': showOlderMonths }"
            fill="none" 
            viewBox="0 0 24 24" 
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
          <span>{{ showOlderMonths ? '過去のデータを閉じる' : '過去のデータを開く' }}</span>
        </button>
        
        <div v-show="showOlderMonths" class="space-y-2 pl-4 border-l-2 border-gray-300">
          <div v-for="(item, index) in olderMonths" :key="index" class="flex items-center">
            <div class="w-24 text-sm text-gray-500">{{ item.month }}</div>
            <div class="flex-1">
              <div class="h-8 bg-gray-200 rounded overflow-hidden">
                <div 
                  class="h-full bg-gray-400 flex items-center justify-end pr-2 text-white text-sm font-semibold"
                  :style="{ width: getBarWidth(item.count) }"
                >
                  <span v-if="item.count > 0">{{ item.count }}人</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- 検索フィルター -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <h2 class="text-lg font-semibold mb-4">検索フィルター</h2>
      <div class="grid gap-4" :class="isSuperAdmin ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-2'">
        <Input
          v-model="filters.date_from"
          type="date"
          label="開始日"
          placeholder="開始日"
        />
        <Input
          v-model="filters.date_to"
          type="date"
          label="終了日"
          placeholder="終了日"
        />
        <Select
          v-if="isSuperAdmin"
          v-model="filters.class_name"
          :options="classNameOptions"
          placeholder="クラスで絞り込み"
          label="クラス"
        />
        <Select
          v-model="filters.division"
          :options="divisionOptions"
          placeholder="区分で絞り込み"
          label="区分"
        />
        <Select
          v-if="isSuperAdmin"
          v-model="filters.grade"
          :options="gradeOptions"
          placeholder="学年で絞り込み"
          label="学年"
        />
        <div class="flex items-end gap-2">
          <Button variant="primary" @click="fetchAbsences" class="flex-1">検索</Button>
          <Button variant="secondary" @click="resetFilters" class="flex-1">クリア</Button>
        </div>
      </div>
    </div>
    
    <!-- データテーブル -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold">
          <span v-if="isFilteredByToday" class="text-red-600">📌 本日の欠席</span>
          <span v-else>欠席一覧</span>
          <span v-if="filters.date_from || filters.date_to" class="text-sm text-gray-600 ml-2">
            <template v-if="filters.date_from && filters.date_to">
              ({{ filters.date_from }} 〜 {{ filters.date_to }})
            </template>
            <template v-else-if="filters.date_from">
              ({{ filters.date_from }} 以降)
            </template>
            <template v-else>
              (〜 {{ filters.date_to }})
            </template>
          </span>
        </h2>
        <Button variant="secondary" size="sm" :disabled="downloading" @click="downloadCsv">
          {{ downloading ? 'ダウンロード中...' : '📥 CSVダウンロード' }}
        </Button>
        <Button variant="primary" size="sm" @click="openCreateModal">
          ＋ 欠席連絡登録
        </Button>
      </div>
      
      <div v-if="loading" class="p-8 text-center text-gray-500">
        読み込み中...
      </div>
      
      <div v-else-if="absences.length === 0" class="p-8 text-center text-gray-500">
        該当する欠席情報はありません
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日付</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">学年</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">クラス</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">出席番号</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">氏名</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">区分</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">理由</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">予定時刻</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
            </tr>
          </thead>
          <tbody class="bg-white">
            <template v-for="(item, index) in absences" :key="item.id">
              <!-- 日付が変わる場合は区切り線を表示 -->
              <tr v-if="index > 0 && item.absence_date !== absences[index - 1].absence_date">
                <td colspan="9" class="px-0 py-0">
                  <div class="border-t-4 border-green-200"></div>
                </td>
              </tr>
              <!-- データ行 -->
              <tr class="hover:bg-gray-50 border-b border-gray-200">
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(item.absence_date) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ getGrade(item.student?.class_model?.class_name) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.student?.class_model?.class_name || '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.student?.seito_number || '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ item.student?.seito_name || '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span 
                    class="px-2 py-1 rounded text-xs font-semibold"
                    :class="getDivisionClass(item.division)"
                  >
                    {{ item.division }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm">{{ item.reason }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.scheduled_time || '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <template v-if="item.is_admin_created">
                    <button @click="openEditModal(item)" class="text-blue-600 hover:text-blue-900 mr-3">編集</button>
                    <button @click="confirmDeleteAbsence(item)" class="text-red-600 hover:text-red-900">削除</button>
                  </template>
                  <span v-else class="text-xs text-gray-400">保護者入力</span>
                </td>
              </tr>
            </template>
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

    <!-- 欠席連絡登録モーダル -->
    <Modal
      :show="showCreateModal"
      title="欠席連絡登録（管理者）"
      @close="showCreateModal = false"
      @confirm="handleCreate"
    >
      <div class="space-y-4">
        <Select
          v-model="createForm.seito_id"
          :options="studentOptions"
          placeholder="生徒を選択"
          label="生徒"
        />
        <Select
          v-model="createForm.division"
          :options="divisionOptions"
          placeholder="区分を選択"
          label="区分"
        />
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">欠席日</label>
          <input
            v-model="createForm.absence_date"
            type="date"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">理由</label>
          <textarea
            v-model="createForm.reason"
            rows="3"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>
        <div v-if="createForm.division === '遅刻'">
          <label class="block text-sm font-medium text-gray-700 mb-1">登校予定時刻</label>
          <input
            v-model="createForm.scheduled_time"
            type="text"
            placeholder="例: 10:30"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>
    </Modal>

    <!-- 欠席連絡編集モーダル -->
    <Modal
      :show="showEditModal"
      title="欠席連絡編集（管理者）"
      @close="showEditModal = false"
      @confirm="handleUpdate"
    >
      <div class="space-y-4">
        <Select
          v-model="editForm.division"
          :options="divisionOptions"
          placeholder="区分を選択"
          label="区分"
        />
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">欠席日</label>
          <input
            v-model="editForm.absence_date"
            type="date"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">理由</label>
          <textarea
            v-model="editForm.reason"
            rows="3"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>
        <div v-if="editForm.division === '遅刻'">
          <label class="block text-sm font-medium text-gray-700 mb-1">登校予定時刻</label>
          <input
            v-model="editForm.scheduled_time"
            type="text"
            placeholder="例: 10:30"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>
    </Modal>

    <!-- 欠席連絡削除確認モーダル -->
    <Modal
      :show="showDeleteAbsenceModal"
      title="削除確認"
      @close="showDeleteAbsenceModal = false"
      @confirm="handleDeleteAbsence"
    >
      <p>「{{ deleteAbsenceTarget?.student?.seito_name }}」の欠席連絡（{{ formatDate(deleteAbsenceTarget?.absence_date) }}）を削除してもよろしいですか？</p>
      <p class="text-sm text-red-600 mt-2">※ この操作は取り消せません</p>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../../stores/auth';
import Button from '../../../components/Button.vue';
import Input from '../../../components/Input.vue';
import Select from '../../../components/Select.vue';
import Modal from '../../../components/Modal.vue';

const authStore = useAuthStore();

const absences = ref([]);
const stats = ref({
  today: 0,
  week: 0,
  month: 0,
  total: 0
});
const monthlyStats = ref([]);
const showOlderMonths = ref(false);
const loading = ref(false);
const downloading = ref(false);
const showAllClasses = ref(false);

const isSuperAdmin = computed(() => {
  return authStore.user?.is_super_admin ?? false;
});

const hasClassId = computed(() => {
  return authStore.user?.class_id != null;
});

const filters = reactive({
  date_from: '',
  date_to: '',
  class_name: '',
  division: '',
  grade: ''
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
});

// 直近3ヶ月のデータ
const recentMonths = computed(() => {
  return monthlyStats.value.slice(0, 3);
});

// 3ヶ月より前のデータ
const olderMonths = computed(() => {
  return monthlyStats.value.slice(3);
});

// 本日の欠席でフィルター中かどうか
const isFilteredByToday = computed(() => {
  const today = new Date();
  const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  return filters.date_from === todayStr && filters.date_to === todayStr;
});

const divisionOptions = [
  { value: '欠席', label: '欠席' },
  { value: '遅刻', label: '遅刻' },
  { value: '早退', label: '早退' }
];

const gradeOptions = [
  { value: '1', label: '1年' },
  { value: '2', label: '2年' },
  { value: '3', label: '3年' }
];

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

const maxMonthlyCount = computed(() => {
  return Math.max(...monthlyStats.value.map(m => m.count), 1);
});

const getBarWidth = (count) => {
  if (count === 0) return '0%';
  return `${(count / maxMonthlyCount.value) * 100}%`;
};

const getGrade = (className) => {
  if (!className) return '-';
  const match = className.match(/^(\d)/);
  return match ? `${match[1]}年` : '-';
};

const getDivisionClass = (division) => {
  switch (division) {
    case '欠席':
      return 'bg-red-100 text-red-800';
    case '遅刻':
      return 'bg-yellow-100 text-yellow-800';
    case '早退':
      return 'bg-blue-100 text-blue-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const formatDate = (date) => {
  if (!date) return '-';
  const d = new Date(date);
  return `${d.getFullYear()}/${String(d.getMonth() + 1).padStart(2, '0')}/${String(d.getDate()).padStart(2, '0')}`;
};

const fetchStats = async () => {
  try {
    const params = {};
    if (showAllClasses.value) {
      params.show_all_classes = 'true';
    }
    const response = await axios.get('/api/admin/absences/stats', { params });
    stats.value = response.data;
  } catch (error) {
    console.error('統計データ取得エラー:', error);
  }
};

const fetchMonthlyStats = async () => {
  try {
    const params = {};
    if (showAllClasses.value) {
      params.show_all_classes = 'true';
    }
    const response = await axios.get('/api/admin/absences/monthly', { params });
    monthlyStats.value = response.data;
  } catch (error) {
    console.error('月別統計データ取得エラー:', error);
  }
};

const fetchAbsences = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      page,
      date_from: filters.date_from || undefined,
      date_to: filters.date_to || undefined,
      class_name: filters.class_name || undefined,
      division: filters.division || undefined,
      grade: filters.grade || undefined
    };
    
    // 担任が全クラス表示を選択している場合
    if (showAllClasses.value) {
      params.show_all_classes = 'true';
    }
    
    const response = await axios.get('/api/admin/absences', { params });
    absences.value = response.data.data || response.data;
    
    if (response.data.current_page) {
      Object.assign(pagination, {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        from: response.data.from,
        to: response.data.to,
        total: response.data.total
      });
    }
  } catch (error) {
    console.error('データ取得エラー:', error);
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.date_from = '';
  filters.date_to = '';
  filters.class_name = '';
  filters.division = '';
  filters.grade = '';
  fetchAbsences();
};

const filterByToday = () => {
  const today = new Date();
  const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  filters.date_from = todayStr;
  filters.date_to = todayStr;
  filters.division = '欠席';
  fetchAbsences();
};

const changePage = (page) => {
  fetchAbsences(page);
};

const downloadCsv = async () => {
  downloading.value = true;
  try {
    const params = {
      date_from: filters.date_from || undefined,
      date_to: filters.date_to || undefined,
      class_name: filters.class_name || undefined,
      division: filters.division || undefined,
      grade: filters.grade || undefined,
    };
    if (showAllClasses.value) {
      params.show_all_classes = 'true';
    }

    const response = await axios.get('/api/admin/absences/export', {
      params,
      responseType: 'blob',
    });

    const today = new Date();
    const dateStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    const url = URL.createObjectURL(new Blob([response.data], { type: 'text/csv; charset=UTF-8' }));
    const a = document.createElement('a');
    a.href = url;
    a.download = `absences_${dateStr}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  } catch (error) {
    console.error('CSVダウンロードエラー:', error);
    alert('CSVのダウンロードに失敗しました');
  } finally {
    downloading.value = false;
  }
};

const toggleClassFilter = () => {
  showAllClasses.value = !showAllClasses.value;
  // データを再取得
  fetchStats();
  fetchMonthlyStats();
  fetchAbsences();
};

// ─── 管理者による欠席連絡 CRUD ───────────────────────────
const students = ref([]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteAbsenceModal = ref(false);
const editTarget = ref(null);
const deleteAbsenceTarget = ref(null);

const createForm = reactive({
  seito_id: '',
  division: '欠席',
  reason: '',
  absence_date: '',
  scheduled_time: ''
});

const editForm = reactive({
  division: '',
  reason: '',
  absence_date: '',
  scheduled_time: ''
});

const studentOptions = computed(() => {
  return students.value.map(s => ({
    value: s.seito_id,
    label: `${s.seito_name} (${s.seito_id})`
  }));
});

const fetchStudentsForForm = async () => {
  try {
    const response = await axios.get('/api/admin/students', { params: { per_page: 200 } });
    students.value = response.data.data || response.data;
  } catch (error) {
    console.error('生徒データ取得エラー:', error);
  }
};

const openCreateModal = () => {
  createForm.seito_id = '';
  createForm.division = '欠席';
  createForm.reason = '';
  createForm.absence_date = '';
  createForm.scheduled_time = '';
  showCreateModal.value = true;
};

const handleCreate = async () => {
  try {
    await axios.post('/api/admin/absences', createForm);
    showCreateModal.value = false;
    fetchAbsences(pagination.current_page);
    fetchStats();
    fetchMonthlyStats();
  } catch (error) {
    alert(error.response?.data?.message || '登録に失敗しました');
  }
};

const openEditModal = (item) => {
  editTarget.value = item;
  editForm.division = item.division;
  editForm.reason = item.reason;
  editForm.absence_date = item.absence_date ? String(item.absence_date).substring(0, 10) : '';
  editForm.scheduled_time = item.scheduled_time || '';
  showEditModal.value = true;
};

const handleUpdate = async () => {
  try {
    await axios.put(`/api/admin/absences/${editTarget.value.id}`, editForm);
    showEditModal.value = false;
    fetchAbsences(pagination.current_page);
  } catch (error) {
    alert(error.response?.data?.message || '更新に失敗しました');
  }
};

const confirmDeleteAbsence = (item) => {
  deleteAbsenceTarget.value = item;
  showDeleteAbsenceModal.value = true;
};

const handleDeleteAbsence = async () => {
  try {
    await axios.delete(`/api/admin/absences/${deleteAbsenceTarget.value.id}`);
    showDeleteAbsenceModal.value = false;
    fetchAbsences(pagination.current_page);
    fetchStats();
    fetchMonthlyStats();
  } catch (error) {
    alert(error.response?.data?.message || '削除に失敗しました');
  }
};

onMounted(() => {
  fetchStats();
  fetchMonthlyStats();
  fetchAbsences();
  fetchStudentsForForm();
});
</script>
