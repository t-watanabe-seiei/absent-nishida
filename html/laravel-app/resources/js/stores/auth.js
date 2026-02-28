import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    guard: null, // 'admin' or 'parent'
    isAuthenticated: false,
    needs2FA: false,
    email: null,
    needsPasswordChange: false,
    loginType: null, // 'student' or 'parent' or 'admin'
    needsEmailRegistration: false,
  }),

  getters: {
    isAdmin: (state) => state.guard === 'admin',
    isParent: (state) => state.guard === 'parent'
  },

  actions: {
    // 管理者ログイン（2FAなし）
    async adminLogin(credentials) {
      try {
        const response = await axios.post('/api/admin/login', credentials);
        this.user = response.data.admin;
        this.isAuthenticated = true;
        this.guard = 'admin';
        this.loginType = 'admin';
        this.needs2FA = false;
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    // 保護者ログイン（2FA必須）
    async parentLogin(credentials) {
      try {
        console.log('📡 API呼び出し: /api/parent/login');
        const response = await axios.post('/api/parent/login', credentials);
        console.log('📨 APIレスポンス:', response.data);
                // メールアドレス未登録の場合（初回ログイン）
        if (response.data.requires_email_registration) {
          this.needsEmailRegistration = true;
          this.guard = 'parent';
          this.loginType = 'parent';
          return response.data;
        }
        // 2FAが必要な場合
        if (response.data.requires_2fa) {
          console.log('🔐 2FA必須 - ストア状態を更新');
          this.needs2FA = true;
          this.email = response.data.email;
          this.guard = 'parent';
          this.loginType = 'parent';
          return response.data;
        }
        
        // 直接ログイン成功（後方互換性のため残す）
        console.log('⚠️ 2FAスキップ - 直接ログイン');
        this.user = response.data.parent;
        this.isAuthenticated = true;
        this.guard = 'parent';
        this.loginType = 'parent';
        this.needs2FA = false;
        this.needsPasswordChange = response.data.needs_password_change || false;
        
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    // 生徒ログイン（2FA不要）
    async studentLogin(credentials) {
      try {
        const response = await axios.post('/api/student/login', credentials);
        
        // 直接ログイン成功
        this.user = response.data.parent;
        this.isAuthenticated = true;
        this.guard = 'parent'; // 生徒も保護者ガードを使用
        this.loginType = 'student';
        this.needs2FA = false;
        this.needsPasswordChange = response.data.needs_password_change || false;
        
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    // 2FA検証
    async verify2FA(code) {
      try {
        let endpoint;
        let requestData = { code };
        
        if (this.loginType === 'student') {
          endpoint = '/api/student/verify-2fa';
          requestData.email = this.email;
        } else if (this.guard === 'admin') {
          endpoint = '/api/admin/verify-2fa';
          // 管理者はemailを送らない
        } else {
          endpoint = '/api/parent/verify-2fa';
          requestData.email = this.email;
        }
        
        const response = await axios.post(endpoint, requestData);

        this.user = response.data.parent || response.data.admin || response.data.user;
        this.isAuthenticated = true;
        this.needs2FA = false;
        
        if (response.data.needs_password_change !== undefined) {
          this.needsPasswordChange = response.data.needs_password_change;
        }

        return response.data;
      } catch (error) {
        throw error;
      }
    },

    // ログアウト
    async logout() {
      try {
        const endpoint = this.guard === 'admin' 
          ? '/api/admin/logout' 
          : '/api/parent/logout';
        
        await axios.post(endpoint);
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        this.user = null;
        this.guard = null;
        this.isAuthenticated = false;
        this.needs2FA = false;
        this.email = null;
        this.needsPasswordChange = false;
        this.loginType = null;
        this.needsEmailRegistration = false;
      }
    },

    // 現在のユーザー情報取得
    async fetchUser() {
      try {
        const endpoint = this.guard === 'admin' 
          ? '/api/admin/me' 
          : '/api/parent/me';
        
        const response = await axios.get(endpoint);
        this.user = response.data;
        this.isAuthenticated = true;
        
        if (response.data.needs_password_change !== undefined) {
          this.needsPasswordChange = response.data.needs_password_change;
        }

        return response.data;
      } catch (error) {
        this.user = null;
        this.isAuthenticated = false;
        throw error;
      }
    },

    // パスワード変更（保護者のみ）
    async changePassword(passwords) {
      try {
        const response = await axios.post('/api/parent/change-password', passwords);
        this.needsPasswordChange = false;
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    // ユーザー情報を直接セット（2FA認証後など）
    setUser(user, guard) {
      this.user = user;
      this.guard = guard;
      this.isAuthenticated = true;
      this.needs2FA = false;
      this.needsPasswordChange = user.needs_password_change || false;
    },

    // お試しモード（管理者として直接ログイン）
    async demoAdminLogin() {
      try {
        const response = await axios.post('/api/admin/login', {
          password: 'seiei2026'
        });
        this.user = response.data.admin;
        this.isAuthenticated = true;
        this.guard = 'admin';
        this.loginType = 'admin';
        this.needs2FA = false;
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    // お試しモード（保護者として直接ログイン）
    async demoParentLogin() {
      try {
        const response = await axios.get('/api/demo/parent-login');
        this.user = response.data.parent;
        this.isAuthenticated = true;
        this.guard = 'parent';
        this.loginType = 'parent';
        this.needs2FA = false;
        this.needsPasswordChange = false;
        return response.data;
      } catch (error) {
        throw error;
      }
    }
  }
});
