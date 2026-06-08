export default function EmptyState({ title = 'Belum Ada Data', description = 'Belum ada data yang cocok dengan pencarian atau filter saat ini.' }) {
  return (
    <div className="rounded-2xl border border-dashed border-[#29314b] bg-[#141b2d]/70 p-8 text-center">
      <h3 className="text-lg font-bold text-[#e0e0e0]">{title}</h3>
      <p className="mt-1 text-sm text-[#a3a3a3]">{description}</p>
    </div>
  );
}
