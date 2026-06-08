import EmptyState from "@/Components/EmptyState";
import SmartSelect from "@/Components/SmartSelect";
import Pagination from "@/Components/Pagination";
import StatusBadge from "@/Components/StatusBadge";
import AppLayout from "@/Layouts/AppLayout";
import { formatDate } from "@/Utils/date";
import { Link, router } from "@inertiajs/react";
import { ArrowDownUp, Edit3, Eye, Plus, Search, Trash2, XCircle } from "lucide-react";
import { useState } from "react";
import { useServerTableFilter } from "@/Utils/useServerTableFilter";

const rupiah = (value) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value ?? 0));

export default function Index({ items, filters = {}, permissions = {}, cabangs = [], kategoris = [] }) {
    const rows = items?.data ?? [];
    const [search, setSearch] = useState(filters.search ?? "");
    const [statusRab, setStatusRab] = useState(filters.status_rab ?? "");
    const [cabangId, setCabangId] = useState(filters.cabang_id ?? "");
    const [kategoriId, setKategoriId] = useState(filters.kategori_id ?? "");
    const [sortDir, setSortDir] = useState(filters.sort_dir ?? "desc");
    useServerTableFilter("/rab", { search, status_rab: statusRab, cabang_id: cabangId, kategori_id: kategoriId, sort_dir: sortDir }, filters);

    return (
        <AppLayout title="RAB Program Kerja">
            <div className="mb-5 flex flex-col justify-between gap-3 md:flex-row md:items-center">
                <p className="text-sm text-slate-500">Kelola RAB Program Kerja sebelum program dipindahkan menjadi Data Pekerjaan.</p>
                {permissions["rab.create"] && <Link href="/rab/create" className="btn-primary"><Plus size={16} className="mr-2" />Tambah RAB</Link>}
            </div>
            <div className="page-card">
                <div className="mb-5 space-y-3">
                    <div className="grid gap-3 md:grid-cols-[1fr_auto]">
                        <div className="relative"><Search className="absolute left-3 top-3 h-4 w-4 text-slate-400" /><input value={search} onChange={(e) => setSearch(e.target.value)} className="input pl-9" placeholder="Cari nomor RAB, program, cabang, status..." type="search" /></div>
                        <button className="btn-light justify-center" type="button" onClick={() => { setSearch(""); setStatusRab(""); setCabangId(""); setKategoriId(""); setSortDir("desc"); }}><XCircle size={16} className="mr-2" />Reset</button>
                    </div>
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-[180px_200px_200px_auto]">
                        <SmartSelect value={statusRab} onChange={(value) => setStatusRab(value)} options={[{ value: "", label: "Semua status" }, "Diajukan", "Direvisi", "Disetujui", "Ditolak"]} placeholder="Semua status" />
                        <SmartSelect value={cabangId} onChange={(value) => setCabangId(value)} options={[{ id: "", nama_cabang: "Semua cabang" }, ...cabangs]} placeholder="Semua cabang" getOptionValue={(c) => c.id} getOptionLabel={(c) => c.nama_cabang} getOptionDescription={(c) => c.kode ? `Kode ${c.kode}` : ""} />
                        <SmartSelect value={kategoriId} onChange={(value) => setKategoriId(value)} options={[{ id: "", nama_kategori: "Semua kategori" }, ...kategoris]} placeholder="Semua kategori" getOptionValue={(k) => k.id} getOptionLabel={(k) => k.nama_kategori} />
                        <button className="btn-light justify-center" type="button" onClick={() => setSortDir(sortDir === "desc" ? "asc" : "desc")} title="Urut terbaru atau terlama"><ArrowDownUp size={16} className="mr-2" />{sortDir === "desc" ? "Terbaru" : "Terlama"}</button>
                    </div>
                </div>
                {rows.length === 0 ? <EmptyState /> : (
                    <div className="table-shell">
                        <table className="data-table min-w-[1240px] table-fixed">
                            <thead><tr><th className="w-40">Nomor</th><th className="w-[340px]">Program Kerja</th><th className="w-40">Cabang</th><th className="w-32">Tanggal</th><th className="w-40 text-right">Total</th><th className="w-32 text-center">Status</th><th className="w-32 text-right">Aksi</th></tr></thead>
                            <tbody>{rows.map((x) => {
                                const source = x.program_kerja ?? x.programKerja ?? x.pekerjaan;
                                const name = source?.nama_program ?? source?.nama_pekerjaan ?? "-";
                                const code = source?.kode_program ?? source?.kode_pekerjaan ?? "-";
                                const kategori = source?.kategori?.nama_kategori ?? "-";
                                const cabang = source?.cabang?.nama_cabang ?? "-";
                                return <tr key={x.id}><td className="table-nowrap font-semibold text-[#4cceac]">{x.nomor_rab}</td><td><div className="truncate font-semibold" title={name}>{code} · {name}</div><p className="text-xs text-slate-500">{kategori}</p></td><td>{cabang}</td><td className="table-nowrap">{formatDate(x.tanggal_rab)}</td><td className="table-currency font-semibold">{rupiah(x.total_rab)}</td><td className="text-center"><StatusBadge value={x.status_rab} /></td><td className="text-right"><div className="table-actions">{permissions["rab.view"] && <Link className="icon-btn" href={`/rab/${x.id}`} title="Lihat detail"><Eye size={15} /></Link>}{permissions["rab.edit"] && <Link className="icon-btn" href={`/rab/${x.id}/edit`} title="Edit"><Edit3 size={15} /></Link>}{permissions["rab.delete"] && <button type="button" className="icon-btn-danger" title="Hapus" onClick={() => confirm("Hapus RAB ini?") && router.delete(`/rab/${x.id}`, { preserveScroll: true })}><Trash2 size={15} /></button>}</div></td></tr>;
                            })}</tbody>
                        </table>
                    </div>
                )}
                <Pagination meta={items} />
            </div>
        </AppLayout>
    );
}
