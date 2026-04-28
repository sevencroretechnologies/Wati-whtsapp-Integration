import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { whatsappApi } from '../api/whatsapp';
import type { WhatsappMessage } from '../types';
import StatusBadge from '../components/StatusBadge';

interface Stats {
  totalMessages: number;
  incoming: number;
  outgoing: number;
  delivered: number;
  failed: number;
  pending: number;
}

export default function Dashboard() {
  const [stats, setStats] = useState<Stats>({
    totalMessages: 0,
    incoming: 0,
    outgoing: 0,
    delivered: 0,
    failed: 0,
    pending: 0,
  });
  const [recentMessages, setRecentMessages] = useState<WhatsappMessage[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchData() {
      try {
        const [allRes, incomingRes, outgoingRes, deliveredRes, failedRes, pendingRes] =
          await Promise.all([
            whatsappApi.getMessages({ per_page: 5 }),
            whatsappApi.getMessages({ direction: 'incoming', per_page: 1 }),
            whatsappApi.getMessages({ direction: 'outgoing', per_page: 1 }),
            whatsappApi.getMessages({ status: 'delivered', per_page: 1 }),
            whatsappApi.getMessages({ status: 'failed', per_page: 1 }),
            whatsappApi.getMessages({ status: 'pending', per_page: 1 }),
          ]);

        setStats({
          totalMessages: allRes.data.data.total,
          incoming: incomingRes.data.data.total,
          outgoing: outgoingRes.data.data.total,
          delivered: deliveredRes.data.data.total,
          failed: failedRes.data.data.total,
          pending: pendingRes.data.data.total,
        });
        setRecentMessages(allRes.data.data.data);
      } catch {
        // handled by interceptor
      } finally {
        setLoading(false);
      }
    }
    fetchData();
  }, []);

  const statCards = [
    { label: 'Total Messages', value: stats.totalMessages, color: 'bg-blue-500' },
    { label: 'Incoming', value: stats.incoming, color: 'bg-purple-500' },
    { label: 'Outgoing', value: stats.outgoing, color: 'bg-whatsapp-500' },
    { label: 'Delivered', value: stats.delivered, color: 'bg-green-500' },
    { label: 'Failed', value: stats.failed, color: 'bg-red-500' },
    { label: 'Pending', value: stats.pending, color: 'bg-yellow-500' },
  ];

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-whatsapp-600" />
      </div>
    );
  }

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-500 mt-1">Overview of your WhatsApp messaging activity</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        {statCards.map((card) => (
          <div key={card.label} className="card flex items-center gap-4">
            <div className={`w-12 h-12 ${card.color} rounded-xl flex items-center justify-center text-white text-lg font-bold shadow-lg`}>
              {card.value}
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">{card.value}</p>
              <p className="text-sm text-gray-500">{card.label}</p>
            </div>
          </div>
        ))}
      </div>

      <div className="card">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold text-gray-900">Recent Messages</h2>
          <Link to="/messages" className="text-sm text-whatsapp-600 hover:text-whatsapp-700 font-medium">
            View all &rarr;
          </Link>
        </div>

        {recentMessages.length === 0 ? (
          <p className="text-gray-500 text-center py-8">No messages yet. Send your first message!</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100">
                  <th className="text-left py-3 px-2 font-medium text-gray-500">Phone</th>
                  <th className="text-left py-3 px-2 font-medium text-gray-500">Direction</th>
                  <th className="text-left py-3 px-2 font-medium text-gray-500">Type</th>
                  <th className="text-left py-3 px-2 font-medium text-gray-500">Message</th>
                  <th className="text-left py-3 px-2 font-medium text-gray-500">Status</th>
                  <th className="text-left py-3 px-2 font-medium text-gray-500">Time</th>
                </tr>
              </thead>
              <tbody>
                {recentMessages.map((msg) => (
                  <tr key={msg.id} className="border-b border-gray-50 hover:bg-gray-50">
                    <td className="py-3 px-2">
                      <Link to={`/conversations/${encodeURIComponent(msg.phone)}`} className="text-whatsapp-600 hover:text-whatsapp-700 font-medium">
                        {msg.phone}
                      </Link>
                    </td>
                    <td className="py-3 px-2">
                      <span className={`inline-flex items-center gap-1 text-xs font-medium ${msg.direction === 'incoming' ? 'text-purple-600' : 'text-blue-600'}`}>
                        {msg.direction === 'incoming' ? '↓ In' : '↑ Out'}
                      </span>
                    </td>
                    <td className="py-3 px-2 text-gray-600">{msg.message_type}</td>
                    <td className="py-3 px-2 text-gray-600 max-w-xs truncate">{msg.message || '—'}</td>
                    <td className="py-3 px-2"><StatusBadge status={msg.status} /></td>
                    <td className="py-3 px-2 text-gray-400 text-xs whitespace-nowrap">
                      {new Date(msg.created_at).toLocaleString()}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
