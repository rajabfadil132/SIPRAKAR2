import { formatDate } from "@/Utils/date";

const userName = (user) => user?.name ?? '-';

export default function AuditInfo({ item, compact = false }) {
  if (compact) {
    return (
      <div className="space-y-1 text-xs leading-relaxed text-[#a3a3a3]">
        <p>Dibuat: <b className="text-[#e0e0e0]">{userName(item?.created_by_user ?? item?.creator)}</b></p>
        <p>Diubah: <b className="text-[#e0e0e0]">{userName(item?.updated_by_user ?? item?.updater)}</b></p>
      </div>
    );
  }

  return (
    <div className="grid gap-3 rounded-2xl border border-[#29314b] bg-[#141b2d] p-4 text-sm md:grid-cols-3">
      <div>
        <p className="text-xs font-semibold uppercase tracking-wide text-[#a3a3a3]">Dibuat oleh</p>
        <b className="text-[#e0e0e0]">{userName(item?.created_by_user ?? item?.creator)}</b>
        <p className="text-xs text-[#a3a3a3]">{formatDate(item?.created_at)}</p>
      </div>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wide text-[#a3a3a3]">Diubah oleh</p>
        <b className="text-[#e0e0e0]">{userName(item?.updated_by_user ?? item?.updater)}</b>
        <p className="text-xs text-[#a3a3a3]">{formatDate(item?.updated_at)}</p>
      </div>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wide text-[#a3a3a3]">Dihapus oleh</p>
        <b className="text-[#e0e0e0]">{userName(item?.deleted_by_user ?? item?.deleter)}</b>
        <p className="text-xs text-[#a3a3a3]">{formatDate(item?.deleted_at)}</p>
      </div>
    </div>
  );
}
