import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { whatsappApi } from '../api/whatsapp';
import type { WhatsappMessage, MessageFilters } from '../types';
import StatusBadge from '../components/StatusBadge';
import Pagination from '../components/Pagination';
import toast from 'react-hot-toast';

export default function Messages() {
  const [messages, setMessages] = useState<WhatsappMessage[]>([]);
  const [loading, setLoading] = useState(true);
  const [pagination, setPagination] = useState({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    from: null as number | null,
    to: null as number | null,
  });
  const [filters, setFilters] = useState<MessageFilters>({
    status: '',
    direction: '',
    phone: '',
    date_from: '',
    date_to: '',
    per_page: 15,
    page: 1,
  });

  const fetchMessages = useCallback(async () => {
    setLoading(true);
    try {
      const response = await whatsappApi.getMessages(filters);
      const data = response.data.data;
      setMessages(data.data);
      setPagination({
        currentPage: data.current_page,
        lastPage: data.last_page,
        total: data.total,
        from: data.from,
        to: data.to,
      });
    } catch {
      toast.error('Failed to load messages');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    fetchMessages();
  }, [fetchMessages]);

  const handleFilterChange = (key: keyof MessageFilters, value: string) => {
    setFilters((prev) => ({ ...prev, [key]: value, page: 1 }));
  };

  const handlePageChange = (page: number) => {
    setFilters((prev) => ({ ...prev, page }));
  };

  const handleResend = async (id: number) => {
    try {
      await whatsappApi.resendMessage(id);
      toast.success('Message re-queued for delivery');
      fetchMessages();
    } catch {
      toast.error('Failed to resend message');
    }
  };

  const clearFilters = () => {
    setFilters({
      status: '',
      direction: '',
      phone: '',
      date_from: '',
      date_to: '',
      per_page: 15,
      page: 1,
    });
  };

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Messages</h1>
        <p className="text-gray-500 mt-1">View and manage all WhatsApp messages</p>
      </div>

      {/* Filters */}
      <div className="card mb-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-sm font-semibold text-gray-700">Filters</h3>
          <button onClick={clearFilters} className="text-xs text-gray-500 hover:text-gray-700">
            Clear all
          </button>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
          <select
            value={filters.status || ''}
            onChange={(e) => handleFilterChange('status', e.target.value)}
            className="input-field text-sm"
          >
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="sent">Sent</option>
            <option value="delivered">Delivered</option>
            <option value="read">Read</option>
            <option value="failed">Failed</option>
            <option value="received">Received</option>
          </select>

          <select
            value={filters.direction || ''}
            onChange={(e) => handleFilterChange('direction', e.target.value)}
            className="input-field text-sm"
          >
            <option value="">All Directions</option>
            <option value="incoming">Incoming</option>
            <option value="outgoing">Outgoing</option>
          </select>

          <input
            type="text"
            placeholder="Phone number"
            value={filters.phone || ''}
            onChange={(e) => handleFilterChange('phone', e.target.value)}
            className="input-field text-sm"
          />

          <input
            type="date"
            value={filters.date_from || ''}
            onChange={(e) => handleFilterChange('date_from', e.target.value)}
            className="input-field text-sm"
          />

          <input
            type="date"
            value={filters.date_to || ''}
            onChange={(e) => handleFilterChange('date_to', e.target.value)}
            className="input-field text-sm"
          />
        </div>
      </div>

      {/* Messages Table */}
      <div className="card">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-whatsapp-600" />
          </div>
        ) : messages.length === 0 ? (
          <div className="text-center py-12">
            <svg className="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <p className="text-gray-500">No messages found</p>
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-gray-100">
                    <th className="text-left py-3 px-2 font-medium text-gray-500">ID</th>
                    <th className="text-left py-3 px-2 font-medium text-gray-500">Phone</th>
                    <th className="text-left py-3 px-2 font-medium text-gray-500">Direction</th>
                    <th className="text-left py-3 px-2 font-medium text-gray-500">Type</th>
                    <th className="text-left py-3 px-2 font-medium text-gray-500">Message</th>
                    <th className="text-left py-3 px-2 font-medium text-gray-500">Status</th>
                    <th className="text-left py-3 px-2 font-medium text-gray-500">Time</th>
                    <th className="text-left py-3 px-2 font-medium text-gray-500">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {messages.map((msg) => (
                    <tr key={msg.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-2 text-gray-400">#{msg.id}</td>
                      <td className="py-3 px-2">
                        <Link
                          to={`/conversations/${encodeURIComponent(msg.phone)}`}
                          className="text-whatsapp-600 hover:text-whatsapp-700 font-medium"
                        >
                          {msg.phone}
                        </Link>
                      </td>
                      <td className="py-3 px-2">
                        <span className={`inline-flex items-center gap-1 text-xs font-medium ${msg.direction === 'incoming' ? 'text-purple-600' : 'text-blue-600'}`}>
                          {msg.direction === 'incoming' ? '↓ Incoming' : '↑ Outgoing'}
                        </span>
                      </td>
                      <td className="py-3 px-2 text-gray-600 capitalize">{msg.message_type}</td>
                      <td className="py-3 px-2 text-gray-600 max-w-xs truncate">{msg.message || '—'}</td>
                      <td className="py-3 px-2"><StatusBadge status={msg.status} /></td>
                      <td className="py-3 px-2 text-gray-400 text-xs whitespace-nowrap">
                        {new Date(msg.created_at).toLocaleString()}
                      </td>
                      <td className="py-3 px-2">
                        {msg.status === 'failed' && (
                          <button
                            onClick={() => handleResend(msg.id)}
                            className="text-xs text-whatsapp-600 hover:text-whatsapp-700 font-medium"
                          >
                            Resend
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <Pagination
              currentPage={pagination.currentPage}
              lastPage={pagination.lastPage}
              total={pagination.total}
              from={pagination.from}
              to={pagination.to}
              onPageChange={handlePageChange}
            />
          </>
        )}
      </div>
    </div>
  );
}
