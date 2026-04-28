interface PaginationProps {
  currentPage: number;
  lastPage: number;
  total: number;
  from: number | null;
  to: number | null;
  onPageChange: (page: number) => void;
}

export default function Pagination({ currentPage, lastPage, total, from, to, onPageChange }: PaginationProps) {
  if (lastPage <= 1) return null;

  const pages: (number | string)[] = [];
  const maxVisible = 5;

  if (lastPage <= maxVisible + 2) {
    for (let i = 1; i <= lastPage; i++) pages.push(i);
  } else {
    pages.push(1);
    if (currentPage > 3) pages.push('...');
    const start = Math.max(2, currentPage - 1);
    const end = Math.min(lastPage - 1, currentPage + 1);
    for (let i = start; i <= end; i++) pages.push(i);
    if (currentPage < lastPage - 2) pages.push('...');
    pages.push(lastPage);
  }

  return (
    <div className="flex items-center justify-between mt-6">
      <p className="text-sm text-gray-600">
        Showing {from ?? 0} to {to ?? 0} of {total} results
      </p>
      <div className="flex items-center gap-1">
        <button
          onClick={() => onPageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        {pages.map((page, i) =>
          typeof page === 'string' ? (
            <span key={`ellipsis-${i}`} className="px-2 py-1.5 text-sm text-gray-400">
              ...
            </span>
          ) : (
            <button
              key={page}
              onClick={() => onPageChange(page)}
              className={`px-3 py-1.5 text-sm rounded-lg border ${
                page === currentPage
                  ? 'bg-whatsapp-600 text-white border-whatsapp-600'
                  : 'border-gray-300 hover:bg-gray-50'
              }`}
            >
              {page}
            </button>
          )
        )}
        <button
          onClick={() => onPageChange(currentPage + 1)}
          disabled={currentPage === lastPage}
          className="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
    </div>
  );
}
