import apiClient from './client';
import type { LoginCredentials, LoginResponse } from '../types';

export const authApi = {
  login(credentials: LoginCredentials) {
    return apiClient.post<LoginResponse>('/auth/login', credentials);
  },

  logout() {
    return apiClient.post('/auth/logout');
  },
};
