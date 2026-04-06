import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// レイアウト
import GuestLayout from '../layouts/GuestLayout.vue';
import AdminLayout from '../layouts/AdminLayout.vue';
import ParentLayout from '../layouts/ParentLayout.vue';

// 認証画面
import LoginSelect from '../pages/auth/LoginSelect.vue';
import AdminLogin from '../pages/auth/AdminLogin.vue';
import StudentLogin from '../pages/auth/StudentLogin.vue';
import ParentLogin from '../pages/auth/ParentLogin.vue';
import Register from '../pages/auth/Register.vue';
import RegisterParent from '../pages/auth/RegisterParent.vue';
import TwoFactorVerify from '../pages/auth/TwoFactorVerify.vue';
import ParentTwoFactorVerify from '../pages/auth/ParentTwoFactorVerify.vue';
import ParentEmailRegister from '../pages/auth/ParentEmailRegister.vue';

// 管理者画面
import AdminDashboard from '../pages/admin/Dashboard.vue';
import ClassList from '../pages/admin/classes/List.vue';
import ClassForm from '../pages/admin/classes/Form.vue';
import ClassDetail from '../pages/admin/classes/Detail.vue';
import StudentList from '../pages/admin/students/List.vue';
import StudentForm from '../pages/admin/students/Form.vue';
import ParentList from '../pages/admin/parents/List.vue';
import ParentForm from '../pages/admin/parents/Form.vue';
import AdminAbsenceList from '../pages/admin/absences/List.vue';
import TodayAbsenceList from '../pages/admin/absences/TodayList.vue';
import CsvImport from '../pages/admin/import/Index.vue';
import AnnouncementList from '../pages/admin/announcements/List.vue';
import AnnouncementForm from '../pages/admin/announcements/Form.vue';
import AnnouncementDetail from '../pages/admin/announcements/Detail.vue';
import AdminSettings from '../pages/admin/Settings.vue';

// 保護者画面
import ParentDashboard from '../pages/parent/Dashboard.vue';
import ParentAbsenceList from '../pages/parent/absences/List.vue';
import ParentAbsenceForm from '../pages/parent/absences/Form.vue';
import PasswordChange from '../pages/parent/PasswordChange.vue';

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  // ログイン選択画面
  {
    path: '/login',
    component: GuestLayout,
    children: [
      {
        path: '',
        name: 'login.select',
        component: LoginSelect,
        meta: { guest: true }
      }
    ]
  },
  // 生徒認証
  {
    path: '/student/login',
    component: GuestLayout,
    children: [
      {
        path: '',
        name: 'student.login',
        component: StudentLogin,
        meta: { guest: true }
      }
    ]
  },
  // 初回登録
  {
    path: '/register',
    component: GuestLayout,
    children: [
      {
        path: '',
        name: 'register',
        component: Register,
        meta: { guest: true }
      }
    ]
  },
  // 保護者情報登録
  {
    path: '/register/parent',
    component: GuestLayout,
    children: [
      {
        path: '',
        name: 'register.parent',
        component: RegisterParent,
        meta: { guest: true }
      }
    ]
  },
  // 管理者認証
  {
    path: '/admin/login',
    component: GuestLayout,
    children: [
      {
        path: '',
        name: 'admin.login',
        component: AdminLogin,
        meta: { guest: true }
      }
    ]
  },
  {
    path: '/admin/verify-2fa',
    component: GuestLayout,
    children: [
      {
        path: '',
        name: 'admin.verify2fa',
        component: TwoFactorVerify,
        meta: { guest: true, guard: 'admin' }
      }
    ]
  },
  // 管理者画面
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, guard: 'admin' },
    children: [
      {
        path: 'dashboard',
        name: 'admin.dashboard',
        component: AdminDashboard
      },
      {
        path: 'classes',
        name: 'admin.classes',
        component: ClassList
      },
      {
        path: 'classes/create',
        name: 'admin.classes.create',
        component: ClassForm
      },
      {
        path: 'classes/:id',
        name: 'admin.classes.detail',
        component: ClassDetail
      },
      {
        path: 'classes/:id/edit',
        name: 'admin.classes.edit',
        component: ClassForm
      },
      {
        path: 'students',
        name: 'admin.students',
        component: StudentList
      },
      {
        path: 'students/create',
        name: 'admin.students.create',
        component: StudentForm
      },
      {
        path: 'students/:id/edit',
        name: 'admin.students.edit',
        component: StudentForm
      },
      {
        path: 'parents',
        name: 'admin.parents',
        component: ParentList
      },
      {
        path: 'parents/create',
        name: 'admin.parents.create',
        component: ParentForm
      },
      {
        path: 'parents/:id/edit',
        name: 'admin.parents.edit',
        component: ParentForm
      },
      {
        path: 'absences',
        name: 'admin.absences',
        component: AdminAbsenceList
      },
      {
        path: 'absences/today',
        name: 'admin.absences.today',
        component: TodayAbsenceList
      },
      {
        path: 'import',
        name: 'admin.import',
        component: CsvImport
      },
      {
        path: 'announcements',
        name: 'admin.announcements',
        component: AnnouncementList
      },
      {
        path: 'announcements/create',
        name: 'admin.announcements.create',
        component: AnnouncementForm
      },
      {
        path: 'announcements/:id',
        name: 'admin.announcements.detail',
        component: AnnouncementDetail
      },
      {
        path: 'announcements/:id/edit',
        name: 'admin.announcements.edit',
        component: AnnouncementForm
      },
      {
        path: 'settings',
        name: 'admin.settings',
        component: AdminSettings
      }
    ]
  },
  // 保護者認証
  {
    path: '/parent/login',
    component: GuestLayout,
    children: [
      {
        path: '',
        name: 'parent.login',
        component: ParentLogin,
        meta: { guest: true }
      }
    ]
  },
  {
    path: '/parent/register-email',
    component: GuestLayout,
    children: [
      {
        path: '',
        name: 'parent.registerEmail',
        component: ParentEmailRegister,
        meta: { guest: true }
      }
    ]
  },
  {
    path: '/parent/verify-2fa',
    component: GuestLayout,
    children: [
      {
        path: '',
        name: 'parent.verify2fa',
        component: ParentTwoFactorVerify,
        meta: { guest: true, guard: 'parent' }
      }
    ]
  },
  // 保護者画面
  {
    path: '/parent',
    component: ParentLayout,
    meta: { requiresAuth: true, guard: 'parent' },
    children: [
      {
        path: 'dashboard',
        name: 'parent.dashboard',
        component: ParentDashboard
      },
      {
        path: 'absences',
        name: 'parent.absences',
        component: ParentAbsenceList
      },
      {
        path: 'absences/create',
        name: 'parent.absences.create',
        component: ParentAbsenceForm
      },
      {
        path: 'absences/:id/edit',
        name: 'parent.absences.edit',
        component: ParentAbsenceForm
      },
      {
        path: 'change-password',
        name: 'parent.changePassword',
        component: PasswordChange
      }
    ]
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// ナビゲーションガード
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  
  // 認証が必要なルート
  if (to.meta.requiresAuth) {
    if (!authStore.isAuthenticated) {
      const guard = to.meta.guard || 'admin';
      next({ name: `${guard}.login` });
    } else if (to.meta.guard && authStore.guard !== to.meta.guard) {
      // 異なるガードの場合はリダイレクト
      next({ name: `${authStore.guard}.dashboard` });
    } else {
      next();
    }
  }
  // ゲスト専用ルート
  else if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: `${authStore.guard}.dashboard` });
  }
  else {
    next();
  }
});

export default router;
