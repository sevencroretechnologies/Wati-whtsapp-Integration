import axios from 'axios';
import apiClient from './client';
import type { LoginCredentials, LoginResponse } from '../types';

const API_BASE_URL = process.env.REACT_APP_API_URL || '';

export const authApi = {
  async login(credentials: LoginCredentials) {
    await axios.get(`${API_BASE_URL}/sanctum/csrf-cookie`, { withCredentials: true });
    return apiClient.post<LoginResponse>('/auth/login', credentials);
  },

  logout() {
    return apiClient.post('/auth/logout');
  },
};
