import AuditInfo from "@/Components/AuditInfo";
import SmartSelect from "@/Components/SmartSelect";
import StatusBadge from "@/Components/StatusBadge";
import AppLayout from "@/Layouts/AppLayout";
import { Link, router, useForm } from "@inertiajs/react";
import { Edit3, ThumbsDown, ThumbsUp, Trash2, Undo2 } from "lucide-react";
import { useState } from "react";

const rupiah = (value) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value ?? 0));
const dateValue = (value) => value?.slice?.(0, 10) ?? "";
const todayValue = () => new Date().toISOString().slice(0, 10);
const blankRabItem = () => ({ nama_item: "", jumlah_item: 1, harga_satuan: 0, keterangan: "" });

export default function Form({ item, programs = [], program_kerja_id, selectedProgram = null, itemsEditable = true, permissions = {} }) {
    const isEdit = Boolean(item?.id);
    const currentStatus = item?.status_rab ?? "Diajukan";
    const details = item?.details ?? [];
    const canReviewRab = isEdit && ["Diajukan", "Direvisi"].includes(currentStatus) && Boolean(permissions["rab.edit"]);
    const [editingItem, setEditingItem] = useState(null);
    const [reviewNote, setReviewNote] = useState(item?.catatan ?? "");
    const [activeProgram, setActiveProgram] = useState(selectedProgram);

    const form = useForm({
        program_kerja_id: item?.program_kerja_id ?? program_kerja_id ?? "",
        tanggal_rab: dateValue(item?.tanggal_rab) || todayValue(),
        catatan: item?.catatan ?? "",
    });
    const itemForm = useForm(blankRabItem());

    const estimasi = Number(activeProgram?.estimasi_total ?? item?.programKerja?.estimasi_total ?? 0);
    const estimasiItems = activeProgram?.estimasiItems ?? item?.programKerja?.estimasiItems ?? [];

    const submit = (e) => {
        e.preventDefault();
        isEdit ? form.put(`/rab/${item.id}`, { preserveScroll: true }) : form.post("/rab");
    };
    const resetItem = () => {
        setEditingItem(null);
        itemForm.setData(blankRabItem());
    };
    const saveItem = (e) => {
        e.preventDefault();
        if (!itemsEditable) return;
        const options = { preserveScroll: true, onSuccess: resetItem };
        editingItem ? itemForm.put(`/rab-items/${editingItem.id}`, options) : itemForm.post(`/rab/${item.id}/items`, options);
    };
    const editItem = (detail) => {
        if (!itemsEditable) return;
        setEditingItem(detail);
        itemForm.setData({
            nama_item: detail.nama_item ?? "",
            jumlah_item: detail.jumlah_item ?? 1,
            harga_satuan: detail.harga_satuan ?? 0,
            keterangan: detail.keterangan ?? "",
        });
    };
    const doAction = (action) => {
        const messages = { approve: "Setujui RAB ini? Program Kerja dapat dijadikan Data Pekerjaan.", revise: "Tandai RAB ini perlu revisi? Item tetap bisa diedit.", reject: "Tolak RAB ini?" };
        if (!confirm(messages[action])) return;
        const data = ["revise", "reject"].includes(action) ? { catatan: reviewNote } : {};
        router.post(`/rab/${item.id}/${action}`, data, { preserveScroll: true });
    };

    return (
        <AppLayout title={isEdit ? "Edit RAB" : "Tambah RAB"}>
            <div className="mb-4">
                <Link href="/rab" className="inline-flex items-center gap-1 text-sm text-slate-400 hover:text-[#4cceac] transition">
                    ← Kembali ke Data RAB
                </Link>
            </div>

            {(activeProgram || item?.programKerja) && (
                <div className="mb-6 page-card">
                    <h2 className="mb-3 font-bold">Program Kerja</h2>
                    <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode</p>
                            <b className="text-[#4cceac]">{(activeProgram || item?.programKerja)?.kode_program}</b>
                        </div>
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama Program</p>
                            <b>{(activeProgram || item?.programKerja)?.nama_program}</b>
                        </div>
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Estimasi RAB</p>
                            <b className="text-[#4cceac]">{rupiah(estimasi)}</b>
                        </div>
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</p>
                            <b>{(activeProgram || item?.programKerja)?.kategori?.nama_kategori ?? "-"}</b>
                        </div>
                    </div>

                    {estimasiItems.length > 0 && (
                        <div className="mt-4">
                            <p className="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Rincian Estimasi dari Program Kerja</p>
                            <div className="table-shell">
                                <table className="data-table min-w-[600px]">
                                    <thead><tr><th>Item</th><th className="w-28 text-right">Jumlah Item</th><th className="w-32 text-right">Harga Satuan</th><th className="w-32 text-right">Subtotal</th></tr></thead>
                                    <tbody>
                                        {estimasiItems.map((d) => (
                                            <tr key={d.id}>
                                                <td><b>{d.nama_item}</b><p className="text-xs text-slate-500">{d.keterangan ?? "-"}</p></td>
                                                <td className="text-right">{d.jumlah_item}</td>
                                                <td className="text-right">{rupiah(d.harga_satuan)}</td>
                                                <td className="text-right font-semibold text-[#4cceac]">{rupiah(d.subtotal)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </div>
            )}

            <form onSubmit={submit} className="page-card grid gap-4 md:grid-cols-2">
                {!isEdit && !activeProgram && (
                    <div className="md:col-span-2">
                        <SmartSelect label="Program Kerja yang membutuhkan RAB" value={form.data.program_kerja_id} onChange={(value) => {
                            form.setData("program_kerja_id", value);
                            const found = programs.find(p => String(p.id) === String(value));
                            setActiveProgram(found ?? null);
                        }} options={programs} placeholder="Pilih program kerja" required getOptionValue={(x) => x.id} getOptionLabel={(x) => `${x.kode_program} - ${x.nama_program}`} getOptionDescription={(x) => [x.cabang?.nama_cabang, x.kategori?.nama_kategori].filter(Boolean).join(" · ") || "Estimasi " + rupiah(x.estimasi_anggaran)} />
                    </div>
                )}
                {isEdit && (
                    <div className="md:col-span-2 text-sm text-slate-400">
                        Program: <span className="font-semibold text-[#4cceac]">{item?.programKerja?.kode_program} - {item?.programKerja?.nama_program}</span>
                    </div>
                )}
                <label>Tanggal RAB<input className="input mt-1" type="date" value={form.data.tanggal_rab || todayValue()} onChange={(e) => form.setData("tanggal_rab", e.target.value)} /></label>
                <div>
                    <p className="mb-2 text-sm font-semibold text-slate-500">Status RAB</p>
                    <StatusBadge value={currentStatus} />
                    <p className="mt-2 text-xs text-slate-500">Status awal otomatis Diajukan. Jika item RAB diubah, status menjadi Direvisi.</p>
                </div>
                <label className="md:col-span-2">Catatan<input className="input mt-1" value={form.data.catatan} onChange={(e) => form.setData("catatan", e.target.value)} /></label>
                {isEdit && <div className="md:col-span-2"><AuditInfo item={item} /></div>}
                <div className="md:col-span-2 flex flex-wrap justify-between gap-2">
                    <Link href="/rab" className="btn-light">Kembali</Link>
                    <button className="btn-primary" disabled={form.processing}>{isEdit ? "Simpan Catatan" : "Buat RAB Diajukan"}</button>
                </div>
            </form>

            {isEdit && canReviewRab && (
                <div className="mt-6 page-card">
                    <h2 className="mb-2 font-bold">Review RAB</h2>
                    <p className="mb-4 text-sm text-slate-500">Setujui RAB jika sudah benar. Jika item diubah, sistem otomatis menandai RAB sebagai Direvisi.</p>
                    <textarea className="input min-h-[90px]" value={reviewNote} onChange={(e) => setReviewNote(e.target.value)} placeholder="Catatan review, wajib diisi jika perlu revisi atau ditolak." />
                    <div className="mt-4 flex flex-wrap gap-2">
                        <button type="button" className="btn-primary" onClick={() => doAction("approve")}><ThumbsUp size={16} className="mr-2" />Setujui RAB</button>
                        <button type="button" className="btn-light" onClick={() => doAction("revise")}><Undo2 size={16} className="mr-2" />Tandai Revisi</button>
                        <button type="button" className="rounded-xl border border-red-500/30 px-4 py-2 text-sm font-semibold text-red-300 hover:bg-red-500/10" onClick={() => doAction("reject")}><ThumbsDown size={16} className="mr-2 inline" />Tolak RAB</button>
                    </div>
                </div>
            )}

            {isEdit && (
                <div className="mt-6 page-card">
                    <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 className="font-bold">Item RAB</h2>
                            <p className="text-sm text-slate-500">Item resmi RAB. Saat item ditambah/diubah/dihapus, status otomatis menjadi RAB Direvisi.</p>
                            {estimasi > 0 && (
                                <div className="mt-2 flex flex-wrap items-center gap-3">
                                    <span className="text-xs text-slate-500">Estimasi program: <b className="text-[#4cceac]">{rupiah(estimasi)}</b></span>
                                    <span className="text-xs text-slate-500">Total RAB: <b className={item.total_rab > estimasi ? "text-red-400" : item.total_rab < estimasi ? "text-yellow-400" : "text-emerald-400"}>{rupiah(item.total_rab)}</b></span>
                                    {item.total_rab > estimasi && <span className="rounded-xl border border-red-500/30 bg-red-500/10 px-2 py-1 text-xs font-bold text-red-300">Melebihi estimasi</span>}
                                    {item.total_rab <= estimasi && <span className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-2 py-1 text-xs font-bold text-emerald-300">Sesuai/Murah</span>}
                                </div>
                            )}
                        </div>
                        {!itemsEditable && <span className="rounded-xl border border-yellow-500/30 bg-yellow-500/10 px-3 py-2 text-xs font-bold text-yellow-200">Item terkunci</span>}
                    </div>

                    <form onSubmit={saveItem} className={`mb-4 grid gap-3 md:grid-cols-[2fr_140px_180px_auto] ${!itemsEditable ? "opacity-60" : ""}`}>
                        <input className="input" placeholder="Item, contoh: Freon AC" value={itemForm.data.nama_item} onChange={(e) => itemForm.setData("nama_item", e.target.value)} disabled={!itemsEditable} required />
                        <input className="input" placeholder="Jumlah item" type="number" step="0.01" min="0" value={itemForm.data.jumlah_item} onChange={(e) => itemForm.setData("jumlah_item", e.target.value)} disabled={!itemsEditable} required />
                        <input className="input" placeholder="Harga satuan" type="number" step="0.01" min="0" value={itemForm.data.harga_satuan} onChange={(e) => itemForm.setData("harga_satuan", e.target.value)} disabled={!itemsEditable} required />
                        <button className="btn-primary justify-center" disabled={!itemsEditable || itemForm.processing}>{editingItem ? "Update" : "Tambah"}</button>
                        <input className="input md:col-span-4" placeholder="Keterangan opsional" value={itemForm.data.keterangan} onChange={(e) => itemForm.setData("keterangan", e.target.value)} disabled={!itemsEditable} />
                        {editingItem && <button type="button" className="btn-light md:col-span-4 justify-center" onClick={resetItem}>Batal edit item</button>}
                    </form>
                    <div className="table-shell">
                        <table className="data-table min-w-[760px]">
                            <thead><tr><th>Item RAB</th><th className="w-32 text-right">Jumlah Item</th><th className="w-36 text-right">Harga Satuan</th><th className="w-36 text-right">Subtotal</th><th className="w-24 text-right">Aksi</th></tr></thead>
                            <tbody>
                                {details.map((d) => (
                                    <tr key={d.id}>
                                        <td><b>{d.nama_item}</b><p className="text-xs text-slate-500">{d.keterangan ?? "-"}</p></td>
                                        <td className="text-right">{d.jumlah_item}</td>
                                        <td className="text-right">{rupiah(d.harga_satuan)}</td>
                                        <td className="text-right font-semibold">{rupiah(d.subtotal)}</td>
                                        <td className="text-right"><div className="table-actions justify-end"><button type="button" className="icon-btn" disabled={!itemsEditable} onClick={() => editItem(d)} title="Edit item"><Edit3 size={14} /></button><button type="button" className="icon-btn-danger" disabled={!itemsEditable} onClick={() => confirm("Hapus item ini?") && router.delete(`/rab-items/${d.id}`, { preserveScroll: true })} title="Hapus item"><Trash2 size={14} /></button></div></td>
                                    </tr>
                                ))}
                                {details.length === 0 && <tr><td colSpan="5" className="text-center text-slate-500">Belum ada item RAB.</td></tr>}
                            </tbody>
                        </table>
                    </div>
                    <div className="mt-4 rounded-2xl border border-[#29314b] bg-[#141b2d] px-4 py-3 text-left">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Total RAB</p>
                                <b className="table-currency block text-lg text-[#4cceac] sm:text-xl">{rupiah(item.total_rab)}</b>
                            </div>
                            {estimasi > 0 && (
                                <div className="text-right">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Estimasi</p>
                                    <b className="text-lg text-slate-400">{rupiah(estimasi)}</b>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
