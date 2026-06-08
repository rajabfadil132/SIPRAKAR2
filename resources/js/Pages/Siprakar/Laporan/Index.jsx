import EmptyState from "@/Components/EmptyState";
import SmartSelect from "@/Components/SmartSelect";
import Pagination from "@/Components/Pagination";
import StatusBadge from "@/Components/StatusBadge";
import AppLayout from "@/Layouts/AppLayout";
import { Link } from "@inertiajs/react";
import { Download, Eye, Printer, Search, XCircle } from "lucide-react";
import { useState } from "react";
import { useServerTableFilter } from "@/Utils/useServerTableFilter";

const rupiah = (value) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value ?? 0));

export default function Index({ items, summary, filters = {} }) {
    const rows = items?.data ?? [];
    const [search, setSearch] = useState(filters.search ?? "");
    const [status, setStatus] = useState(filters.status ?? "");
    useServerTableFilter("/reports", { search, status }, filters);

    return (
        <AppLayout title="Laporan & Statistik">
            <div className="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div className="stat-card"><p className="text-sm text-slate-500">Total data laporan</p><h3 className="text-3xl font-black">{summary.total}</h3></div>
                <div className="stat-card"><p className="text-sm text-slate-500">Rata-rata progress</p><h3 className="text-3xl font-black">{summary.avg_progress}%</h3></div>
                <div className="stat-card"><p className="text-sm text-slate-500">Pekerjaan selesai</p><h3 className="text-3xl font-black">{summary.done ?? 0}</h3></div>
                <div className="stat-card"><p className="text-sm text-slate-500">Memiliki RAB</p><h3 className="text-3xl font-black">{summary.with_rab ?? 0}</h3></div>
            </div>
            <div className="page-card">
                <div className="mb-5 flex flex-wrap justify-end gap-2">
                    <a className="btn-light" href={`/reports/export?${new URLSearchParams({ search, status }).toString()}`}><Download size={16} className="mr-2" />Export CSV</a>
                    <button className="btn-light" type="button" onClick={() => window.print()}><Printer size={16} className="mr-2" />Print</button>
                </div>
                <div className="mb-5 space-y-3">
                    <div className="grid gap-3 md:grid-cols-[1fr_auto]">
                        <div className="relative"><Search className="absolute left-3 top-3 h-4 w-4 text-slate-400" /><input value={search} onChange={(e) => setSearch(e.target.value)} className="input pl-9" placeholder="Cari pekerjaan, kode, cabang, kategori, petugas..." type="search" /></div>
                        <button className="btn-light justify-center" type="button" onClick={() => { setSearch(""); setStatus(""); }}><XCircle size={16} className="mr-2" />Reset</button>
                    </div>
                    <div className="grid gap-3 md:grid-cols-[220px]">
                        <SmartSelect value={status} onChange={(value) => setStatus(value)} options={[{ value: "", label: "Semua status" }, "Belum Diproses", "Diproses", "Selesai", "Dibatalkan"]} placeholder="Semua status" />
                    </div>
                </div>
                {rows.length === 0 ? <EmptyState /> : (
                    <div className="table-shell">
                        <table className="data-table min-w-[1320px] table-fixed">
                            <thead>
                                <tr>
                                    <th className="w-40">Kode</th>
                                    <th className="w-[320px]">Pekerjaan</th>
                                    <th className="w-44">Cabang</th>
                                    <th className="w-44">Kategori</th>
                                    <th className="w-44">Petugas</th>
                                    <th className="w-40">Progres</th>
                                    <th className="w-44">Total RAB</th>
                                    <th className="w-36 text-center">Status</th>
                                    <th className="w-24">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((p) => (
                                    <tr key={p.id}>
                                        <td className="table-nowrap font-semibold text-[#4cceac]">{p.kode_pekerjaan}</td>
                                        <td><div className="max-w-[300px] truncate font-semibold" title={p.nama_pekerjaan}>{p.nama_pekerjaan}</div></td>
                                        <td><div className="max-w-[160px] truncate" title={p.cabang?.nama_cabang}>{p.cabang?.nama_cabang ?? "-"}</div></td>
                                        <td><div className="max-w-[160px] truncate" title={p.kategori?.nama_kategori}>{p.kategori?.nama_kategori ?? "-"}</div></td>
                                        <td><div className="max-w-[160px] truncate" title={p.petugas?.name}>{p.petugas?.name ?? "-"}</div></td>
                                        <td><div className="progress-cell"><div className="progress-bar"><div className="h-2 rounded-full bg-[#4cceac]" style={{ width: `${p.progress}%` }} /></div><span className="progress-value">{p.progress}%</span></div></td>
                                        <td><div className="table-currency max-w-[160px] truncate font-semibold" title={rupiah(p.rab?.total_rab)}>{rupiah(p.rab?.total_rab)}</div></td>
                                        <td className="text-center"><StatusBadge value={p.status} /></td>
                                        <td><Link className="icon-btn" href={`/pekerjaan/${p.id}`} title="Lihat detail"><Eye size={15} /></Link></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                <Pagination meta={items} />
            </div>
        </AppLayout>
    );
}
