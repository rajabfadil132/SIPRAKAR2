import EmptyState from "@/Components/EmptyState";
import Pagination from "@/Components/Pagination";
import SmartSelect from "@/Components/SmartSelect";
import AppLayout from "@/Layouts/AppLayout";
import { formatDate } from "@/Utils/date";
import { router, usePage } from "@inertiajs/react";
import { RotateCcw, Search, Trash2, XCircle } from "lucide-react";
import { useState } from "react";

const rupiah = (value) =>
    new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value ?? 0));

const tabs = [
    { key: "program-kerja", label: "Arsip Program Kerja" },
    { key: "pekerjaan", label: "Arsip Pekerjaan" },
    { key: "rab", label: "Arsip RAB" },
];

function TabButton({ tab, active, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-xl px-4 py-2.5 text-sm font-bold transition ${
                active
                    ? "bg-[#6870fa] text-white shadow"
                    : "bg-[#1F2A40] text-[#a3a3a3] hover:bg-[#29314b] hover:text-white"
            }`}
        >
            {tab.label}
        </button>
    );
}

function ProgramKerjaRow({ item, permissions, isSuperadmin }) {
    return (
        <tr>
            <td className="table-nowrap font-semibold text-[#4cceac]">{item.kode_program}</td>
            <td>
                <div className="max-w-[260px] truncate font-bold" title={item.nama_program}>{item.nama_program}</div>
                <p className="line-clamp-1 text-xs text-slate-500">{item.kategori?.nama_kategori ?? "-"}</p>
                {item.delete_reason && <p className="mt-1 line-clamp-2 text-xs text-red-300">Alasan: {item.delete_reason}</p>}
            </td>
            <td><div className="max-w-[140px] truncate">{item.cabang?.nama_cabang ?? "-"}</div></td>
            <td className="table-nowrap">{item.tahun ?? "-"}</td>
            <td className="table-nowrap">{formatDate(item.deleted_at)}</td>
            <td className="text-right">
                <div className="table-actions">
                    <span className="text-xs text-slate-500">{item.deleter?.name ?? "-"}</span>
                    {permissions["arsip.view"] && (
                        <button
                            type="button"
                            className="icon-btn"
                            onClick={() => confirm("Pulihkan program kerja ini dari arsip?") && router.post(`/arsip/program-kerja/${item.id}/restore`, {}, { preserveScroll: true })}
                            title="Pulihkan"
                        >
                            <RotateCcw size={15} />
                        </button>
                    )}
                    {permissions["arsip.view"] && isSuperadmin && (
                        <button
                            type="button"
                            className="icon-btn-danger"
                            onClick={() => confirm("Hapus permanen program kerja ini? Data tidak bisa dikembalikan.") && router.delete(`/arsip/program-kerja/${item.id}/force`, {}, { preserveScroll: true })}
                            title="Hapus permanen"
                        >
                            <Trash2 size={15} />
                        </button>
                    )}
                </div>
            </td>
        </tr>
    );
}

function PekerjaanRow({ item, permissions, isSuperadmin }) {
    return (
        <tr>
            <td className="table-nowrap font-semibold text-[#4cceac]">{item.kode_pekerjaan}</td>
            <td>
                <div className="max-w-[260px] truncate font-bold" title={item.nama_pekerjaan}>{item.nama_pekerjaan}</div>
                <p className="line-clamp-1 text-xs text-slate-500">{item.kategori?.nama_kategori ?? "-"}</p>
                {item.program_kerja?.kode_program && <p className="line-clamp-1 text-xs text-slate-500">Sumber: {item.program_kerja.kode_program}</p>}
                {item.delete_reason && <p className="mt-1 line-clamp-2 text-xs text-red-300">Alasan: {item.delete_reason}</p>}
            </td>
            <td><div className="max-w-[140px] truncate">{item.cabang?.nama_cabang ?? "-"}</div></td>
            <td className="table-nowrap">{formatDate(item.target_selesai)}</td>
            <td><b>{item.progress ?? 0}%</b></td>
            <td className="table-nowrap">{formatDate(item.deleted_at)}</td>
            <td className="text-right">
                <div className="table-actions">
                    <span className="text-xs text-slate-500">{item.deleter?.name ?? "-"}</span>
                    {permissions["arsip.view"] && (
                        <button
                            type="button"
                            className="icon-btn"
                            onClick={() => confirm("Pulihkan pekerjaan ini dari arsip?") && router.post(`/arsip/pekerjaan/${item.id}/restore`, {}, { preserveScroll: true })}
                            title="Pulihkan"
                        >
                            <RotateCcw size={15} />
                        </button>
                    )}
                    {permissions["arsip.view"] && isSuperadmin && (
                        <button
                            type="button"
                            className="icon-btn-danger"
                            onClick={() => confirm("Hapus permanen pekerjaan ini? Data tidak bisa dikembalikan.") && router.delete(`/arsip/pekerjaan/${item.id}/force`, {}, { preserveScroll: true })}
                            title="Hapus permanen"
                        >
                            <Trash2 size={15} />
                        </button>
                    )}
                </div>
            </td>
        </tr>
    );
}

function RabRow({ item, permissions, isSuperadmin }) {
    const source = item.program_kerja ?? item.pekerjaan;
    return (
        <tr>
            <td className="table-nowrap font-semibold text-[#4cceac]">{item.nomor_rab}</td>
            <td>
                <div className="max-w-[260px] truncate font-bold" title={source?.nama_program ?? source?.nama_pekerjaan ?? "-"}>
                    {source?.nama_program ?? source?.nama_pekerjaan ?? "-"}
                </div>
                <p className="line-clamp-1 text-xs text-slate-500">
                    {item.program_kerja ? "Program Kerja" : item.pekerjaan ? "Pekerjaan" : "-"}
                </p>
                {item.catatan && <p className="mt-1 line-clamp-2 text-xs text-red-300">Alasan: {item.catatan}</p>}
            </td>
            <td><div className="max-w-[140px] truncate">{item.program_kerja?.cabang?.nama_cabang ?? item.pekerjaan?.cabang?.nama_cabang ?? "-"}</div></td>
            <td><b className="table-currency">{rupiah(item.total_rab)}</b></td>
            <td className="table-nowrap">{formatDate(item.deleted_at)}</td>
            <td className="text-right">
                <div className="table-actions">
                    <span className="text-xs text-slate-500">{item.deleter?.name ?? "-"}</span>
                    {permissions["arsip.view"] && (
                        <button
                            type="button"
                            className="icon-btn"
                            onClick={() => confirm("Pulihkan RAB ini dari arsip?") && router.post(`/arsip/rab/${item.id}/restore`, {}, { preserveScroll: true })}
                            title="Pulihkan"
                        >
                            <RotateCcw size={15} />
                        </button>
                    )}
                    {permissions["arsip.view"] && isSuperadmin && (
                        <button
                            type="button"
                            className="icon-btn-danger"
                            onClick={() => confirm("Hapus permanen RAB ini? Data tidak bisa dikembalikan.") && router.delete(`/arsip/rab/${item.id}/force`, {}, { preserveScroll: true })}
                            title="Hapus permanen"
                        >
                            <Trash2 size={15} />
                        </button>
                    )}
                </div>
            </td>
        </tr>
    );
}

export default function ArsipIndex({ items = { data: [], links: [] }, permissions = {}, kategoris = [], cabangs = [], activeTab = "program-kerja" }) {
    const { auth } = usePage().props;
    const isSuperadmin = Boolean(auth?.isSuperadmin);
    const [active, setActive] = useState(activeTab);
    const [search, setSearch] = useState("");
    const [cabangId, setCabangId] = useState("");
    const [kategoriId, setKategoriId] = useState("");

    const switchTab = (tab) => {
        setActive(tab);
        setSearch("");
        setCabangId("");
        setKategoriId("");
        router.get(`/arsip?tab=${tab}`, {}, { preserveState: true, preserveScroll: true });
    };

    const handleSearch = () => {
        const params = { tab: active, search, cabang_id: cabangId };
        if (active !== "rab") params.kategori_id = kategoriId;
        router.get("/arsip", params, { preserveState: true, preserveScroll: true });
    };

    const handleFilterChange = (tab = active) => {
        const params = { tab, search, cabang_id: cabangId };
        if (tab !== "rab") params.kategori_id = kategoriId;
        router.get("/arsip", params, { preserveState: true, preserveScroll: true });
    };

    const resetFilters = () => {
        setSearch("");
        setCabangId("");
        setKategoriId("");
        router.get(`/arsip?tab=${active}`, {}, { preserveState: true, preserveScroll: true });
    };

    const RowComponent = active === "program-kerja" ? ProgramKerjaRow : active === "pekerjaan" ? PekerjaanRow : RabRow;
    const rows = items?.data ?? [];

    return (
        <AppLayout title="Arsip">
            <div className="mb-5 flex flex-col justify-between gap-3 md:flex-row md:items-center">
                <p className="text-sm text-slate-500">
                    Data yang dihapus secara soft delete. Arsip disimpan sementara sebelum dihapus permanen oleh sistem.
                </p>
            </div>

            <div className="page-card">
                {/* Tab Navigation */}
                <div className="mb-5 flex flex-wrap gap-2 border-b border-[#29314b] pb-4">
                    {tabs.map((tab) => (
                        <TabButton key={tab.key} tab={tab} active={active === tab.key} onClick={() => switchTab(tab.key)} />
                    ))}
                </div>

                {/* Filters */}
                <div className="mb-4 space-y-3">
                    <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
                        <div className="relative">
                            <Search className="absolute left-3 top-3 h-4 w-4 text-slate-400" />
                            <input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === "Enter" && handleSearch()}
                                className="input pl-9"
                                placeholder="Cari..."
                                type="search"
                            />
                        </div>
                        <button className="btn-light justify-center" type="button" onClick={resetFilters}>
                            <XCircle size={16} className="mr-2" />Reset
                        </button>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-[190px_190px_auto]">
                        <SmartSelect
                            value={cabangId}
                            onChange={(value) => { setCabangId(value); handleFilterChange(active); }}
                            options={[{ id: "", nama_cabang: "Semua cabang" }, ...cabangs]}
                            placeholder="Semua cabang"
                            getOptionValue={(x) => x.id}
                            getOptionLabel={(x) => x.nama_cabang}
                        />
                        {active !== "rab" && (
                            <SmartSelect
                                value={kategoriId}
                                onChange={(value) => { setKategoriId(value); handleFilterChange(active); }}
                                options={[{ id: "", nama_kategori: "Semua kategori" }, ...kategoris]}
                                placeholder="Semua kategori"
                                getOptionValue={(x) => x.id}
                                getOptionLabel={(x) => x.nama_kategori}
                            />
                        )}
                    </div>
                </div>

                {/* Table */}
                {rows.length === 0 ? (
                    <EmptyState />
                ) : (
                    <div className="table-shell">
                        <table className="data-table min-w-[1000px] table-fixed">
                            <thead>
                                <tr>
                                    {active === "program-kerja" && (
                                        <>
                                            <th className="w-36">Kode</th>
                                            <th className="w-[280px]">Nama Program</th>
                                            <th className="w-40">Cabang</th>
                                            <th className="w-20">Tahun</th>
                                            <th className="w-36">Dihapus</th>
                                            <th className="w-48 text-right">Aksi</th>
                                        </>
                                    )}
                                    {active === "pekerjaan" && (
                                        <>
                                            <th className="w-36">Kode</th>
                                            <th className="w-[280px]">Nama Pekerjaan</th>
                                            <th className="w-40">Cabang</th>
                                            <th className="w-32">Target</th>
                                            <th className="w-20">Progres</th>
                                            <th className="w-36">Dihapus</th>
                                            <th className="w-48 text-right">Aksi</th>
                                        </>
                                    )}
                                    {active === "rab" && (
                                        <>
                                            <th className="w-36">Nomor RAB</th>
                                            <th className="w-[280px]">Sumber</th>
                                            <th className="w-40">Cabang</th>
                                            <th className="w-36">Total</th>
                                            <th className="w-36">Dihapus</th>
                                            <th className="w-48 text-right">Aksi</th>
                                        </>
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((item) => (
                                    <RowComponent key={item.id} item={item} permissions={permissions} isSuperadmin={isSuperadmin} />
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