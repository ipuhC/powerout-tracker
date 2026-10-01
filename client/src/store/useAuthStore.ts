import { create } from 'zustand';
import { apiClient } from '@/services/apiClient';

interface User {
  id: number;
  name: string;
  email: string;
}

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
  
  setAuth: (user: User, token: string) => void;
  clearAuth: () => void;
  checkAuth: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  token: localStorage.getItem('powerout_token'),
  isAuthenticated: !!localStorage.getItem('powerout_token'),
  isLoading: true, // Initially true while we verify the token if it exists
  error: null,

  setAuth: (user, token) => {
    localStorage.setItem('powerout_token', token);
    set({ user, token, isAuthenticated: true, error: null });
  },

  clearAuth: () => {
    localStorage.removeItem('powerout_token');
    set({ user: null, token: null, isAuthenticated: false, error: null, isLoading: false });
  },

  checkAuth: async () => {
    const token = localStorage.getItem('powerout_token');
    if (!token) {
      set({ isLoading: false, isAuthenticated: false });
      return;
    }

    try {
      set({ isLoading: true, error: null });
      const response = await apiClient.get('/auth/user');
      set({ user: response.data.data || response.data, isAuthenticated: true, isLoading: false });
    } catch (error) {
      console.error("Auth check failed:", error);
      localStorage.removeItem('powerout_token');
      set({ user: null, token: null, isAuthenticated: false, error: 'Session expired', isLoading: false });
    }
  }
}));
