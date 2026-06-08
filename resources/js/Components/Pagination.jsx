import { Link } from '@inertiajs/react';

export default function Pagination({ meta }) {
  const links = meta?.links ?? [];
  if (!links.length) return null;

  return (
    <div className="mt-5 flex flex-col gap-3 border-t border-[#29314b] pt-4 text-sm text-[#a3a3a3] md:flex-row md:items-center md:justify-between">
      <p>
        Menampilkan <b className="text-[#e0e0e0]">{meta.from ?? 0}</b> - <b className="text-[#e0e0e0]">{meta.to ?? 0}</b> dari{' '}
        <b className="text-[#e0e0e0]">{meta.total ?? 0}</b> data
      </p>
      <div className="flex flex-wrap gap-2">
        {links.map((link, index) => {
          const label = link.label.replace('&laquo; Previous', '‹').replace('Next &raquo;', '›');
          if (!link.url) {
            return <span key={index} className="grid min-h-9 min-w-9 place-items-center rounded-lg border border-[#29314b] px-3 text-[#646a7f]" dangerouslySetInnerHTML={{ __html: label }} />;
          }
          return (
            <Link
              key={index}
              href={link.url}
              preserveScroll
              className={`grid min-h-9 min-w-9 place-items-center rounded-lg border px-3 font-semibold transition ${
                link.active ? 'border-[#6870fa] bg-[#6870fa] text-white' : 'border-[#29314b] text-[#e0e0e0] hover:border-[#6870fa] hover:text-[#868dfb]'
              }`}
              dangerouslySetInnerHTML={{ __html: label }}
            />
          );
        })}
      </div>
    </div>
  );
}
