import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { whatsappApi } from '../api/whatsapp';
import type { WhatsappMessage } from '../types';
import toast from 'react-hot-toast';

interface ConversationSummary {
  phone: string;
  lastMessage: string | null;
  lastMessageTime: string;
  messageCount: number;
  direction: 'incoming' | 'outgoing';
}

export default function Conversations() {
  const [conversations, setConversations] = useState<ConversationSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchPhone, setSearchPhone] = useState('');

  useEffect(() => {
    async function fetchConversations() {
      try {
        const response = await whatsappApi.getMessages({ per_page: 100 });
        const messages: WhatsappMessage[] = response.data.data.data;

        const phoneMap = new Map<string, ConversationSummary>();
        for (const msg of messages) {
          const existing = phoneMap.get(msg.phone);
          if (!existing) {
            phoneMap.set(msg.phone, {
              phone: msg.phone,
              lastMessage: msg.message,
              lastMessageTime: msg.created_at,
              messageCount: 1,
              direction: msg.direction,
            });
          } else {
            existing.messageCount++;
          }
        }

        setConversations(Array.from(phoneMap.values()));
      } catch {
        toast.error('Failed to load conversations');
      } finally {
        setLoading(false);
      }
    }
    fetchConversations();
  }, []);

  const filteredConversations = conversations.filter((c) =>
    c.phone.includes(searchPhone)
  );

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
        <h1 className="text-2xl font-bold text-gray-900">Conversations</h1>
        <p className="text-gray-500 mt-1">View message threads by phone number</p>
      </div>

      <div className="mb-4">
        <input
          type="text"
          placeholder="Search by phone number..."
          value={searchPhone}
          onChange={(e) => setSearchPhone(e.target.value)}
          className="input-field max-w-sm"
        />
      </div>

      {filteredConversations.length === 0 ? (
        <div className="card text-center py-12">
          <svg className="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
          </svg>
          <p className="text-gray-500">No conversations found</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {filteredConversations.map((conv) => (
            <Link
              key={conv.phone}
              to={`/conversations/${encodeURIComponent(conv.phone)}`}
              className="card hover:shadow-md transition-shadow group"
            >
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 bg-whatsapp-100 rounded-full flex items-center justify-center flex-shrink-0">
                  <svg className="w-5 h-5 text-whatsapp-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center justify-between">
                    <p className="font-semibold text-gray-900 group-hover:text-whatsapp-600 transition-colors">
                      {conv.phone}
                    </p>
                    <span className="text-xs text-gray-400">
                      {new Date(conv.lastMessageTime).toLocaleDateString()}
                    </span>
                  </div>
                  <p className="text-sm text-gray-500 truncate mt-1">{conv.lastMessage || 'No message'}</p>
                  <div className="flex items-center gap-2 mt-2">
                    <span className="text-xs text-gray-400">{conv.messageCount} messages</span>
                    <span className={`text-xs ${conv.direction === 'incoming' ? 'text-purple-500' : 'text-blue-500'}`}>
                      Last: {conv.direction}
                    </span>
                  </div>
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
