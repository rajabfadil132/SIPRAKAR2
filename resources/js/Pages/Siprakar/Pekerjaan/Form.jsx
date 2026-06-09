import AuditInfo from "@/Components/AuditInfo";
import LocationAutocomplete from "@/Components/LocationAutocomplete";
import SearchableDropdown from "@/Components/SearchableDropdown";
import SmartSelect from "@/Components/SmartSelect";
import AppLayout from "@/Layouts/AppLayout";
import { Link, useForm } from "@inertiajs/react";
import { Plus, Trash2 } from "lucide-react";
import { useEffect, useMemo, useState } from "react";

const dateValue = (value) => value?.slice?.(0, 10) ?? "";
const formatNumber = (value) => String(value ?? "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
const onlyDigits = (value) => String(value ?? "").replace(/[^0-9]/g, "");
const formatRupiah = (value) => {
    const num = parseFloat(value ?? 0);
    return num.toLocaleString("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0, maximumFractionDigits: 0 });
};
const defaultChecklist = ["Survey lokasi dan kebutuhan", "Persiapan alat/material", "Pelaksanaan pekerjaan", "Pemeriksaan hasil pekerjaan"];

export default function Form({ item, programs = [], selectedProgramId = "", cabangs = [], gedungs = [], lantais = [], ruangs = [], kategoris = [], users = [], roles = [] }) {
    const isEdit = Boolean(item?.id);
    const existingChecklist = (item?.checklists ?? []).map((row) => row.deskripsi);
    const existingAssignees = useMemo(() => {
        const rows = (item?.petugas_tambahan ?? item?.petugasTambahan ?? []).map((assignment) => ({
            role_text: assignment.role_text ?? assignment.user?.role_category?.name ?? assignment.user?.roleCategory?.name ?? assignment.user?.role?.nama_role ?? "",
            user_id: assignment.user_id ?? "",
            nama_petugas_manual: assignment.nama_petugas_manual ?? assignment.user?.name ?? "",
        }));
        if (rows.length === 0 && item?.petugas) {
            rows.push({ role_text: item.petugas.role_category?.name ?? item.petugas.roleCategory?.name ?? item.petugas.role?.nama_role ?? "", user_id: item.petugas.id, nama_petugas_manual: item.petugas.name ?? "" });
        }
        return rows;
    }, [item]);

    const form = useForm({
        program_kerja_id: item?.program_kerja_id ?? selectedProgramId ?? "",
        nama_pekerjaan: item?.nama_pekerjaan ?? "",
        deskripsi: item?.deskripsi ?? "",
        cabang_id: item?.cabang_id ?? "",
        lokasi_id: item?.lokasi_id ?? "",
        nama_gedung: item?.nama_gedung ?? item?.lokasi?.nama_gedung ?? "",
        nama_lantai: item?.nama_lantai ?? item?.lokasi?.lantai_master?.nama_lantai ?? (item?.lantai !== undefined && item?.lantai !== null && item?.lantai !== "" ? `Lantai ${item.lantai}` : ""),
        nama_ruang: item?.nama_ruang ?? item?.lokasi?.nama_ruang ?? "",
        lantai: item?.lantai ?? item?.lokasi?.lantai ?? "",
        no_ruang: item?.no_ruang ?? item?.lokasi?.kode_ruang ?? "",
        location_text: item?.location_text ?? "",
        kategori_id: item?.kategori_id ?? "",
        prioritas: item?.prioritas ?? "Sedang",
        penanggung_jawab_id: item?.penanggung_jawab_id ?? "",
        petugas_id: item?.petugas_id ?? "",
        tanggal_mulai: dateValue(item?.tanggal_mulai),
        target_selesai: dateValue(item?.target_selesai),
        tanggal_selesai: dateValue(item?.tanggal_selesai),
        status: item?.status ?? "Belum Diproses",
        catatan: item?.catatan ?? "",
        checklists: existingChecklist.length ? existingChecklist : defaultChecklist,
        assignees: existingAssignees,
    });

    const selectedProgram = useMemo(() => programs.find((program) => String(program.id) === String(form.data.program_kerja_id)), [programs, form.data.program_kerja_id]);

    const selectedKategori = useMemo(() => kategoris.find((k) => String(k.id) === String(form.data.kategori_id)), [kategoris, form.data.kategori_id]);

    const filteredUsers = useMemo(() => {
        if (!selectedKategori?.roleCategories?.length) return users;
        const allowedIds = selectedKategori.roleCategories.map((rc) => rc.id);
        return users.filter((user) => allowedIds.includes(user.role_category_id));
    }, [users, selectedKategori]);

    const rabInfo = useMemo(() => {
        if (isEdit && item?.rab) {
            return { hasRab: true, rab: item.rab };
        }
        if (selectedProgram?.rab) {
            return { hasRab: true, rab: selectedProgram.rab };
        }
        return { hasRab: false, rab: null };
    }, [isEdit, item, selectedProgram]);

    useEffect(() => {
        if (!selectedProgram) return;
        if (isEdit && String(item?.program_kerja_id ?? "") === String(selectedProgram.id)) return;

        form.setData({
            ...form.data,
            nama_pekerjaan: selectedProgram.nama_program ?? form.data.nama_pekerjaan,
            deskripsi: selectedProgram.deskripsi ?? form.data.deskripsi,
            cabang_id: selectedProgram.cabang_id ?? form.data.cabang_id,
            kategori_id: selectedProgram.kategori_id ?? form.data.kategori_id,
            prioritas: selectedProgram.prioritas ?? form.data.prioritas,
            tanggal_mulai: dateValue(selectedProgram.target_mulai) || form.data.tanggal_mulai,
            target_selesai: dateValue(selectedProgram.target_selesai) || form.data.target_selesai,
            lokasi_id: selectedProgram.lokasi_id ?? form.data.lokasi_id,
            nama_gedung: selectedProgram.nama_gedung ?? form.data.nama_gedung,
            nama_lantai: selectedProgram.nama_lantai ?? form.data.nama_lantai,
            nama_ruang: selectedProgram.nama_ruang ?? form.data.nama_ruang,
            lantai: selectedProgram.lantai ?? form.data.lantai,
            no_ruang: selectedProgram.no_ruang ?? form.data.no_ruang,
            location_text: selectedProgram.location_text ?? form.data.location_text,
        });
    }, [selectedProgram?.id]);

    const roleOptions = useMemo(() => Array.from(new Set([
        ...roles.map((role) => role.nama_role),
        ...roles.flatMap((role) => (role.active_categories ?? role.activeCategories ?? []).map((category) => category.name)),
        ...users.map((user) => user.role_category?.name || user.roleCategory?.name || user.role?.nama_role),
    ].filter(Boolean))).sort(), [roles, users]);
    const pekerjaanStatusOptions = useMemo(() => {
        const options = ["Belum Diproses", "Diproses", "Dibatalkan"];
        return item?.status === "Selesai" ? [...options, { value: "Selesai", label: "Selesai (otomatis dari checklist)" }] : options;
    }, [item]);
    const [assignRole, setAssignRole] = useState("");
    const [assignUser, setAssignUser] = useState("");

    const setChecklist = (index, value) => {
        const next = [...form.data.checklists];
        next[index] = value;
        form.setData("checklists", next);
    };

    const addChecklist = () => form.setData("checklists", [...form.data.checklists, ""]);
    const removeChecklist = (index) => form.setData("checklists", form.data.checklists.filter((_, i) => i !== index));

    const addAssignee = () => {
        const typedName = assignUser.trim();
        const matchedUser = users.find((user) => user.name.toLowerCase() === typedName.toLowerCase());
        const roleText = assignRole.trim() || matchedUser?.role_category?.name || matchedUser?.roleCategory?.name || matchedUser?.role?.nama_role || "";
        if (!typedName && !roleText) return;

        const next = [...form.data.assignees, {
            role_text: roleText,
            user_id: matchedUser?.id ?? "",
            nama_petugas_manual: matchedUser?.name ?? typedName,
        }];
        form.setData("assignees", next);
        setAssignRole("");
        setAssignUser("");
    };
    const removeAssignee = (index) => form.setData("assignees", form.data.assignees.filter((_, i) => i !== index));

    const submit = (e) => {
        e.preventDefault();
        isEdit ? form.put(`/pekerjaan/${item.id}`) : form.post("/pekerjaan");
    };

    return (
        <AppLayout title={isEdit ? "Edit Pekerjaan" : "Tambah Pekerjaan"}>
            <form onSubmit={submit} className="space-y-6">
                <div className="page-card grid gap-4 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <SmartSelect label="Ambil dari Program Kerja" value={form.data.program_kerja_id} onChange={(value) => form.setData("program_kerja_id", value)} options={programs} placeholder="Pilih program kerja yang tersedia" required getOptionValue={(x) => x.id} getOptionLabel={(x) => `${x.kode_program} - ${x.nama_program}`} getOptionDescription={(x) => [x.source_type || "PROKER", x.cabang?.nama_cabang, x.kategori?.nama_kategori, x.prioritas, x.needs_rab ? `RAB ${x.rab?.status_rab || "belum dibuat"}` : "Tanpa RAB"].filter(Boolean).join(" · ") || "Program kerja tersedia"} />
                        <span className="mt-1 block text-xs text-slate-500">Program yang dipilih akan otomatis dipindahkan ke Data Pekerjaan dan tidak muncul lagi di daftar Program Kerja.</span>
                        {form.errors.program_kerja_id && <Error>{form.errors.program_kerja_id}</Error>}
                    </div>
                    <label className="md:col-span-2">Nama Pekerjaan<input className="input mt-1" value={form.data.nama_pekerjaan} onChange={(e) => form.setData("nama_pekerjaan", e.target.value)} required />{form.errors.nama_pekerjaan && <Error>{form.errors.nama_pekerjaan}</Error>}</label>
                    <SmartSelect label="Kategori" value={form.data.kategori_id} onChange={(value) => form.setData("kategori_id", value)} options={kategoris} placeholder="Pilih kategori" getOptionValue={(x) => x.id} getOptionLabel={(x) => x.nama_kategori} getOptionDescription={(x) => x.keterangan || ""} />
                    {selectedKategori?.roleCategories?.length ? <span className="mt-1 block text-xs text-slate-500">Petugas & penanggung jawab difilter sesuai role yang sesuai dengan kategori ini.</span> : null}
                    <SmartSelect label="Prioritas" value={form.data.prioritas} onChange={(value) => form.setData("prioritas", value)} options={["Rendah", "Sedang", "Tinggi", "Mendesak"]} placeholder="Pilih prioritas" />
                    <SmartSelect label="Penanggung Jawab" value={form.data.penanggung_jawab_id} onChange={(value) => form.setData("penanggung_jawab_id", value)} options={filteredUsers} placeholder="Pilih penanggung jawab" getOptionValue={(x) => x.id} getOptionLabel={(x) => x.name} getOptionDescription={(x) => [x.role?.nama_role, x.role_category?.name || x.roleCategory?.name, x.email].filter(Boolean).join(' · ') || 'User'} />
                    <label>Tanggal Mulai<input type="date" className="input mt-1" value={form.data.tanggal_mulai} onChange={(e) => form.setData("tanggal_mulai", e.target.value)} /></label>
                    <label>Target Selesai<input type="date" className="input mt-1" value={form.data.target_selesai} onChange={(e) => form.setData("target_selesai", e.target.value)} /></label>
                    <div><SmartSelect label="Status" value={form.data.status} onChange={(value) => form.setData("status", value)} options={pekerjaanStatusOptions} placeholder="Pilih status" /><span className="mt-1 block text-xs text-slate-500">Status Selesai tidak dipilih manual. Jika semua checklist selesai, progress menjadi 100% dan status otomatis berubah menjadi Selesai.</span></div>
                    <div className="md:col-span-2">
                        <h3 className="mb-2 font-bold text-slate-300">Anggaran Biaya / RAB</h3>
                        {rabInfo.hasRab && rabInfo.rab ? (
                            <div className="rounded-xl border border-[#29314b] bg-[#141b2d] p-4">
                                <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                                    <div className="flex flex-wrap items-center gap-3">
                                        <span className="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-bold text-emerald-400">Disetujui</span>
                                        <span className="text-sm text-slate-400">No. RAB: <span className="font-mono font-semibold text-slate-200">{rabInfo.rab.nomor_rab || '-'}</span></span>
                                    </div>
                                    <span className="text-sm font-bold text-emerald-400">{formatRupiah(rabInfo.rab.total_rab)}</span>
                                </div>
                                {(rabInfo.rab.details ?? []).length > 0 ? (
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="border-b border-[#29314b] text-slate-500">
                                                <th className="pb-2 pr-3 font-medium">Nama Item</th>
                                                <th className="w-16 pb-2 pr-3 text-right font-medium">Jumlah</th>
                                                <th className="w-32 pb-2 pr-3 text-right font-medium">Harga Satuan</th>
                                                <th className="w-32 pb-2 pr-3 text-right font-medium">Subtotal</th>
                                                <th className="w-40 pb-2 font-medium">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {(rabInfo.rab.details ?? []).map((item) => (
                                                <tr key={item.id} className="border-b border-dashed border-[#29314b]/50 last:border-0">
                                                    <td className="py-2 pr-3 font-medium text-slate-200">{item.nama_item}</td>
                                                    <td className="py-2 pr-3 text-right font-mono text-slate-300">{formatNumber(item.jumlah_item)}</td>
                                                    <td className="py-2 pr-3 text-right font-mono text-slate-300">{formatRupiah(item.harga_satuan)}</td>
                                                    <td className="py-2 pr-3 text-right font-mono font-semibold text-slate-200">{formatRupiah(item.subtotal)}</td>
                                                    <td className="py-2 text-slate-400">{item.keterangan || '-'}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                ) : (
                                    <p className="text-sm text-slate-500">Tidak ada detail item RAB.</p>
                                )}
                            </div>
                        ) : (
                            <div className="rounded-xl border border-dashed border-[#29314b] bg-[#141b2d] px-4 py-3">
                                <span className="text-sm text-slate-500">Tanpa RAB — pekerjaan ini tidak menggunakan rincian anggaran.</span>
                            </div>
                        )}
                    </div>
                    <label className="md:col-span-2">Deskripsi<textarea className="input mt-1" rows="4" value={form.data.deskripsi} onChange={(e) => form.setData("deskripsi", e.target.value)} /></label>
                    <label className="md:col-span-2">Catatan<textarea className="input mt-1" rows="3" value={form.data.catatan} onChange={(e) => form.setData("catatan", e.target.value)} /></label>
                    <div className="md:col-span-2">
                        <div className="mb-3">
                            <h2 className="font-bold">Lokasi Pekerjaan</h2>
                            <p className="text-sm text-slate-500">Ketik lokasi secara manual atau pilih saran dari master data gedung, lantai, ruang, dan no ruang.</p>
                        </div>
                        <LocationAutocomplete form={form} cabangs={cabangs} gedungs={gedungs} lantais={lantais} ruangs={ruangs} cabangPlaceholder="Ikuti user login" />
                    </div>
                </div>

                <div className="page-card">
                    <div className="mb-4">
                        <h2 className="font-bold">Tugaskan</h2>
                        <p className="text-sm text-slate-500">Tambahkan satu atau beberapa petugas. Role dan user bisa diketik manual atau dipilih dari dropdown saran.</p>
                    </div>
                    <div className="grid gap-3 md:grid-cols-[220px_1fr_auto]">
                        <SearchableDropdown
                            label="Role"
                            value={assignRole}
                            onChange={(value) => setAssignRole(value)}
                            options={roleOptions.map((role) => ({ value: role, label: role }))}
                            placeholder="Ketik/pilih role, contoh: Teknisi"
                        />
                        <SearchableDropdown
                            label="User"
                            value={assignUser}
                            onChange={(value) => {
                                setAssignUser(value);
                                const matchedUser = users.find((user) => user.name.toLowerCase() === value.trim().toLowerCase());
                                if (!assignRole) setAssignRole(matchedUser?.role_category?.name || matchedUser?.roleCategory?.name || matchedUser?.role?.nama_role || '');
                            }}
                            onSelect={(user) => {
                                setAssignUser(user.name ?? "");
                                if (!assignRole) setAssignRole(user.role_category?.name || user.roleCategory?.name || user.role?.nama_role || '');
                            }}
                            options={filteredUsers}
                            placeholder="Ketik/pilih nama user"
                            getOptionValue={(user) => user.name}
                            getOptionLabel={(user) => user.name}
                            getOptionDescription={(user) => [user.role?.nama_role, user.role_category?.name || user.roleCategory?.name, user.cabang?.nama_cabang].filter(Boolean).join(" · ")}
                        />
                        <div className="flex items-end"><button type="button" className="btn-primary w-full justify-center" onClick={addAssignee}><Plus size={16} className="mr-2" />Tambah Petugas</button></div>
                    </div>
                    <div className="mt-4 space-y-2">
                        {form.data.assignees.length === 0 && <p className="rounded-xl border border-dashed border-[#29314b] p-3 text-sm text-slate-500">Belum ada petugas yang ditambahkan.</p>}
                        {form.data.assignees.map((assignment, index) => (
                            <div key={`${assignment.user_id}-${assignment.nama_petugas_manual}-${index}`} className="flex items-center justify-between gap-3 rounded-xl border border-[#29314b] bg-[#141b2d] p-3">
                                <div className="min-w-0"><b className="block truncate">{assignment.nama_petugas_manual || users.find((user) => String(user.id) === String(assignment.user_id))?.name || "Petugas"}</b><p className="text-xs text-slate-500">Role: {assignment.role_text || "-"}</p></div>
                                <button type="button" className="icon-btn-danger" onClick={() => removeAssignee(index)} title="Hapus petugas"><Trash2 size={15} /></button>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="page-card">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 className="font-bold">Detail Pekerjaan / Checklist</h2>
                            <p className="text-sm text-slate-500">Petugas yang ditugaskan dapat membuka detail pekerjaan dan memperbarui checklist ini.</p>
                        </div>
                        <button type="button" className="btn-light" onClick={addChecklist}><Plus size={16} className="mr-2" />Tambah Detail</button>
                    </div>
                    <div className="space-y-3">
                        {form.data.checklists.map((value, index) => (
                            <div key={index} className="flex gap-2">
                                <input className="input" value={value} onChange={(e) => setChecklist(index, e.target.value)} placeholder={`Detail pekerjaan ${index + 1}`} />
                                <button type="button" className="icon-btn-danger" onClick={() => removeChecklist(index)} title="Hapus detail"><Trash2 size={15} /></button>
                            </div>
                        ))}
                    </div>
                </div>

                {Object.keys(form.errors).length > 0 && <div className="rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-200">Periksa kembali data yang wajib diisi.</div>}
                {isEdit && <div className="page-card"><h2 className="mb-4 font-bold">Riwayat</h2><AuditInfo item={item} /></div>}
                <div className="flex justify-end gap-2"><Link href={isEdit ? `/pekerjaan/${item.id}` : "/pekerjaan"} className="btn-light">Batal</Link><button className="btn-primary" disabled={form.processing}>Simpan</button></div>
            </form>
        </AppLayout>
    );
}

function Error({ children }) { return <p className="mt-1 text-xs text-red-300">{children}</p>; }
