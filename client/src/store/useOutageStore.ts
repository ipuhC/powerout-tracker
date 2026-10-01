import { create } from 'zustand';
import { apiClient } from '@/services/apiClient';

import { Outage, Bounds } from '@/types';

interface OutageState {
  outages: Outage[]; 
  isLoading: boolean;
  error: unknown | null;
  fetchOutages: (bounds?: Bounds) => Promise<void>;
}

export const useOutageStore = create<OutageState>((set) => ({
  outages: [],
  isLoading: false,
  error: null,
  
  fetchOutages: async (bounds) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get('/public/outages', { params: bounds });
      set({ outages: response.data.data, isLoading: false });
    } catch (err) {
      set({ error: err, isLoading: false });
    }
  },
}));
