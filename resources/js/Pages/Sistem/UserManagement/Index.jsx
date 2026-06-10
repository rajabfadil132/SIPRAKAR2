import EmptyState from "@/Components/EmptyState";
import SmartSelect from "@/Components/SmartSelect";
import Pagination from "@/Components/Pagination";
import AppLayout from "@/Layouts/AppLayout";
import { Link, router } from "@inertiajs/react";
import { ArrowDownUp, Edit3, Eye, Plus, Search, ShieldCheck, Trash2, XCircle } from "lucide-react";
import { useMemo, useState } from "react";
import { useServerTableFilter } from "@/Utils/useServerTableFilter";

function StatusPill({ value }) {
    const active = value === "active" || value === 1 || value === true;
    const suspended = value === "suspended";
    return <span className={`badge ${active ? "bg-emerald-500/15 text-emerald-300" : suspended ? "bg-amber-500/15 text-amber-300" : "bg-red-500/15 text-red-300"}`}>{value ?? "-"}</span>;
}

export default function Index({ items, filters = {}, permissions = {}, canManagePermissions = false, roles = [], roleCategories = [], cabangs = [] }) {
    const rows = items?.data ?? [];
    const [search, setSearch] = useState(filters.search ?? "");
    const [status, setStatus] = useState(filters.status ?? "");
    const [roleId, setRoleId] = useState(filters.role_id ?? "");
    const [categoryId, setCategoryId] = useState(filters.role_category_id ?? "");
    const [cabangId, setCabangId] = useState(filters.cabang_id ?? "");
    const [sortDir, setSortDir] = useState(filters.sort_dir ?? "desc");
    const categoryOptions = useMemo(() => {
        if (!roleId) return roleCategories;
        return roleCategories.filter((category) => String(category.role_id) === String(roleId));
    }, [roleCategories, roleId]);

    useServerTableFilter("/users-management", { search, status, role_id: roleId, role_category_id: categoryId, cabang_id: cabangId, sort_dir: sortDir }, filters);

    const chooseRole = (value) => {
        setRoleId(value);
        setCategoryId("");
    };

    return (
        <AppLayout title="Manajemen Pengguna">
            <div className="mb-5 flex flex-col justify-between gap-3 md:flex-row md:items-center">
                <p className="text-sm text-slate-500">Kelola pengguna, role dinamis, subkategori role, jenis identitas, cabang, status akun, dan hak akses.</p>
                <div className="flex flex-wrap gap-2">
                    {canManagePermissions && <Link href="/role-permissions" className="btn-light"><ShieldCheck size={16} className="mr-2" />Role & Hak Akses</Link>}
                    {permissions["users.create"] && <Link href="/users-management/create" className="btn-primary"><Plus size={16} className="mr-2" />Tambah User</Link>}
                </div>
            </div>
            <div className="page-card">
                <div className="mb-5 space-y-3">
                    <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
                        <div className="relative"><Search className="absolute left-3 top-3 h-4 w-4 text-slate-400" /><input value={search} onChange={(e) => setSearch(e.target.value)} className="input pl-9" placeholder="Cari nama, NIK/NIM/NIP, email, role, subkategori, cabang..." type="search" /></div>
                        <button className="btn-light justify-center" type="button" onClick={() => { setSearch(""); setStatus(""); setRoleId(""); setCategoryId(""); setCabangId(""); setSortDir("desc"); }}><XCircle size={16} className="mr-2" />Reset</button>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-[160px_180px_210px_200px_auto]">
                        <SmartSelect value={status} onChange={(value) => setStatus(value)} options={[{ value: "", label: "Semua status" }, { value: "active", label: "Active" }, { value: "inactive", label: "Inactive" }, { value: "suspended", label: "Suspended" }]} placeholder="Semua status" />
                        <SmartSelect value={roleId} onChange={chooseRole} options={[{ id: "", nama_role: "Semua role" }, ...roles]} placeholder="Semua role" getOptionValue={(r) => r.id} getOptionLabel={(r) => r.nama_role} />
                        <SmartSelect value={categoryId} onChange={(value) => setCategoryId(value)} options={[{ id: "", name: "Semua subkategori" }, ...categoryOptions]} placeholder="Semua subkategori" getOptionValue={(r) => r.id} getOptionLabel={(r) => r.name} getOptionDescription={(r) => r.role?.nama_role ?? ""} />
                        <SmartSelect value={cabangId} onChange={(value) => setCabangId(value)} options={[{ id: "", nama_cabang: "Semua cabang" }, ...cabangs]} placeholder="Semua cabang" getOptionValue={(c) => c.id} getOptionLabel={(c) => c.nama_cabang} getOptionDescription={(c) => c.kode ? `Kode ${c.kode}` : ""} />
                        <button className="btn-light justify-center" type="button" onClick={() => setSortDir(sortDir === "desc" ? "asc" : "desc")} title="Urut terbaru atau terlama"><ArrowDownUp size={16} className="mr-2" />{sortDir === "desc" ? "Terbaru" : "Terlama"}</button>
                    </div>
                </div>
                {rows.length === 0 ? <EmptyState /> : (
                    <div className="table-shell">
                        <table className="data-table min-w-[1180px] table-fixed">
                            <thead><tr><th className="w-52">User</th><th className="w-44">Identitas</th><th className="w-64 table-nowrap">Email</th><th className="w-40">Role</th><th className="w-36">Cabang</th><th className="w-24 text-center">Status</th><th className="w-28 text-right">Aksi</th></tr></thead>
                            <tbody>{rows.map((u) => <tr key={u.id}>
                                <td className="whitespace-nowrap"><b>{u.name}</b><p className="text-xs text-slate-500">{u.phone ?? "-"}</p></td>
                                <td className="whitespace-nowrap"><b className="text-sm text-[#4cceac]">{u.jenis_identitas?.nama_jenis ?? u.identity_type ?? "Identitas"}</b><p className="text-xs text-slate-500">{u.identity_number ?? "-"}</p></td>
                                <td className="table-nowrap">{u.email}</td>
                                <td className="whitespace-nowrap"><b>{u.role?.nama_role ?? "-"}</b><p className="truncate text-xs text-slate-500">{u.role_category?.name ?? u.roleCategory?.name ?? "Tanpa subkategori"}</p></td>
                                <td className="whitespace-nowrap">{u.cabang?.nama_cabang ?? "-"}</td>
                                <td className="text-center"><StatusPill value={u.status} /></td>
                                <td className="text-right"><div className="table-actions">{permissions["users.show"] && <Link href={`/users-management/${u.id}`} className="icon-btn" title="Lihat detail"><Eye size={15} /></Link>}{permissions["users.edit"] && <Link href={`/users-management/${u.id}/edit`} className="icon-btn" title="Edit"><Edit3 size={15} /></Link>}{permissions["users.delete"] && <button type="button" className="icon-btn-danger" onClick={() => confirm("Hapus user ini?") && router.delete(`/users-management/${u.id}`, { preserveScroll: true })} title="Hapus"><Trash2 size={15} /></button>}</div></td>
                            </tr>)}</tbody>
                        </table>
                    </div>
                )}
                <Pagination meta={items} />
            </div>
        </AppLayout>
    );
}
