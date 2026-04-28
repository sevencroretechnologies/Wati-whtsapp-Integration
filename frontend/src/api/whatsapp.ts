import apiClient from './client';
import type {
  ApiResponse,
  MessageFilters,
  PaginatedResponse,
  SendButtonsPayload,
  SendMediaPayload,
  SendMessagePayload,
  SendTemplatePayload,
  WhatsappMessage,
} from '../types';

export const whatsappApi = {
  sendMessage(payload: SendMessagePayload) {
    return apiClient.post<ApiResponse<{ phone: string }>>('/whatsapp/send-message', payload);
  },

  sendTemplate(payload: SendTemplatePayload) {
    return apiClient.post<ApiResponse<{ phone: string; template_name: string }>>(
      '/whatsapp/send-template',
      payload
    );
  },

  sendMedia(payload: SendMediaPayload) {
    return apiClient.post<ApiResponse<{ phone: string }>>('/whatsapp/send-media', payload);
  },

  sendButtons(payload: SendButtonsPayload) {
    return apiClient.post<ApiResponse<{ phone: string }>>('/whatsapp/send-buttons', payload);
  },

  getMessages(filters: MessageFilters = {}) {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        params.append(key, String(value));
      }
    });
    return apiClient.get<ApiResponse<PaginatedResponse<WhatsappMessage>>>(
      `/whatsapp/messages?${params.toString()}`
    );
  },

  getConversation(phone: string, page = 1, perPage = 50) {
    return apiClient.get<ApiResponse<PaginatedResponse<WhatsappMessage>>>(
      `/whatsapp/conversation/${encodeURIComponent(phone)}?page=${page}&per_page=${perPage}`
    );
  },

  getMessageStatus(id: string) {
    return apiClient.get<ApiResponse<Record<string, unknown>>>(`/whatsapp/message-status/${id}`);
  },

  resendMessage(id: number) {
    return apiClient.post<ApiResponse<null>>(`/whatsapp/resend/${id}`);
  },
};
