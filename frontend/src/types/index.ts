export interface WhatsappMessage {
  id: number;
  phone: string;
  direction: 'incoming' | 'outgoing';
  message_type: string;
  message: string | null;
  status: string;
  external_message_id: string | null;
  payload: Record<string, unknown> | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

export interface PaginatedResponse<T> {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: PaginationLink[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface ApiResponse<T> {
  status: boolean;
  message: string;
  data: T;
}

export interface SendMessagePayload {
  phone: string;
  message: string;
}

export interface SendTemplatePayload {
  phone: string;
  template_name: string;
  parameters?: { name: string; value: string }[];
}

export interface SendMediaPayload {
  phone: string;
  file_url: string;
}

export interface SendButtonsPayload {
  phone: string;
  message: string;
  buttons: { text: string }[];
}

export interface User {
  id: number;
  name: string;
  email: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface LoginResponse {
  status: boolean;
  message: string;
  data: {
    user: User;
    token: string;
  };
}

export interface MessageFilters {
  status?: string;
  direction?: string;
  phone?: string;
  date_from?: string;
  date_to?: string;
  per_page?: number;
  page?: number;
}
