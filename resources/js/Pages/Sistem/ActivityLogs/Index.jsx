import StatusBadge from "@/Components/StatusBadge";
import AppLayout from "@/Layouts/AppLayout";
import { formatDate } from "@/Utils/date";
import { Link } from "@inertiajs/react";
import { Activity, ExternalLink } from "lucide-react";

export default function Index({ items = [] }) {
    return (
        <AppLayout title="Riwayat Aktivitas">
            <p className="mb-6 text-sm text-slate-500">Audit ringkas dari aktivitas penting pada modul SIPRAKAR dan RAB. Data ini berasal dari kolom pembuat, pengubah, penghapus, serta status soft delete.</p>
            <div className="page-card">
                <div className="space-y-3">
                    {items.map((item, index) => (
                        <div key={`${item.module}-${item.action}-${item.code}-${index}`} className="rounded-2xl border border-[#29314b] bg-[#141b2d] p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="flex min-w-0 items-start gap-3">
                                    <div className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#4cceac]/15 text-[#4cceac]"><Activity size={18} /></div>
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <StatusBadge value={item.action} />
                                            <b className="text-[#4cceac]">{item.module}</b>
                                            <span className="text-xs text-slate-500">{item.code}</span>
                                        </div>
                                        <p className="mt-1 font-bold">{item.title ?? "-"}</p>
                                        <p className="mt-1 text-sm text-slate-500">{item.description}</p>
                                    </div>
                                </div>
                                <div className="text-right text-xs text-slate-500">
                                    <p>{formatDate(item.time)}</p>
                                    <p>oleh {item.actor}</p>
                                    {item.href && <Link href={item.href} className="mt-2 inline-flex items-center gap-1 font-bold text-[#4cceac]">Detail <ExternalLink size={13} /></Link>}
                                </div>
                            </div>
                        </div>
                    ))}
                    {items.length === 0 && <p className="text-sm text-slate-500">Belum ada aktivitas yang dapat ditampilkan.</p>}
                </div>
            </div>
        </AppLayout>
    );
}
