interface StatusBadgeProps {
  status: string;
}

const statusConfig: Record<string, { bg: string; text: string; dot: string }> = {
  pending: { bg: 'bg-yellow-50', text: 'text-yellow-700', dot: 'bg-yellow-500' },
  sent: { bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500' },
  delivered: { bg: 'bg-green-50', text: 'text-green-700', dot: 'bg-green-500' },
  read: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
  failed: { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500' },
  received: { bg: 'bg-purple-50', text: 'text-purple-700', dot: 'bg-purple-500' },
  retrying: { bg: 'bg-orange-50', text: 'text-orange-700', dot: 'bg-orange-500' },
};

export default function StatusBadge({ status }: StatusBadgeProps) {
  const config = statusConfig[status] || { bg: 'bg-gray-50', text: 'text-gray-700', dot: 'bg-gray-500' };

  return (
    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${config.bg} ${config.text}`}>
      <span className={`w-1.5 h-1.5 rounded-full ${config.dot}`} />
      {status.charAt(0).toUpperCase() + status.slice(1)}
    </span>
  );
}
