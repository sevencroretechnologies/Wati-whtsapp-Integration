import { useEffect, useState, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import { whatsappApi } from '../api/whatsapp';
import type { WhatsappMessage } from '../types';
import StatusBadge from '../components/StatusBadge';
import toast from 'react-hot-toast';

export default function ConversationDetail() {
  const { phone } = useParams<{ phone: string }>();
  const [messages, setMessages] = useState<WhatsappMessage[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchConversation = useCallback(async () => {
    if (!phone) return;
    setLoading(true);
    try {
      const response = await whatsappApi.getConversation(phone);
      setMessages(response.data.data.data.reverse());
    } catch {
      toast.error('Failed to load conversation');
    } finally {
      setLoading(false);
    }
  }, [phone]);

  useEffect(() => {
    fetchConversation();
  }, [fetchConversation]);

  const handleResend = async (id: number) => {
    try {
      await whatsappApi.resendMessage(id);
      toast.success('Message re-queued for delivery');
      fetchConversation();
    } catch {
      toast.error('Failed to resend message');
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-whatsapp-600" />
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <Link to="/conversations" className="text-sm text-whatsapp-600 hover:text-whatsapp-700 mb-2 inline-block">
          &larr; Back to Conversations
        </Link>
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 bg-whatsapp-100 rounded-full flex items-center justify-center">
            <svg className="w-5 h-5 text-whatsapp-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">{phone}</h1>
            <p className="text-gray-500 text-sm">{messages.length} messages</p>
          </div>
        </div>
      </div>

      <div className="card">
        <div className="space-y-4 max-h-[600px] overflow-y-auto p-4">
          {messages.length === 0 ? (
            <p className="text-center text-gray-500 py-8">No messages in this conversation</p>
          ) : (
            messages.map((msg) => (
              <div
                key={msg.id}
                className={`flex ${msg.direction === 'outgoing' ? 'justify-end' : 'justify-start'}`}
              >
                <div
                  className={`max-w-[75%] rounded-2xl px-4 py-3 ${
                    msg.direction === 'outgoing'
                      ? 'bg-whatsapp-500 text-white rounded-br-md'
                      : 'bg-gray-100 text-gray-900 rounded-bl-md'
                  }`}
                >
                  <p className="text-sm whitespace-pre-wrap">{msg.message || '(no content)'}</p>
                  <div className={`flex items-center gap-2 mt-1 ${msg.direction === 'outgoing' ? 'justify-end' : 'justify-start'}`}>
                    <span className={`text-xs ${msg.direction === 'outgoing' ? 'text-white/70' : 'text-gray-400'}`}>
                      {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                    </span>
                    <span className={`text-xs ${msg.direction === 'outgoing' ? 'text-white/70' : 'text-gray-400'}`}>
                      &middot; {msg.message_type}
                    </span>
                    {msg.direction === 'outgoing' && (
                      <StatusBadge status={msg.status} />
                    )}
                  </div>
                  {msg.status === 'failed' && (
                    <button
                      onClick={() => handleResend(msg.id)}
                      className={`text-xs mt-1 font-medium underline ${msg.direction === 'outgoing' ? 'text-white/90' : 'text-red-600'}`}
                    >
                      Resend
                    </button>
                  )}
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}
