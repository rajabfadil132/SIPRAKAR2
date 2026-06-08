import AppLayout from "@/Layouts/AppLayout";
import { router, useForm } from "@inertiajs/react";
import { CheckCircle2, Edit3, Plus, Save, Settings2, Trash2, XCircle } from "lucide-react";
import { useEffect, useMemo, useState } from "react";

function Toggle({ checked, disabled, onChange }) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onChange}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition ${checked ? "bg-[#6870fa]" : "bg-[#29314b]"} ${disabled ? "cursor-not-allowed opacity-50" : ""}`}
            aria-pressed={checked}
        >
            <span className={`inline-block h-5 w-5 transform rounded-full bg-white transition ${checked ? "translate-x-5" : "translate-x-1"}`} />
        </button>
    );
}

function Badge({ children, tone = "slate" }) {
    const tones = {
        green: "bg-emerald-500/15 text-emerald-300",
        red: "bg-red-500/15 text-red-300",
        amber: "bg-amber-500/15 text-amber-300",
        slate: "bg-slate-500/15 text-slate-300",
    };
    return <span className={`badge ${tones[tone] ?? tones.slate}`}>{children}</span>;
}

export default function Permissions({ roles = [], groups = {} }) {
    const [selectedId, setSelectedId] = useState(roles[0]?.id ?? null);
    const selected = useMemo(() => roles.find((role) => String(role.id) === String(selectedId)) ?? roles[0] ?? null, [roles, selectedId]);
    const [maps, setMaps] = useState(() => Object.fromEntries(roles.map((role) => [role.id, role.permissions ?? {}])));
    const [editingRole, setEditingRole] = useState(null);
    const [editingCategory, setEditingCategory] = useState(null);

    const roleForm = useForm({ nama_role: "", keterangan: "", is_active: true });
    const editRoleForm = useForm({ nama_role: "", keterangan: "", is_active: true });
    const categoryForm = useForm({ name: "", description: "", is_active: true });
    const editCategoryForm = useForm({ name: "", description: "", is_active: true });

    useEffect(() => {
        setMaps(Object.fromEntries(roles.map((role) => [role.id, role.permissions ?? {}])));
        if (!roles.some((role) => String(role.id) === String(selectedId))) {
            setSelectedId(roles[0]?.id ?? null);
        }
    }, [roles]);

    useEffect(() => {
        if (!selected) return;
        setEditingRole(null);
        setEditingCategory(null);
        editRoleForm.setData({ nama_role: selected.nama_role ?? "", keterangan: selected.keterangan ?? "", is_active: Boolean(selected.is_active) });
        categoryForm.setData({ name: "", description: "", is_active: true });
    }, [selected?.id]);

    const setPermission = (roleId, key) => {
        setMaps((current) => ({
            ...current,
            [roleId]: {
                ...(current[roleId] ?? {}),
                [key]: !(current[roleId]?.[key] ?? false),
            },
        }));
    };

    const savePermissions = () => {
        if (!selected) return;
        router.put(`/role-permissions/${selected.id}`, { permissions: maps[selected.id] ?? {} }, { preserveScroll: true });
    };

    const createRole = (event) => {
        event.preventDefault();
        roleForm.post("/role-permissions/roles", {
            preserveScroll: true,
            onSuccess: () => roleForm.reset(),
        });
    };

    const startEditRole = () => {
        if (!selected) return;
        setEditingRole(selected.id);
        editRoleForm.setData({ nama_role: selected.nama_role ?? "", keterangan: selected.keterangan ?? "", is_active: Boolean(selected.is_active) });
    };

    const saveRole = (event) => {
        event.preventDefault();
        if (!selected) return;
        editRoleForm.put(`/role-permissions/roles/${selected.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditingRole(null),
        });
    };

    const deleteRole = () => {
        if (!selected) return;
        if (!confirm(`${selected.system_locked || (selected.users_count ?? 0) > 0 ? "Nonaktifkan" : "Hapus"} role ${selected.nama_role}? Role sistem atau role yang masih digunakan tidak akan dihapus permanen.`)) return;
        router.delete(`/role-permissions/roles/${selected.id}`, { preserveScroll: true });
    };

    const createCategory = (event) => {
        event.preventDefault();
        if (!selected) return;
        categoryForm.post(`/role-permissions/${selected.id}/categories`, {
            preserveScroll: true,
            onSuccess: () => categoryForm.reset(),
        });
    };

    const startEditCategory = (category) => {
        setEditingCategory(category.id);
        editCategoryForm.setData({ name: category.name ?? "", description: category.description ?? "", is_active: Boolean(category.is_active) });
    };

    const saveCategory = (event) => {
        event.preventDefault();
        if (!editingCategory) return;
        editCategoryForm.put(`/role-permissions/categories/${editingCategory}`, {
            preserveScroll: true,
            onSuccess: () => setEditingCategory(null),
        });
    };

    const deleteCategory = (category) => {
        if (!confirm(`Hapus subkategori ${category.name}? Subkategori hanya bisa dihapus jika belum digunakan user.`)) return;
        router.delete(`/role-permissions/categories/${category.id}`, { preserveScroll: true });
    };

    return (
        <AppLayout title="Role & Hak Akses">
            <p className="mb-6 text-sm text-slate-500">Kelola role dinamis, subkategori role, dan hak akses setiap role. Role superadmin dilindungi agar sistem tetap memiliki pemilik utama.</p>
            <div className="grid gap-6 xl:grid-cols-[320px_1fr]">
                <div className="space-y-6">
                    <div className="page-card">
                        <div className="mb-4 flex items-center gap-2">
                            <Plus size={18} className="text-[#4cceac]" />
                            <h2 className="font-black">Tambah Role</h2>
                        </div>
                        <form onSubmit={createRole} className="space-y-3">
                            <label className="block text-sm font-bold">Nama Role
                                <input className="input mt-1" value={roleForm.data.nama_role} onChange={(e) => roleForm.setData("nama_role", e.target.value)} placeholder="contoh: verifikator" />
                                {roleForm.errors.nama_role && <p className="mt-1 text-xs text-red-300">{roleForm.errors.nama_role}</p>}
                            </label>
                            <label className="block text-sm font-bold">Keterangan
                                <textarea className="input mt-1" rows="3" value={roleForm.data.keterangan} onChange={(e) => roleForm.setData("keterangan", e.target.value)} placeholder="Keterangan singkat role" />
                            </label>
                            <label className="flex items-center justify-between rounded-xl border border-[#29314b] bg-[#141b2d] px-4 py-3 text-sm font-bold">
                                Aktif
                                <Toggle checked={roleForm.data.is_active} onChange={() => roleForm.setData("is_active", !roleForm.data.is_active)} />
                            </label>
                            <button type="submit" className="btn-primary w-full justify-center" disabled={roleForm.processing}><Plus size={16} className="mr-2" />Tambah Role</button>
                        </form>
                    </div>

                    <div className="page-card h-fit space-y-2">
                        <h2 className="mb-3 font-black">Daftar Role</h2>
                        {roles.map((role) => (
                            <button key={role.id} type="button" onClick={() => setSelectedId(role.id)} className={`w-full rounded-xl px-4 py-3 text-left text-sm font-bold transition ${selected?.id === role.id ? "bg-[#6870fa] text-white" : "bg-[#141b2d] text-[#e0e0e0] hover:bg-white/10"}`}>
                                <span className="block">{role.nama_role}</span>
                                <span className={`mt-1 block text-xs ${selected?.id === role.id ? "text-white/80" : "text-slate-500"}`}>{role.users_count ?? 0} user · {(role.categories ?? []).length} subkategori</span>
                            </button>
                        ))}
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="page-card">
                        <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="text-xl font-black">{selected?.nama_role ?? "Role"}</h2>
                                    {selected?.system_locked && <Badge tone="green">role sistem</Badge>}
                                    {selected?.is_active ? <Badge tone="green">aktif</Badge> : <Badge tone="red">nonaktif</Badge>}
                                    <Badge>{selected?.users_count ?? 0} user</Badge>
                                </div>
                                <p className="mt-1 text-sm text-slate-500">{selected?.keterangan ?? "Belum ada keterangan role."}</p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <button type="button" className="btn-light" onClick={startEditRole} disabled={!selected}><Edit3 size={16} className="mr-2" />Edit Role</button>
                                <button type="button" className="btn-light text-red-300" onClick={deleteRole} disabled={!selected || selected.locked}><Trash2 size={16} className="mr-2" />{selected?.system_locked || (selected?.users_count ?? 0) > 0 ? "Nonaktifkan" : "Hapus"}</button>
                            </div>
                        </div>

                        {editingRole === selected?.id && (
                            <form onSubmit={saveRole} className="mb-6 grid gap-3 rounded-2xl border border-[#29314b] bg-[#141b2d] p-4 md:grid-cols-2">
                                <label className="block text-sm font-bold">Nama Role
                                    <input className="input mt-1" value={editRoleForm.data.nama_role} onChange={(e) => editRoleForm.setData("nama_role", e.target.value)} disabled={selected?.system_locked} />
                                    {selected?.system_locked && <span className="mt-1 block text-xs text-slate-500">Nama/slug role sistem dikunci agar logika sistem tetap aman.</span>}
                                    {editRoleForm.errors.nama_role && <p className="mt-1 text-xs text-red-300">{editRoleForm.errors.nama_role}</p>}
                                </label>
                                <label className="flex items-center justify-between rounded-xl border border-[#29314b] bg-[#1F2A40] px-4 py-3 text-sm font-bold">
                                    Status Aktif
                                    <Toggle checked={editRoleForm.data.is_active} disabled={selected?.slug === "superadmin"} onChange={() => editRoleForm.setData("is_active", !editRoleForm.data.is_active)} />
                                </label>
                                <label className="block text-sm font-bold md:col-span-2">Keterangan
                                    <textarea className="input mt-1" rows="3" value={editRoleForm.data.keterangan} onChange={(e) => editRoleForm.setData("keterangan", e.target.value)} />
                                </label>
                                <div className="flex justify-end gap-2 md:col-span-2">
                                    <button type="button" className="btn-light" onClick={() => setEditingRole(null)}><XCircle size={16} className="mr-2" />Batal</button>
                                    <button type="submit" className="btn-primary" disabled={editRoleForm.processing}><Save size={16} className="mr-2" />Simpan Role</button>
                                </div>
                            </form>
                        )}

                        <div className="grid gap-4 lg:grid-cols-[1fr_320px]">
                            <div>
                                <div className="mb-3 flex items-center gap-2">
                                    <Settings2 size={17} className="text-[#4cceac]" />
                                    <h3 className="font-black">Subkategori Role</h3>
                                </div>
                                <div className="space-y-2">
                                    {(selected?.categories ?? []).length === 0 && <p className="rounded-xl border border-dashed border-[#29314b] p-3 text-sm text-slate-500">Role ini belum memiliki subkategori. Subkategori bersifat opsional.</p>}
                                    {(selected?.categories ?? []).map((category) => (
                                        <div key={category.id} className="rounded-xl border border-[#29314b] bg-[#141b2d] p-3">
                                            {editingCategory === category.id ? (
                                                <form onSubmit={saveCategory} className="space-y-3">
                                                    <input className="input" value={editCategoryForm.data.name} onChange={(e) => editCategoryForm.setData("name", e.target.value)} />
                                                    <textarea className="input" rows="2" value={editCategoryForm.data.description} onChange={(e) => editCategoryForm.setData("description", e.target.value)} />
                                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                                        <label className="flex items-center gap-2 text-sm font-bold"><Toggle checked={editCategoryForm.data.is_active} onChange={() => editCategoryForm.setData("is_active", !editCategoryForm.data.is_active)} />Aktif</label>
                                                        <div className="flex gap-2"><button type="button" className="btn-light" onClick={() => setEditingCategory(null)}>Batal</button><button type="submit" className="btn-primary" disabled={editCategoryForm.processing}>Simpan</button></div>
                                                    </div>
                                                </form>
                                            ) : (
                                                <div className="flex items-start justify-between gap-3">
                                                    <div>
                                                        <div className="flex flex-wrap items-center gap-2"><b>{category.name}</b>{category.is_active ? <Badge tone="green">aktif</Badge> : <Badge tone="red">nonaktif</Badge>}</div>
                                                        <p className="mt-1 text-xs text-slate-500">{category.description ?? "Tidak ada keterangan."}</p>
                                                    </div>
                                                    <div className="flex shrink-0 gap-1"><button type="button" className="icon-btn" onClick={() => startEditCategory(category)}><Edit3 size={15} /></button><button type="button" className="icon-btn-danger" onClick={() => deleteCategory(category)}><Trash2 size={15} /></button></div>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <form onSubmit={createCategory} className="rounded-2xl border border-[#29314b] bg-[#141b2d] p-4">
                                <h3 className="mb-3 font-black">Tambah Subkategori</h3>
                                <div className="space-y-3">
                                    <label className="block text-sm font-bold">Nama
                                        <input className="input mt-1" value={categoryForm.data.name} onChange={(e) => categoryForm.setData("name", e.target.value)} placeholder="contoh: Teknisi" />
                                        {categoryForm.errors.name && <p className="mt-1 text-xs text-red-300">{categoryForm.errors.name}</p>}
                                    </label>
                                    <label className="block text-sm font-bold">Keterangan
                                        <textarea className="input mt-1" rows="3" value={categoryForm.data.description} onChange={(e) => categoryForm.setData("description", e.target.value)} />
                                    </label>
                                    <label className="flex items-center justify-between rounded-xl border border-[#29314b] bg-[#1F2A40] px-4 py-3 text-sm font-bold">
                                        Aktif
                                        <Toggle checked={categoryForm.data.is_active} onChange={() => categoryForm.setData("is_active", !categoryForm.data.is_active)} />
                                    </label>
                                    <button type="submit" className="btn-primary w-full justify-center" disabled={!selected || categoryForm.processing}><Plus size={16} className="mr-2" />Tambah</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div className="page-card">
                        <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                            <div><h2 className="text-xl font-black">Hak Akses {selected?.nama_role}</h2><p className="text-sm text-slate-500">Toggle aktif berarti menu/aksi tersedia untuk role ini.</p></div>
                            <button type="button" className="btn-primary" onClick={savePermissions} disabled={!selected || selected.locked}><Save size={16} className="mr-2" />Simpan Hak Akses</button>
                        </div>
                        <div className="space-y-5">
                            {Object.entries(groups).map(([groupKey, group]) => (
                                <div key={groupKey} className="rounded-2xl border border-[#29314b] bg-[#141b2d] p-4">
                                    <h3 className="mb-3 font-bold">{group.label}</h3>
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {Object.entries(group.permissions).map(([key, label]) => (
                                            <div key={key} className="flex items-center justify-between gap-3 rounded-xl border border-[#29314b] bg-[#1F2A40] px-4 py-3">
                                                <div><b className="text-sm">{label}</b><p className="text-xs text-slate-500">{key}</p></div>
                                                <Toggle checked={Boolean(maps[selected?.id]?.[key])} disabled={!selected || selected.locked} onChange={() => setPermission(selected.id, key)} />
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
