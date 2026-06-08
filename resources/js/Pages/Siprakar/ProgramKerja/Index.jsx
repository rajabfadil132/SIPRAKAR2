import EmptyState from "@/Components/EmptyState";
import SmartSelect from "@/Components/SmartSelect";
import Pagination from "@/Components/Pagination";
import PriorityBadge from "@/Components/PriorityBadge";
import StatusBadge from "@/Components/StatusBadge";
import AppLayout from "@/Layouts/AppLayout";
import { Link, router } from "@inertiajs/react";
import { ArrowDownUp, Edit3, Eye, Plus, Search, Trash2, XCircle } from "lucide-react";
import { useState } from "react";
import { useServerTableFilter } from "@/Utils/useServerTableFilter";

const rupiah = (value) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value ?? 0));

const rabLabel = (program) => {
    if (!program.needs_rab) {
        return "Tidak perlu RAB";
    }

    if (program.rab) {
        return `${program.rab.status_rab} · ${rupiah(program.rab.total_rab)}`;
    }

    return "Perlu RAB";
};

export default function Index({ items, filters = {}, permissions = {}, kategoris = [], cabangs = [], statuses = [] }) {
    const rows = items?.data ?? [];
    const [search, setSearch] = useState(filters.search ?? "");
    const [status, setStatus] = useState(filters.status ?? "");
    const [kategoriId, setKategoriId] = useState(filters.kategori_id ?? "");
    const [cabangId, setCabangId] = useState(filters.cabang_id ?? "");
    const [sortDir, setSortDir] = useState(filters.sort_dir ?? "desc");
    useServerTableFilter("/program-kerja", { search, status, kategori_id: kategoriId, cabang_id: cabangId, sort_dir: sortDir }, filters);

    return (
        <AppLayout title="Program Kerja">
            <div className="mb-5 flex flex-col justify-between gap-3 md:flex-row md:items-center">
                <p className="text-sm text-slate-500">Daftar program kerja yang masih tersedia. Jika sudah diambil menjadi pekerjaan, program otomatis pindah ke Data Pekerjaan.</p>
                {permissions["program_kerja.create"] && (
                    <Link href="/program-kerja/create" className="btn-primary"><Plus size={16} className="mr-2" />Tambah Program</Link>
                )}
            </div>

            <div className="page-card">
                <div className="mb-5 space-y-3">
                    <div className="grid gap-3 md:grid-cols-[1fr_auto]">
                        <div className="relative">
                            <Search className="absolute left-3 top-3 h-4 w-4 text-slate-400" />
                            <input value={search} onChange={(e) => setSearch(e.target.value)} className="input pl-9" placeholder="Cari kode, nama program, cabang, kategori..." type="search" />
                        </div>
                        <button className="btn-light justify-center" type="button" onClick={() => { setSearch(""); setStatus(""); setKategoriId(""); setCabangId(""); setSortDir("desc"); }}><XCircle size={16} className="mr-2" />Reset</button>
                    </div>
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-[180px_200px_200px_auto]">
                        <SmartSelect value={status} onChange={(value) => setStatus(value)} options={[{ value: "", label: "Semua status" }, ...(statuses.length ? statuses : ["RAB Diajukan", "RAB Direvisi", "RAB Disetujui", "Siap Dijadikan Pekerjaan"])]} placeholder="Semua status" />
                        <SmartSelect value={kategoriId} onChange={(value) => setKategoriId(value)} options={[{ id: "", nama_kategori: "Semua kategori" }, ...kategoris]} placeholder="Semua kategori" getOptionValue={(k) => k.id} getOptionLabel={(k) => k.nama_kategori} />
                        <SmartSelect value={cabangId} onChange={(value) => setCabangId(value)} options={[{ id: "", nama_cabang: "Semua cabang" }, ...cabangs]} placeholder="Semua cabang" getOptionValue={(c) => c.id} getOptionLabel={(c) => c.nama_cabang} getOptionDescription={(c) => c.kode ? `Kode ${c.kode}` : ""} />
                        <button className="btn-light justify-center" type="button" onClick={() => setSortDir(sortDir === "desc" ? "asc" : "desc")} title="Urut terbaru atau terlama"><ArrowDownUp size={16} className="mr-2" />{sortDir === "desc" ? "Terbaru" : "Terlama"}</button>
                    </div>
                </div>

                {rows.length === 0 ? <EmptyState /> : (
                    <div className="table-shell">
                        <table className="data-table min-w-[1420px] table-fixed">
                            <thead>
                                <tr>
                                    <th className="w-40">Kode</th>
                                    <th className="w-[320px]">Program</th>
                                    <th className="w-32">Sumber</th>
                                    <th className="w-48">Cabang</th>
                                    <th className="w-40">Kategori</th>
                                    <th className="w-36">Prioritas</th>
                                    <th className="w-44 text-right">RAB</th>
                                    <th className="w-36 text-center">Status</th>
                                    <th className="w-40 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((x) => (
                                    <tr key={x.id}>
                                        <td className="table-nowrap font-semibold text-[#4cceac]">{x.kode_program}</td>
                                        <td><div className="max-w-[300px] truncate font-bold" title={x.nama_program}>{x.nama_program}</div></td>
                                        <td><span className="badge bg-slate-500/15 text-slate-300">{x.source_type || "PROKER"}</span></td>
                                        <td><div className="max-w-[170px] truncate" title={x.cabang?.nama_cabang}>{x.cabang?.nama_cabang ?? "-"}</div></td>
                                        <td><div className="max-w-[140px] truncate" title={x.kategori?.nama_kategori}>{x.kategori?.nama_kategori ?? "-"}</div></td>
                                        <td className="table-nowrap"><PriorityBadge value={x.prioritas} /></td>
                                        <td className="table-nowrap font-semibold">{rabLabel(x)}</td>
                                        <td className="text-center"><StatusBadge value={x.status} /></td>
                                        <td className="text-right">
                                            <div className="table-actions">
                                                {permissions["program_kerja.show"] && <Link className="icon-btn" href={`/program-kerja/${x.id}`} title="Lihat detail"><Eye size={15} /></Link>}
                                                {permissions["program_kerja.edit"] && <Link className="icon-btn" href={`/program-kerja/${x.id}/edit`} title="Edit"><Edit3 size={15} /></Link>}
                                                {permissions["program_kerja.delete"] && <button className="icon-btn-danger" type="button" onClick={() => confirm("Hapus program kerja ini?") && router.delete(`/program-kerja/${x.id}`, { preserveScroll: true })} title="Hapus"><Trash2 size={15} /></button>}
                                            </div>
                                        </td>
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
