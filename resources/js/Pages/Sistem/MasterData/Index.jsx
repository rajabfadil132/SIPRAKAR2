import AuditInfo from "@/Components/AuditInfo";
import EmptyState from "@/Components/EmptyState";
import SmartSelect from "@/Components/SmartSelect";
import AppLayout from "@/Layouts/AppLayout";
import { router, useForm } from "@inertiajs/react";
import { ArrowDownUp, Edit3, Eye, Plus, Search, Trash2, X, XCircle } from "lucide-react";
import { useMemo, useState } from "react";

const tabs = [
    { key: "cabangs", label: "Cabang" },
    { key: "gedungs", label: "Gedung" },
    { key: "lantais", label: "Lantai" },
    { key: "ruangs", label: "Ruang" },
    { key: "kategoris", label: "Kategori" },
    { key: "jenis_identitas", label: "Jenis Identitas" },
];

const emptyByType = {
    cabangs: { nama_cabang: "", kode: "", alamat: "", status: "active" },
    gedungs: { cabang_id: "", nama_gedung: "", status: "active" },
    lantais: { gedung_id: "", nomor_lantai: "", nama_lantai: "", status: "active" },
    ruangs: { gedung_id: "", lantai_id: "", nama_ruang: "", kode_ruang: "", status: "active" },
    kategoris: { nama_kategori: "", keterangan: "", status: "active", role_relations: [] },
    jenis_identitas: { nama_jenis: "", kode: "", keterangan: "", status: "active" },
};

function StatusPill({ value }) {
    return <span className={`badge ${value === "active" ? "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300" : "bg-slate-500/15 text-slate-600 dark:text-slate-300"}`}>{value === "active" ? "Aktif" : "Nonaktif"}</span>;
}

function Info({ label, value }) {
    return (
        <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p>
            <b>{value || "-"}</b>
        </div>
    );
}

function lantaiNumber(row) {
    return row.nomor_lantai ?? row.lantai_master?.nomor_lantai ?? row.lantai ?? null;
}

function formatLantai(row) {
    const nomor = lantaiNumber(row);
    const raw = String(row?.nama_lantai ?? row?.lantai_master?.nama_lantai ?? row?.lantaiMaster?.nama_lantai ?? "").trim();
    if (raw && !/^\d+$/.test(raw) && raw.toLowerCase() !== "lantai 0") return raw;
    if (String(nomor) === "0" || raw === "0") return "Basement";
    if (nomor !== undefined && nomor !== null && nomor !== "") return `Lantai ${nomor}`;
    return raw || "-";
}

function gedungOfRuang(row) {
    return row.lantai_master?.gedung ?? null;
}

function nameOf(row, active) {
    if (row.nama_cabang) return row.nama_cabang;
    if (row.nama_gedung) return row.nama_gedung;
    if (row.nomor_lantai !== undefined && row.nomor_lantai !== null) return formatLantai(row);
    if (row.nama_ruang) return row.nama_ruang;
    if (row.nama_kategori) return row.nama_kategori;
    if (row.nama_jenis) return row.nama_jenis;
    if (row.name) return row.name;
    return "-";
}

function relationOf(row, active) {
    if (active === "cabangs") return row.kode || "-";
    if (active === "gedungs") return row.cabang?.nama_cabang ?? "-";
    if (active === "lantais") return row.gedung?.nama_gedung ?? "-";
    if (active === "ruangs") {
        const lt = row.lantai_master ?? row.lantaiMaster ?? {};
        return formatLantai(lt);
    }
    if (active === "kategoris") return row.keterangan || "-";
    if (active === "jenis_identitas") return row.kode || "-";
    return "-";
}

function kategoriRoleLabel(relation) {
    const roleName = relation?.role?.nama_role ?? relation?.role_nama ?? "Role";
    const categoryName = relation?.role_category?.name ?? relation?.roleCategory?.name ?? relation?.role_category_name;
    return categoryName ? `${roleName} › ${categoryName}` : `${roleName} › Semua subkategori`;
}

function buildKategoriRelationOptions(roles = [], roleCategories = []) {
    const roleOptions = roles.map((role) => ({
        key: `role:${role.id}`,
        role_id: role.id,
        role_category_id: null,
        label: `${role.nama_role} › Semua subkategori`,
        description: role.keterangan ?? "Cocok untuk semua user dalam role ini",
    }));

    const categoryOptions = roleCategories.map((category) => ({
        key: `category:${category.id}`,
        role_id: category.role_id,
        role_category_id: category.id,
        label: category.full_label ?? `${category.role_nama ?? "Role"} › ${category.name}`,
        description: "Cocok untuk subkategori role ini",
    }));

    return [...roleOptions, ...categoryOptions];
}

// QuickAddForm: stays open after saving for batch add
function QuickAddForm({ type, cabangs, gedungs, lantais, item, onClose, roles, roleCategories }) {
    const buildInitial = () => {
        const base = emptyByType[type];
        if (!item) return base;
        const baseWithItem = { ...base, ...item };
        if (type === 'kategoris') {
            if (item.roleRelations) {
                baseWithItem.role_relations = item.roleRelations.map((relation) => ({
                    role_id: String(relation.role_id),
                    role_category_id: relation.role_category_id ? String(relation.role_category_id) : '',
                    role: relation.role,
                    role_category: relation.role_category ?? relation.roleCategory,
                }));
            } else if (item.roleCategories) {
                baseWithItem.role_relations = item.roleCategories.map((rc) => ({
                    role_id: String(rc.role_id),
                    role_category_id: String(rc.id),
                    role: rc.role,
                    role_category: rc,
                }));
            }
        }
        return baseWithItem;
    };
    const initial = buildInitial();

    // For ruangs: pre-select gedung from first lantai's gedung
    const initialGedungId = type === "ruangs"
        ? (item?.lantai_master?.gedung_id ?? item?.lantaiMaster?.gedung_id ?? "")
        : (item?.gedung_id ?? initial.gedung_id ?? "");
    const [selectedGedungId, setSelectedGedungId] = useState(initialGedungId ? String(initialGedungId) : "");

    // For lantais: pre-select gedung_id
    const initialLantaiGedungId = type === "lantais"
        ? (item?.gedung_id ?? "")
        : "";
    const [selectedLantaiGedungId, setSelectedLantaiGedungId] = useState(
        type === "lantais" && item ? String(item.gedung_id ?? "") : ""
    );

    const form = useForm({
        ...initial,
        ...(type === "ruangs" ? { gedung_id: initialGedungId } : {}),
        ...(type === "lantais" ? { gedung_id: initialLantaiGedungId } : {}),
    });
    const isEdit = Boolean(item?.id);

    const selectedLantais = useMemo(
        () => lantais.filter((lantai) => !selectedGedungId || String(lantai.gedung_id ?? lantai.gedung?.id ?? "") === String(selectedGedungId)),
        [lantais, selectedGedungId]
    );

    const kategoriRelationOptions = useMemo(() => buildKategoriRelationOptions(roles, roleCategories), [roles, roleCategories]);

    const selectedCabangId = useMemo(() => {
        if (type === "gedungs") return form.data.cabang_id ? String(form.data.cabang_id) : "";
        if (type === "lantais" && selectedLantaiGedungId) {
            const found = gedungs.find((g) => String(g.id) === String(selectedLantaiGedungId));
            return found ? String(found.cabang_id ?? found.cabang?.id ?? "") : "";
        }
        if (type === "ruangs" && selectedGedungId) {
            const found = gedungs.find((g) => String(g.id) === String(selectedGedungId));
            return found ? String(found.cabang_id ?? found.cabang?.id ?? "") : "";
        }
        return "";
    }, [type, form.data.cabang_id, selectedLantaiGedungId, selectedGedungId, gedungs]);

    const scopedGedungs = useMemo(() => {
        if (!selectedCabangId) return gedungs;
        return gedungs.filter((g) => String(g.cabang_id ?? g.cabang?.id ?? "") === selectedCabangId);
    }, [gedungs, selectedCabangId]);

    const submit = (e) => {
        e.preventDefault();
        const payload = { ...form.data };

        if (type === "ruangs") payload.gedung_id = selectedGedungId;
        if (type === "lantais") payload.gedung_id = selectedLantaiGedungId;

        const method = item ? 'put' : 'post';
        const url = item ? `/master-data/${type}/${item.id}` : `/master-data/${type}`;
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                if (item) {
                    onClose();
                } else {
                    // Reset form fields but keep fixed selectors for batch add
                    if (type === "cabangs") {
                        form.setData({ ...emptyByType[type], status: form.data.status });
                    } else if (type === "gedungs") {
                        form.setData({ ...emptyByType[type], cabang_id: form.data.cabang_id, status: form.data.status });
                    } else if (type === "lantais") {
                        form.setData({ ...emptyByType[type], gedung_id: selectedLantaiGedungId, status: form.data.status });
                    } else if (type === "ruangs") {
                        form.setData({ ...emptyByType[type], gedung_id: selectedGedungId, lantai_id: "", status: form.data.status });
                    } else if (type === "kategoris") {
                        form.setData({ ...emptyByType[type], status: form.data.status, role_relations: form.data.role_relations ?? [] });
                    } else {
                        form.setData(emptyByType[type]);
                    }
                }
            },
            onError: () => {},
        };

        if (method === 'put') {
            router.post(url, { ...payload, _method: 'put' }, options);
        } else {
            router.post(url, payload, options);
        }
    };

    return (
        <div className="fixed inset-0 z-50 grid place-items-center bg-black/70 p-4 backdrop-blur-sm">
            <form onSubmit={submit} className="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 text-slate-800 shadow-2xl dark:border-[#29314b] dark:bg-[#1F2A40] dark:text-[#e0e0e0]">
                <div className="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-black">{item ? "Edit" : "Tambah"} {tabs.find((tab) => tab.key === type)?.label}</h2>
                        <p className="text-sm text-slate-500">
                            {type === "cabangs" ? "Tambah data cabang kampus baru." :
                             type === "gedungs" ? "Tambah data gedung pada cabang." :
                             type === "lantais" ? "Tambah data lantai pada gedung. Data lantai tidak boleh duplikat dalam satu gedung." :
                             type === "ruangs" ? "Tambah data ruang pada lantai." :
                             type === "kategoris" ? "Tambah kategori pekerjaan." :
                             type === "jenis_identitas" ? "Tambah jenis identitas user." :
                             "Master data bertingkat."}
                        </p>
                    </div>
                    <button type="button" onClick={onClose} className="rounded-xl border border-slate-200 p-2 hover:border-[#6870fa] dark:border-[#29314b]"><X size={18} /></button>
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                    {/* CABANGS */}
                    {type === "cabangs" && <>
                        <label className="block text-sm">Nama Cabang<input className="input mt-1" value={form.data.nama_cabang} onChange={(e) => form.setData("nama_cabang", e.target.value)} required /></label>
                        <label className="block text-sm">Kode<input className="input mt-1 uppercase" value={form.data.kode} onChange={(e) => form.setData("kode", e.target.value.replace(/[^a-zA-Z]/g, "").toUpperCase().slice(0, 3))} maxLength="3" placeholder="CTH: PUS" required /><span className="mt-1 block text-xs text-slate-500">3 huruf, contoh: PUS</span></label>
                        <label className="block text-sm md:col-span-2">Alamat<input className="input mt-1" value={form.data.alamat ?? ""} onChange={(e) => form.setData("alamat", e.target.value)} /></label>
                    </>}

                    {/* GEDUNGS */}
                    {type === "gedungs" && <>
                        <div className="text-sm md:col-span-2">
                            <SmartSelect
                                label="Cabang"
                                value={form.data.cabang_id ?? ""}
                                onChange={(value) => form.setData("cabang_id", value)}
                                options={cabangs}
                                placeholder="Pilih cabang"
                                required
                                getOptionValue={(c) => c.id}
                                getOptionLabel={(c) => c.nama_cabang}
                                getOptionDescription={(c) => c.kode ? `Kode ${c.kode}` : ""}
                            />
                        </div>
                        <label className="block text-sm md:col-span-2">Nama Gedung<input className="input mt-1" value={form.data.nama_gedung} onChange={(e) => form.setData("nama_gedung", e.target.value)} placeholder="Contoh: Gedung Utama" required /></label>
                    </>}

                    {/* LANT AIS */}
                    {type === "lantais" && <>
                        <div className="text-sm md:col-span-2">
                            <SmartSelect
                                label="Gedung"
                                value={selectedLantaiGedungId}
                                onChange={(value) => {
                                    setSelectedLantaiGedungId(value);
                                    form.setData({ ...form.data, gedung_id: value });
                                }}
                                options={scopedGedungs}
                                placeholder="Pilih gedung"
                                required
                                getOptionValue={(g) => g.id}
                                getOptionLabel={(g) => g.nama_gedung}
                                getOptionDescription={(g) => g.cabang?.nama_cabang ?? "Gedung"}
                            />
                        </div>
                        <label className="block text-sm">Jenis Lantai<input className="input mt-1" value={form.data.nama_lantai ?? ""} onChange={(e) => form.setData("nama_lantai", e.target.value)} placeholder="Contoh: Basement, Lantai 1" required /></label>
                        <label className="block text-sm">No Lantai<input type="number" min="0" className="input mt-1" value={form.data.nomor_lantai ?? ""} onChange={(e) => form.setData("nomor_lantai", e.target.value)} placeholder="0" required /><span className="mt-1 block text-xs text-slate-500">Basement = 0, Lantai 1 = 1</span></label>
                    </>}

                    {/* RUANGS */}
                    {type === "ruangs" && <>
                        <div className="text-sm md:col-span-2">
                            <SmartSelect
                                label="Lantai"
                                value={form.data.lantai_id ?? ""}
                                onChange={(value) => {
                                    const selectedLantai = lantais.find((l) => String(l.id) === String(value));
                                    setSelectedGedungId(selectedLantai ? String(selectedLantai.gedung_id ?? selectedLantai.gedung?.id ?? "") : "");
                                    form.setData({ ...form.data, lantai_id: value, gedung_id: selectedLantai?.gedung_id ?? selectedLantai?.gedung?.id ?? "" });
                                }}
                                options={lantais}
                                placeholder="Pilih lantai"
                                required
                                getOptionValue={(l) => l.id}
                                getOptionLabel={(l) => formatLantai(l)}
                                getOptionDescription={(l) => l.gedung?.nama_gedung ?? "Lantai"}
                            />
                        </div>
                        <label className="block text-sm">Nama Ruang<input className="input mt-1" value={form.data.nama_ruang ?? ""} onChange={(e) => form.setData("nama_ruang", e.target.value)} placeholder="Contoh: Ruang Lab, Toilet" required /></label>
                        <label className="block text-sm">No Ruang<input className="input mt-1 uppercase" value={form.data.kode_ruang ?? ""} onChange={(e) => form.setData("kode_ruang", e.target.value.toUpperCase())} placeholder="Contoh: 101, A-101" /></label>
                    </>}

                    {/* KATEGORIS */}
                    {type === "kategoris" && (
                        <>
                            <label className="block text-sm md:col-span-2">Nama Kategori<input className="input mt-1" value={form.data.nama_kategori} onChange={(e) => form.setData("nama_kategori", e.target.value)} required /></label>
                            <label className="block text-sm md:col-span-2">Keterangan<textarea className="input mt-1" rows="2" value={form.data.keterangan ?? ""} onChange={(e) => form.setData("keterangan", e.target.value)} placeholder="Jelaskan deskripsi atau penjelasan singkat tentang kategori ini..." /></label>
                            <div className="block text-sm md:col-span-2">
                                <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Petugas yang Sesuai (Opsional)</label>
                                <div className="mb-2 flex flex-wrap gap-1.5">
                                    {(form.data.role_relations ?? []).map((relation, index) => (
                                        <span key={`${relation.role_id}-${relation.role_category_id || 'all'}-${index}`} className="inline-flex items-center gap-1 rounded-full bg-[#6870fa]/15 px-2.5 py-1 text-xs font-semibold text-[#6870fa]">
                                            {kategoriRoleLabel(relation)}
                                            <button type="button" onClick={() => form.setData("role_relations", (form.data.role_relations ?? []).filter((_, i) => i !== index))} className="ml-0.5 rounded-full hover:bg-[#6870fa]/30"><X size={10} /></button>
                                        </span>
                                    ))}
                                </div>
                                <SmartSelect
                                    value=""
                                    onChange={(val, option) => {
                                        if (!option) return;
                                        const current = form.data.role_relations ?? [];
                                        const exists = current.some((relation) => String(relation.role_id) === String(option.role_id) && String(relation.role_category_id || '') === String(option.role_category_id || ''));
                                        if (!exists) {
                                            form.setData("role_relations", [...current, {
                                                role_id: String(option.role_id),
                                                role_category_id: option.role_category_id ? String(option.role_category_id) : '',
                                                role: roles.find((role) => String(role.id) === String(option.role_id)),
                                                role_category: roleCategories.find((category) => String(category.id) === String(option.role_category_id)),
                                            }]);
                                        }
                                    }}
                                    options={kategoriRelationOptions.filter((option) => !(form.data.role_relations ?? []).some((relation) => String(relation.role_id) === String(option.role_id) && String(relation.role_category_id || '') === String(option.role_category_id || '')))}
                                    placeholder="Cari dan pilih role/subkategori yang sesuai..."
                                    getOptionValue={(option) => option.key}
                                    getOptionLabel={(option) => option.label}
                                    getOptionDescription={(option) => option.description}
                                />
                                <p className="mt-1 text-xs text-slate-500">Pilih role utama atau subkategori role yang cocok menangani kategori ini. Ini digunakan sebagai rekomendasi, bukan penugasan otomatis.</p>
                            </div>
                        </>
                    )}

                    {/* JENIS IDENTITAS */}
                    {type === "jenis_identitas" && (
                        <>
                            <label className="block text-sm">Nama Jenis Identitas<input className="input mt-1" value={form.data.nama_jenis} onChange={(e) => form.setData("nama_jenis", e.target.value)} placeholder="Contoh: NIK Karyawan" required /></label>
                            <label className="block text-sm">Kode<input className="input mt-1 uppercase" value={form.data.kode} onChange={(e) => form.setData("kode", e.target.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '').slice(0, 30))} placeholder="Contoh: NIK_KARYAWAN" required /><span className="mt-1 block text-xs text-slate-500">Kode unik, contoh: NIK_KARYAWAN, NO_PEGAWAI</span></label>
                            <label className="block text-sm md:col-span-2">Keterangan<textarea className="input mt-1" rows="2" value={form.data.keterangan ?? ""} onChange={(e) => form.setData("keterangan", e.target.value)} placeholder="Deskripsi singkat tentang jenis identitas ini..." /></label>
                        </>
                    )}

                    {/* STATUS */}
                    <div className="text-sm"><SmartSelect label="Status" value={form.data.status} onChange={(value) => form.setData("status", value)} options={[{ value: "active", label: "Aktif" }, { value: "inactive", label: "Nonaktif" }]} placeholder="Pilih status" /></div>
                </div>

                {Object.keys(form.errors).length > 0 && <div className="mt-4 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-600 dark:text-red-200">Periksa kembali field yang wajib diisi.</div>}
                <div className="mt-5 flex justify-end gap-2">
                    <button type="button" onClick={onClose} className="btn-light">Tutup</button>
                    <button className="btn-primary" disabled={form.processing}>
                        {form.processing ? "Menyimpan..." : (item ? "Simpan" : "Simpan & Tambah Lagi")}
                    </button>
                </div>
            </form>
        </div>
    );
}

function DetailModal({ type, item, onClose }) {
    if (!item) return null;
    return (
        <div className="fixed inset-0 z-50 grid place-items-center bg-black/70 p-4 backdrop-blur-sm">
            <div className="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 text-slate-800 shadow-2xl dark:border-[#29314b] dark:bg-[#1F2A40] dark:text-[#e0e0e0]">
                <div className="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-black">Detail {tabs.find((tab) => tab.key === type)?.label}</h2>
                        <p className="text-sm text-slate-500">{nameOf(item, type)}</p>
                    </div>
                    <button type="button" onClick={onClose} className="rounded-xl border border-slate-200 p-2 hover:border-[#6870fa] dark:border-[#29314b]"><X size={18} /></button>
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                    <Info label="Nama" value={nameOf(item, type)} />
                    {type === "jenis_identitas" ? (
                        <Info label="Kode" value={item.kode || "-"} />
                    ) : type !== "kategoris" ? (
                        <Info label={type === "cabangs" ? "Kode" : "Relasi"} value={relationOf(item, type)} />
                    ) : null}
                    {type === "jenis_identitas" && item.keterangan ? (
                        <div className="md:col-span-2"><Info label="Keterangan" value={item.keterangan} /></div>
                    ) : null}
                    {type === "kategoris" && item.keterangan && <Info label="Keterangan" value={item.keterangan} />}
                    {type === "kategoris" && (
                        <div className="md:col-span-2">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Petugas yang Sesuai</p>
                            <div className="mt-1 flex flex-wrap gap-1.5">
                                {(item.roleRelations ?? []).length > 0 ? item.roleRelations.map((relation) => (
                                    <span key={relation.id} className="inline-flex items-center rounded-full bg-[#6870fa]/15 px-2.5 py-1 text-xs font-semibold text-[#6870fa]">{kategoriRoleLabel(relation)}</span>
                                )) : <span className="text-sm text-slate-500">Belum diatur</span>}
                            </div>
                        </div>
                    )}
                    <Info label="Status" value={item.status === "active" ? "Aktif" : "Nonaktif"} />
                </div>
                {item.created_at && <div className="mt-4 text-xs text-slate-500">Dibuat: {new Date(item.created_at).toLocaleString('id-ID')}</div>}
            </div>
        </div>
    );
}

// Cascade filter section above table
function TableFilters({ active, cabangs, gedungs, lantais, ruangs, filters, onFilterChange }) {
    const scopedGedungs = useMemo(() => {
        if (!filters.cabang_id) return gedungs;
        return gedungs.filter((g) => String(g.cabang_id ?? g.cabang?.id ?? "") === String(filters.cabang_id));
    }, [gedungs, filters.cabang_id]);

    const scopedLantais = useMemo(() => {
        if (!filters.gedung_id) return lantais;
        return lantais.filter((l) => String(l.gedung_id ?? l.gedung?.id ?? "") === String(filters.gedung_id));
    }, [lantais, filters.gedung_id]);

    const scopedRuangs = useMemo(() => {
        if (!filters.lantai_id) return ruangs;
        return ruangs.filter((r) => String(r.lantai_id ?? r.lantai_master?.id ?? r.lantaiMaster?.id ?? "") === String(filters.lantai_id));
    }, [ruangs, filters.lantai_id]);

    const activeFilters = {
        cabangs: null,
        gedungs: <SmartSelect value={filters.cabang_id ?? ""} onChange={(v) => onFilterChange({ ...filters,cabang_id: v, gedung_id: "", lantai_id: "" })} options={[{ id: "", nama_cabang: "Semua cabang" }, ...cabangs]} placeholder="Filter cabang" getOptionValue={(x) => x.id} getOptionLabel={(x) => x.nama_cabang} getOptionDescription={(x) => x.kode ? `Kode ${x.kode}` : ""} />,
        lantais: <SmartSelect value={filters.gedung_id ?? ""} onChange={(v) => onFilterChange({ ...filters, gedung_id: v, lantai_id: "" })} options={[{ id: "", nama_gedung: "Semua gedung" }, ...scopedGedungs]} placeholder="Filter gedung" getOptionValue={(x) => x.id} getOptionLabel={(x) => x.nama_gedung} />,
        ruangs: [
            <SmartSelect key="gedung" value={filters.gedung_id ?? ""} onChange={(v) => onFilterChange({ ...filters, gedung_id: v, lantai_id: "" })} options={[{ id: "", nama_gedung: "Semua gedung" }, ...scopedGedungs]} placeholder="Filter gedung" getOptionValue={(x) => x.id} getOptionLabel={(x) => x.nama_gedung} />,
            <SmartSelect key="lantai" value={filters.lantai_id ?? ""} onChange={(v) => onFilterChange({ ...filters, lantai_id: v })} options={[{ id: "", nama_lantai: "Semua lantai" }, ...scopedLantais]} placeholder="Filter lantai" getOptionValue={(x) => x.id} getOptionLabel={(x) => formatLantai(x)} />,
        ],
        kategoris: null,
        jenis_identitas: null,
    };

    return activeFilters[active] ? (
        <div className="mb-4 flex flex-wrap gap-2">
            {Array.isArray(activeFilters[active])
                ? activeFilters[active].map((el, i) => <div key={i}>{el}</div>)
                : <div>{activeFilters[active]}</div>}
        </div>
    ) : null;
}

export default function Index({ cabangs = [], gedungs = [], lantais = [], ruangs = [], kategoris = [], roles = [], roleCategories = [], jenisIdentitas = [], permissions = {} }) {
    const [active, setActive] = useState("cabangs");
    const [editing, setEditing] = useState(null);
    const [detail, setDetail] = useState(null);
    const [search, setSearch] = useState("");
    const [statusFilter, setStatusFilter] = useState("");
    const [sortDir, setSortDir] = useState("desc");
    const [tableFilters, setTableFilters] = useState({});

    const data = useMemo(() => ({
        cabangs, gedungs, lantais, ruangs, kategoris,
        jenis_identitas: jenisIdentitas,
    }), [cabangs, gedungs, lantais, ruangs, kategoris, jenisIdentitas]);

    const rows = useMemo(() => {
        let result = data[active] ?? [];

        if (tableFilters.cabang_id && active === "gedungs") {
            result = result.filter((r) => String(r.cabang_id ?? r.cabang?.id ?? "") === String(tableFilters.cabang_id));
        }
        if (tableFilters.gedung_id && active === "lantais") {
            result = result.filter((r) => String(r.gedung_id ?? r.gedung?.id ?? "") === String(tableFilters.gedung_id));
        }
        if (tableFilters.gedung_id && active === "ruangs") {
            result = result.filter((r) => {
                const lt = r.lantai_master ?? r.lantaiMaster ?? {};
                return String(lt.gedung_id ?? lt.gedung?.id ?? "") === String(tableFilters.gedung_id);
            });
        }
        if (tableFilters.lantai_id && active === "ruangs") {
            result = result.filter((r) => {
                const lt = r.lantai_master ?? r.lantaiMaster ?? {};
                return String(lt.id ?? "") === String(tableFilters.lantai_id);
            });
        }

        return result
            .filter((row) => JSON.stringify(row).toLowerCase().includes(search.toLowerCase()))
            .filter((row) => !statusFilter || row.status === statusFilter)
            .sort((a, b) => {
                const left = new Date(a.created_at ?? 0).getTime() || Number(a.id ?? 0);
                const right = new Date(b.created_at ?? 0).getTime() || Number(b.id ?? 0);
                return sortDir === "desc" ? right - left : left - right;
            });
    }, [data, active, search, statusFilter, sortDir, tableFilters]);

    const clearTableFilters = () => setTableFilters({});

    return (
        <AppLayout title="Master Data">
            <p className="mb-6 text-sm text-slate-500">Kelola data master lokasi secara bertingkat: cabang → gedung → lantai → ruang. Serta kategori pekerjaan.</p>
            <div className="page-card">
                <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap gap-2">
                        {tabs.map((tab) => <button key={tab.key} onClick={() => { setActive(tab.key); setSearch(""); setStatusFilter(""); setTableFilters({}); }} className={`rounded-xl px-4 py-2 text-sm font-semibold ${active === tab.key ? "bg-[#6870fa] text-white" : "bg-slate-100 text-slate-600 hover:text-slate-900 dark:bg-[#29314b] dark:text-[#a3a3a3] dark:hover:text-white"}`}>{tab.label}</button>)}
                    </div>
                    {permissions["master_data.create"] && <button className="btn-primary" onClick={() => setEditing({ type: active, item: null })}><Plus size={16} className="mr-2" />Tambah</button>}
                </div>

                <div className="mb-5 space-y-3">
                    <div className="grid gap-3 md:grid-cols-[1fr_auto]">
                        <div className="relative"><Search className="absolute left-3 top-3 h-4 w-4 text-slate-400" /><input className="input pl-9" value={search} onChange={(e) => setSearch(e.target.value)} placeholder={`Cari ${tabs.find(t => t.key === active)?.label?.toLowerCase() ?? 'data'}...`} type="search" /></div>
                        <button className="btn-light justify-center" type="button" onClick={() => { setSearch(""); setStatusFilter(""); setTableFilters({}); }}><XCircle size={16} className="mr-2" />Reset</button>
                    </div>

                    <TableFilters
                        active={active}
                        cabangs={cabangs}
                        gedungs={gedungs}
                        lantais={lantais}
                        ruangs={ruangs}
                        filters={tableFilters}
                        onFilterChange={setTableFilters}
                    />

                    <div className="grid gap-3 md:grid-cols-[180px_auto]">
                        <SmartSelect value={statusFilter} onChange={(value) => setStatusFilter(value)} options={[{ value: "", label: "Semua status" }, { value: "active", label: "Aktif" }, { value: "inactive", label: "Nonaktif" }]} placeholder="Semua status" />
                        <button className="btn-light justify-center" type="button" onClick={() => setSortDir(sortDir === "desc" ? "asc" : "desc")}><ArrowDownUp size={16} className="mr-2" />{sortDir === "desc" ? "Terbaru" : "Terlama"}</button>
                    </div>
                </div>

                {rows.length === 0 ? <EmptyState /> : (
                    <div className="table-shell">
                        <table className={`data-table min-w-[900px] ${active === 'ruangs' || active === 'kategoris' || active === 'jenis_identitas' ? 'min-w-[1100px]' : ''} table-fixed`}>
                            <thead>
                                {active === 'ruangs' ? (
                                    <tr>
                                        <th>Nama Ruang</th>
                                        <th className="w-32">No Ruang</th>
                                        <th className="w-64">Lantai</th>
                                        <th className="w-28 text-center">Status</th>
                                        <th className="w-36 text-right">Aksi</th>
                                    </tr>
                                ) : active === 'kategoris' ? (
                                    <tr>
                                        <th>Nama Kategori</th>
                                        <th className="w-64">Keterangan</th>
                                        <th className="w-72">Petugas yang Sesuai</th>
                                        <th className="w-28 text-center">Status</th>
                                        <th className="w-36 text-right">Aksi</th>
                                    </tr>
                                ) : active === 'jenis_identitas' ? (
                                    <tr>
                                        <th>Nama Jenis</th>
                                        <th className="w-40">Kode</th>
                                        <th className="w-64">Keterangan</th>
                                        <th className="w-28 text-center">Status</th>
                                        <th className="w-36 text-right">Aksi</th>
                                    </tr>
                                ) : (
                                    <tr>
                                        <th>Nama</th>
                                        <th className="w-64">Relasi</th>
                                        <th className="w-28 text-center">Status</th>
                                        <th className="w-36 text-right">Aksi</th>
                                    </tr>
                                )}
                            </thead>
                            <tbody>
                                {rows.map((row) => (
                                    <tr key={row.id}>
                                        {active === 'ruangs' ? (
                                            <>
                                                <td className="font-semibold"><div className="truncate" title={row.nama_ruang}>{row.nama_ruang}</div></td>
                                                <td className="text-center font-mono text-sm"><div className="truncate" title={row.kode_ruang}>{row.kode_ruang ?? '-'}</div></td>
                                                <td><div className="truncate" title={relationOf(row, active)}>{relationOf(row, active)}</div></td>
                                                <td className="text-center"><StatusPill value={row.status} /></td>
                                                <td className="text-right">
                                                    <div className="table-actions">
                                                        <button className="icon-btn" onClick={() => setDetail({ type: active, item: row })} title="Detail"><Eye size={14} /></button>
                                                        {permissions["master_data.edit"] && <button className="icon-btn" onClick={() => setEditing({ type: active, item: row })} title="Edit"><Edit3 size={14} /></button>}
                                                        {permissions["master_data.delete"] && <button className="icon-btn-danger" onClick={() => confirm("Hapus data ini?") && router.delete(`/master-data/${active}/${row.id}`, { preserveScroll: true })} title="Hapus"><Trash2 size={14} /></button>}
                                                    </div>
                                                </td>
                                            </>
                                        ) : active === 'kategoris' ? (
                                            <>
                                                <td className="font-semibold"><div className="truncate" title={row.nama_kategori}>{row.nama_kategori}</div></td>
                                                <td><div className="truncate" title={row.keterangan}>{row.keterangan || '-'}</div></td>
                                                <td>
                                                    <div className="flex flex-wrap gap-1">
                                                        {(row.roleRelations ?? []).length > 0 ? row.roleRelations.map((relation) => (
                                                            <span key={relation.id} className="inline-flex items-center rounded-full bg-[#6870fa]/15 px-2 py-0.5 text-xs font-semibold text-[#6870fa]">{kategoriRoleLabel(relation)}</span>
                                                        )) : <span className="text-xs text-slate-500">Belum diatur</span>}
                                                    </div>
                                                </td>
                                                <td className="text-center"><StatusPill value={row.status} /></td>
                                                <td className="text-right">
                                                    <div className="table-actions">
                                                        <button className="icon-btn" onClick={() => setDetail({ type: active, item: row })} title="Detail"><Eye size={14} /></button>
                                                        {permissions["master_data.edit"] && <button className="icon-btn" onClick={() => setEditing({ type: active, item: row })} title="Edit"><Edit3 size={14} /></button>}
                                                        {permissions["master_data.delete"] && <button className="icon-btn-danger" onClick={() => confirm("Hapus data ini?") && router.delete(`/master-data/${active}/${row.id}`, { preserveScroll: true })} title="Hapus"><Trash2 size={14} /></button>}
                                                    </div>
                                                </td>
                                            </>
                                        ) : active === 'jenis_identitas' ? (
                                            <>
                                                <td className="font-semibold"><div className="truncate" title={row.nama_jenis}>{row.nama_jenis}</div></td>
                                                <td className="font-mono text-sm"><div className="truncate" title={row.kode}>{row.kode || '-'}</div></td>
                                                <td><div className="truncate" title={row.keterangan}>{row.keterangan || '-'}</div></td>
                                                <td className="text-center"><StatusPill value={row.status} /></td>
                                                <td className="text-right">
                                                    <div className="table-actions">
                                                        <button className="icon-btn" onClick={() => setDetail({ type: active, item: row })} title="Detail"><Eye size={14} /></button>
                                                        {permissions["master_data.edit"] && <button className="icon-btn" onClick={() => setEditing({ type: active, item: row })} title="Edit"><Edit3 size={14} /></button>}
                                                        {permissions["master_data.delete"] && <button className="icon-btn-danger" onClick={() => confirm("Hapus data ini?") && router.delete(`/master-data/${active}/${row.id}`, { preserveScroll: true })} title="Hapus"><Trash2 size={14} /></button>}
                                                    </div>
                                                </td>
                                            </>
                                        ) : (
                                            <>
                                                <td className="font-semibold"><div className="truncate" title={row.name ?? row.nama_cabang ?? row.nama_gedung ?? row.nama_lantai ?? '-'}>{row.name ?? row.nama_cabang ?? row.nama_gedung ?? row.nama_lantai ?? '-'}</div></td>
                                                <td><div className="truncate" title={relationOf(row, active)}>{relationOf(row, active)}</div></td>
                                                <td className="text-center"><StatusPill value={row.status} /></td>
                                                <td className="text-right">
                                                    <div className="table-actions">
                                                        <button className="icon-btn" onClick={() => setDetail({ type: active, item: row })} title="Detail"><Eye size={14} /></button>
                                                        {permissions["master_data.edit"] && <button className="icon-btn" onClick={() => setEditing({ type: active, item: row })} title="Edit"><Edit3 size={14} /></button>}
                                                        {permissions["master_data.delete"] && <button className="icon-btn-danger" onClick={() => confirm("Hapus data ini?") && router.delete(`/master-data/${active}/${row.id}`, { preserveScroll: true })} title="Hapus"><Trash2 size={14} /></button>}
                                                    </div>
                                                </td>
                                            </>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {editing && (
                <QuickAddForm
                    type={editing.type}
                    item={editing.item}
                    cabangs={cabangs}
                    gedungs={gedungs}
                    lantais={lantais}
                    roles={roles ?? []}
                    roleCategories={roleCategories ?? []}
                    onClose={() => setEditing(null)}
                />
            )}
            {detail && <DetailModal type={detail.type} item={detail.item} onClose={() => setDetail(null)} />}
        </AppLayout>
    );
}