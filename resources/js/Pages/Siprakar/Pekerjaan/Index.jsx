import EmptyState from "@/Components/EmptyState";
import SmartSelect from "@/Components/SmartSelect";
import Pagination from "@/Components/Pagination";
import StatusBadge from "@/Components/StatusBadge";
import AppLayout from "@/Layouts/AppLayout";
import { formatDate } from "@/Utils/date";
import { useServerTableFilter } from "@/Utils/useServerTableFilter";
import { Link, router, useForm, usePage } from "@inertiajs/react";
import { ArrowDownUp, Edit3, Eye, FileSpreadsheet, Plus, RotateCcw, Search, Trash2, XCircle } from "lucide-react";
import { useState } from "react";

const rupiah = (value) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value ?? 0));

function RabInfo({ pekerjaan }) {
    if (!pekerjaan.rab) {
        return <div className="flex h-full min-h-10 items-center justify-start"><span className="badge bg-slate-500/15 text-slate-300">Tanpa Anggaran</span></div>;
    }

    return (
        <div className="flex min-w-0 flex-col items-start justify-center gap-1 leading-tight">
            <StatusBadge value={pekerjaan.rab.status_rab} />
            <span className="table-currency max-w-full truncate font-semibold text-slate-500" title={rupiah(pekerjaan.rab.total_rab)}>{rupiah(pekerjaan.rab.total_rab)}</span>
        </div>
    );
}

function PetugasList({ pekerjaan }) {
    const assignments = pekerjaan.petugas_tambahan ?? pekerjaan.petugasTambahan ?? [];
    const names = assignments.map((item) => item.user?.name ?? item.nama_petugas_manual).filter(Boolean);
    if (names.length === 0 && pekerjaan.petugas?.name) names.push(pekerjaan.petugas.name);
    if (names.length === 0) return <span>-</span>;
    return <div className="max-w-[180px] truncate" title={names.join(", ")}>{names.join(", ")}</div>;
}

export default function Index({ items, filters = {}, permissions = {}, kategoris = [], users = [], cabangs = [], statuses = [], title = "Data Pekerjaan", description = "Kelola pekerjaan sarana prasarana, petugas, status, progress, dan anggaran.", basePath = "/pekerjaan", isArchive = false, isMyTasks = false }) {
    const { auth } = usePage().props;
    const isSuperadmin = Boolean(auth?.isSuperadmin);
    const rows = items?.data ?? [];
    const [search, setSearch] = useState(filters.search ?? "");
    const [status, setStatus] = useState(filters.status ?? "");
    const [kategoriId, setKategoriId] = useState(filters.kategori_id ?? "");
    const [petugasId, setPetugasId] = useState(filters.petugas_id ?? "");
    const [cabangId, setCabangId] = useState(filters.cabang_id ?? "");
    const [sortDir, setSortDir] = useState(filters.sort_dir ?? "desc");
    const [deleteTarget, setDeleteTarget] = useState(null);
    const deleteForm = useForm({ delete_reason: "" });
    const canViewRab = Boolean(permissions["rab.view"] || permissions["rab.create"] || permissions["rab.edit"]);

    useServerTableFilter(basePath, { search, status, kategori_id: kategoriId, petugas_id: petugasId, cabang_id: cabangId, sort_by: "created_at", sort_dir: sortDir }, filters);

    const resetFilters = () => { setSearch(""); setStatus(""); setKategoriId(""); setPetugasId(""); setCabangId(""); setSortDir("desc"); };
    const askDelete = (row) => { setDeleteTarget(row); deleteForm.setData("delete_reason", ""); deleteForm.clearErrors(); };
    const submitDelete = (e) => {
        e.preventDefault();
        if (!deleteTarget) return;
        deleteForm.delete(`/pekerjaan/${deleteTarget.id}`, { preserveScroll: true, onSuccess: () => setDeleteTarget(null) });
    };

    return (
        <AppLayout title={title}>
            <div className="mb-5 flex flex-col justify-between gap-3 md:flex-row md:items-center">
                <p className="text-sm text-slate-500">{description}</p>
                <div className="flex flex-wrap gap-2">
                    {!isMyTasks && <Link href={isArchive ? "/pekerjaan" : "/pekerjaan/archive"} className="btn-light">{isArchive ? "Data Aktif" : "Arsip"}</Link>}
                    {!isArchive && !isMyTasks && permissions["pekerjaan.create"] && <Link href="/pekerjaan/create" className="btn-primary"><Plus size={16} className="mr-2" />Tambah Pekerjaan</Link>}
                </div>
            </div>

            <div className="page-card">
                <div className="mb-5 space-y-3">
                    <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
                        <div className="relative"><Search className="absolute left-3 top-3 h-4 w-4 text-slate-400" /><input value={search} onChange={(e) => setSearch(e.target.value)} className="input pl-9" placeholder="Cari kode, pekerjaan, cabang, kategori, petugas..." type="search" /></div>
                        <button className="btn-light justify-center" type="button" onClick={resetFilters}><XCircle size={16} className="mr-2" />Reset</button>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-[180px_190px_190px_190px_auto]">
                        <SmartSelect value={status} onChange={(value) => setStatus(value)} options={[{ value: "", label: "Semua status" }, ...(statuses.length ? statuses : ["Belum Diproses", "Diproses", "Selesai", "Dibatalkan"])]} placeholder="Semua status" />
                        <SmartSelect value={kategoriId} onChange={(value) => setKategoriId(value)} options={[{ id: "", nama_kategori: "Semua kategori" }, ...kategoris]} placeholder="Semua kategori" getOptionValue={(x) => x.id} getOptionLabel={(x) => x.nama_kategori} getOptionDescription={(x) => x.keterangan || ""} />
                        <SmartSelect value={petugasId} onChange={(value) => setPetugasId(value)} options={[{ id: "", name: "Semua petugas" }, ...users]} placeholder="Semua petugas" getOptionValue={(x) => x.id} getOptionLabel={(x) => x.name} getOptionDescription={(x) => [x.role?.nama_role, x.role_category?.name || x.roleCategory?.name, x.email].filter(Boolean).join(' · ')} />
                        <SmartSelect value={cabangId} onChange={(value) => setCabangId(value)} options={[{ id: "", nama_cabang: "Semua cabang" }, ...cabangs]} placeholder="Semua cabang" getOptionValue={(x) => x.id} getOptionLabel={(x) => x.nama_cabang} getOptionDescription={(x) => x.kode ? `Kode ${x.kode}` : ""} />
                        <button className="btn-light justify-center" type="button" onClick={() => setSortDir(sortDir === "desc" ? "asc" : "desc")} title="Urutkan terbaru/terlama"><ArrowDownUp size={16} className="mr-2" />{sortDir === "desc" ? "Terbaru" : "Terlama"}</button>
                    </div>
                </div>

                {rows.length === 0 ? <EmptyState /> : (
                    <div className="table-shell">
                        <table className="data-table min-w-[1580px] table-fixed">
                            <thead>
                                <tr>
                                    <th className="w-40">Kode</th><th className="w-[300px]">Nama Pekerjaan</th><th className="w-44">Cabang</th><th className="w-52">Petugas</th><th className="w-40">Mulai</th><th className="w-40">Target</th><th className="w-44">Durasi</th><th className="w-40">Progres</th>{canViewRab && <th className="w-48">Anggaran</th>}<th className="w-36 text-center">Status</th><th className="w-56 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((p) => (
                                    <tr key={p.id}>
                                        <td className="table-nowrap font-semibold text-[#4cceac]">{p.kode_pekerjaan}</td>
                                        <td><div className="max-w-[280px] truncate font-bold" title={p.nama_pekerjaan}>{p.nama_pekerjaan}</div><p className="line-clamp-1 text-xs text-slate-500">{p.kategori?.nama_kategori ?? "-"}</p>{p.program_kerja?.kode_program && <p className="line-clamp-1 text-xs text-slate-500">Sumber: {p.program_kerja.kode_program}</p>}{isArchive && p.delete_reason && <p className="mt-1 line-clamp-2 text-xs text-red-300">Alasan: {p.delete_reason}</p>}</td>
                                        <td><div className="max-w-[160px] truncate" title={p.cabang?.nama_cabang}>{p.cabang?.nama_cabang ?? "-"}</div></td>
                                        <td><PetugasList pekerjaan={p} /></td>
                                        <td className="table-nowrap">{formatDate(p.tanggal_mulai)}</td>
                                        <td className="table-nowrap">{formatDate(p.target_selesai)}</td>
                                        <td><b>{p.durasi_label ?? "-"}</b><p className={`text-xs ${String(p.sisa_label ?? "").includes("terlambat") ? "text-red-300" : "text-slate-500"}`}>{p.sisa_label ?? "-"}</p></td>
                                        <td><div className="progress-cell"><div className="progress-bar"><div className="h-2 rounded-full bg-[#4cceac]" style={{ width: `${p.progress}%` }} /></div><span className="progress-value">{p.progress}%</span></div></td>
                                        {canViewRab && <td><RabInfo pekerjaan={p} /></td>}
                                        <td className="text-center"><StatusBadge value={p.status_label ?? p.status} /></td>
                                        <td className="text-right">
                                            <div className="table-actions">
                                                {isArchive ? <>
                                                    <span className="text-xs text-slate-500">Terhapus {formatDate(p.deleted_at)}</span>
                                                    {permissions["pekerjaan.delete"] && <button type="button" className="icon-btn" onClick={() => confirm("Pulihkan pekerjaan ini dari arsip?") && router.post(`/pekerjaan/archive/${p.id}/restore`, {}, { preserveScroll: true })} title="Pulihkan"><RotateCcw size={15} /></button>}
                                                    {isSuperadmin && permissions["pekerjaan.delete"] && <button type="button" className="icon-btn-danger" onClick={() => confirm("Hapus permanen pekerjaan ini? Data tidak bisa dikembalikan.") && router.delete(`/pekerjaan/archive/${p.id}/force`, { preserveScroll: true })} title="Hapus permanen"><Trash2 size={15} /></button>}
                                                </> : <>
                                                    {permissions["pekerjaan.show"] && <Link className="icon-btn" href={`/pekerjaan/${p.id}`} title="Lihat detail"><Eye size={15} /></Link>}
                                                    {p.rab && permissions["rab.view"] && <Link className="icon-btn" href={`/rab/${p.rab.id}`} title="Detail Anggaran"><FileSpreadsheet size={15} /></Link>}
                                                    
                                                    {permissions["pekerjaan.edit"] && <Link className="icon-btn" href={`/pekerjaan/${p.id}/edit`} title="Edit"><Edit3 size={15} /></Link>}
                                                    {permissions["pekerjaan.delete"] && <button type="button" className="icon-btn-danger" onClick={() => askDelete(p)} title="Hapus"><Trash2 size={15} /></button>}
                                                </>}
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

            {deleteTarget && (
                <div className="fixed inset-0 z-[80] flex items-center justify-center bg-black/60 p-4">
                    <form onSubmit={submitDelete} className="w-full max-w-lg rounded-3xl border border-[#29314b] bg-[#141b2d] p-6 shadow-2xl">
                        <h2 className="text-lg font-black text-white">Hapus pekerjaan ke arsip</h2>
                        <p className="mt-2 text-sm text-slate-400">Tulis alasan penghapusan agar riwayat arsip lebih jelas.</p>
                        <label className="mt-4 block text-sm font-semibold text-slate-300">Alasan penghapusan<textarea className="input mt-1 min-h-[110px]" value={deleteForm.data.delete_reason} onChange={(e) => deleteForm.setData("delete_reason", e.target.value)} required /></label>
                        {deleteForm.errors.delete_reason && <p className="mt-2 text-sm text-red-300">{deleteForm.errors.delete_reason}</p>}
                        <div className="mt-5 flex justify-end gap-2"><button type="button" className="btn-light" onClick={() => setDeleteTarget(null)}>Batal</button><button className="rounded-xl bg-red-500 px-4 py-2 text-sm font-bold text-white hover:bg-red-400" disabled={deleteForm.processing}>Hapus ke Arsip</button></div>
                    </form>
                </div>
            )}
        </AppLayout>
    );
}
