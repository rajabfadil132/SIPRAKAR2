import AuditInfo from "@/Components/AuditInfo";
import StatusBadge from "@/Components/StatusBadge";
import AppLayout from "@/Layouts/AppLayout";
import { formatDate } from "@/Utils/date";
import { Link, router, useForm } from "@inertiajs/react";
import { CheckCircle2, Edit3, FileSpreadsheet, Trash2 } from "lucide-react";
import { useState } from "react";

const rupiah = (value) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value ?? 0));

export default function Show({ item, permissions = {}, canUpdateAssignment = false, canUpdateChecklist = false, rabBlockMessage = null }) {
    const checklists = item.checklists ?? [];
    const checklistTotal = checklists.length;
    const checklistDone = checklists.filter((checklist) => Boolean(checklist.is_done)).length;
    const progress = checklistTotal > 0 ? Math.round((checklistDone / checklistTotal) * 100) : Number(item.progress ?? 0);
    const canViewRab = Boolean(permissions["rab.view"] || permissions["rab.create"] || permissions["rab.edit"]);
    const assignments = item.petugas_tambahan ?? item.petugasTambahan ?? [];
    const [deleteOpen, setDeleteOpen] = useState(false);
    const deleteForm = useForm({ delete_reason: "" });

    const toggleChecklist = (checklist) => {
        if (!canUpdateChecklist) return;
        router.patch(`/pekerjaan/${item.id}/checklist/${checklist.id}`, { is_done: !checklist.is_done }, { preserveScroll: true });
    };
    const submitDelete = (e) => {
        e.preventDefault();
        deleteForm.delete(`/pekerjaan/${item.id}`, { preserveScroll: true, onSuccess: () => setDeleteOpen(false) });
    };

    return (
        <AppLayout title={item.nama_pekerjaan}>
            <div className="mb-5 flex flex-wrap justify-between gap-2">
                <Link href="/pekerjaan" className="btn-light">Kembali</Link>
                <div className="flex flex-wrap gap-2">
                    {item.rab && permissions["rab.view"] && <Link href={`/rab/${item.rab.id}`} className="btn-light"><FileSpreadsheet size={16} className="mr-2" />Detail Anggaran</Link>}
                    
                    {permissions["pekerjaan.edit"] && <Link href={`/pekerjaan/${item.id}/edit`} className="btn-light"><Edit3 size={16} className="mr-2" />Edit</Link>}
                    {permissions["pekerjaan.delete"] && <button type="button" className="rounded-xl border border-red-500/30 px-4 py-2 text-sm font-semibold text-red-300 hover:bg-red-500/10" onClick={() => { deleteForm.setData("delete_reason", ""); deleteForm.clearErrors(); setDeleteOpen(true); }}><Trash2 size={16} className="mr-2 inline" />Hapus</button>}
                </div>
            </div>

            <div className="space-y-6">
                <div className="page-card">
                    <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                        <div><b className="text-[#4cceac]">{item.kode_pekerjaan}</b><p className="mt-1 text-sm text-slate-500">{item.program_kerja?.nama_program ?? "Tanpa program kerja"}</p></div>
                        <StatusBadge value={item.status_label ?? item.status} />
                    </div>
                    <p className="text-slate-600 dark:text-slate-300">{item.deskripsi ?? "Belum ada deskripsi."}</p>
                    <div className="mt-6 grid gap-4 md:grid-cols-3">
                        <Info label="Cabang" value={item.cabang?.nama_cabang} />
                        <Info label="Lokasi" value={item.label_lokasi ?? item.lokasi?.label_lokasi} />
                        <Info label="Kategori" value={item.kategori?.nama_kategori} />
                        <Info label="Prioritas" value={item.prioritas} />
                        <Info label="Penanggung Jawab" value={item.penanggung_jawab?.name} />
                        <Info label="Petugas Utama" value={item.petugas?.name} />
                        <Info label="Durasi" value={`${item.durasi_label ?? "-"} · ${item.sisa_label ?? "-"}`} />
                        <Info label="Estimasi Anggaran Awal" value={rupiah(item.estimasi_rab_awal)} />
                        <Info label="Tanggal Mulai" value={formatDate(item.tanggal_mulai)} />
                        <Info label="Target Selesai" value={formatDate(item.target_selesai)} />
                    </div>
                    <div className="mt-6">
                        <div className="mb-2 flex flex-wrap justify-between gap-2 text-sm"><span>Progres checklist</span><b>{progress}% ({checklistDone}/{checklistTotal || 0} detail)</b></div>
                        <div className="h-3 rounded-full bg-[#29314b]"><div className="h-3 rounded-full bg-[#4cceac]" style={{ width: `${progress}%` }} /></div>
                    </div>
                </div>

                <div className="page-card">
                    <h2 className="mb-4 font-bold">Petugas Ditugaskan</h2>
                    {assignments.length === 0 && !item.petugas?.name && <p className="text-sm text-slate-500">Belum ada petugas tambahan.</p>}
                    <div className="grid gap-3 md:grid-cols-2">
                        {item.petugas?.name && <div className="rounded-2xl border border-[#29314b] bg-[#141b2d] p-4"><b>{item.petugas.name}</b><p className="text-xs text-slate-500">Petugas utama</p></div>}
                        {assignments.map((assignment) => (
                            <div key={assignment.id} className="rounded-2xl border border-[#29314b] bg-[#141b2d] p-4">
                                <b>{assignment.user?.name ?? assignment.nama_petugas_manual ?? "Petugas"}</b>
                                <p className="text-xs text-slate-500">Role: {assignment.role_text ?? assignment.user?.role_category?.name ?? assignment.user?.roleCategory?.name ?? assignment.user?.role?.nama_role ?? "-"}</p>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="page-card">
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <div><h2 className="font-bold">Checklist Detail Pekerjaan</h2><p className="text-sm text-slate-500">Mencentang checklist akan langsung menghitung ulang progress pekerjaan.</p></div>
                        <CheckCircle2 className="text-[#4cceac]" />
                    </div>
                    {rabBlockMessage && <div className="mb-4 rounded-2xl border border-yellow-500/30 bg-yellow-500/10 p-4 text-sm text-yellow-200">{rabBlockMessage}</div>}
                    <div className="space-y-3">
                        {checklists.map((checklist) => (
                            <label key={checklist.id} className={`flex items-start gap-3 rounded-2xl border border-[#29314b] bg-[#141b2d] p-4 ${!canUpdateChecklist ? "opacity-75" : ""}`}>
                                <input type="checkbox" className="mt-1 h-5 w-5 rounded border-[#29314b] bg-[#141b2d] text-[#6870fa] focus:ring-[#6870fa]" checked={Boolean(checklist.is_done)} disabled={!canUpdateChecklist} onChange={() => toggleChecklist(checklist)} />
                                <span className="flex-1">
                                    <b className={checklist.is_done ? "line-through opacity-70" : ""}>{checklist.deskripsi}</b>
                                    <span className="mt-1 block text-xs text-slate-500">{checklist.is_done ? `Selesai oleh ${checklist.completer?.name ?? "-"} pada ${formatDate(checklist.completed_at)}` : "Belum selesai"}</span>
                                </span>
                            </label>
                        ))}
                        {checklists.length === 0 && <p className="text-sm text-slate-500">Belum ada checklist. Tambahkan dari form edit pekerjaan.</p>}
                    </div>
                </div>

                {canViewRab && (
                    <div className="page-card">
                        <h2 className="mb-4 font-bold">Anggaran</h2>
                        {item.rab ? (
                            <div className="space-y-4">
                                <div className="grid gap-3 md:grid-cols-3"><Info label="Nomor Anggaran" value={item.rab.nomor_rab} /><Info label="Status Anggaran" value={item.rab.status_rab} /><Info label="Total Anggaran" value={rupiah(item.rab.total_rab)} /></div>
                                <div className="table-shell"><table className="data-table min-w-[760px] table-fixed"><thead><tr><th>Item</th><th className="w-28 text-right">Jumlah Item</th><th className="w-36 text-right">Harga Satuan</th><th className="w-36 text-right">Subtotal</th></tr></thead><tbody>{(item.rab.details ?? []).map((d) => <tr key={d.id}><td><b>{d.nama_item}</b><p className="text-xs text-slate-500">{d.keterangan ?? "-"}</p></td><td className="text-right">{d.jumlah_item}</td><td className="text-right">{rupiah(d.harga_satuan)}</td><td className="text-right font-bold">{rupiah(d.subtotal)}</td></tr>)}</tbody></table></div>
                            </div>
                        ) : <p className="text-sm text-slate-500">Pekerjaan ini belum memiliki data anggaran.</p>}
                    </div>
                )}

                <div className="page-card">
                    <h2 className="mb-4 font-bold">Riwayat Progres</h2>
                    {(item.progress_logs ?? []).length === 0 && <p className="text-sm text-slate-500">Belum ada riwayat progress.</p>}
                    <div className="space-y-3">
                        {(item.progress_logs ?? []).map((log) => (
                            <div className="rounded-2xl border border-[#29314b] bg-[#141b2d] p-4" key={log.id}>
                                <div className="flex flex-wrap items-center justify-between gap-3"><b>{log.progress}% - {log.status}</b><span className="text-xs text-slate-500">{formatDate(log.tanggal_update)} · {log.updater?.name ?? "-"}</span></div>
                                <p className="mt-2 text-sm text-slate-500">{log.catatan ?? "-"}</p>
                                {(log.kendala || log.solusi) && <div className="mt-3 grid gap-3 text-sm md:grid-cols-2"><p><b>Kendala:</b> {log.kendala ?? "-"}</p><p><b>Solusi:</b> {log.solusi ?? "-"}</p></div>}
                            </div>
                        ))}
                    </div>
                </div>

                <div className="page-card"><h2 className="mb-4 font-bold">Riwayat Audit</h2><AuditInfo item={item} /></div>
            </div>

            {deleteOpen && (
                <div className="fixed inset-0 z-[80] flex items-center justify-center bg-black/60 p-4">
                    <form onSubmit={submitDelete} className="w-full max-w-lg rounded-3xl border border-[#29314b] bg-[#141b2d] p-6 shadow-2xl">
                        <h2 className="text-lg font-black text-white">Hapus pekerjaan ke arsip</h2>
                        <p className="mt-2 text-sm text-slate-400">Tulis alasan penghapusan agar riwayat arsip lebih jelas.</p>
                        <label className="mt-4 block text-sm font-semibold text-slate-300">Alasan penghapusan<textarea className="input mt-1 min-h-[110px]" value={deleteForm.data.delete_reason} onChange={(e) => deleteForm.setData("delete_reason", e.target.value)} required /></label>
                        {deleteForm.errors.delete_reason && <p className="mt-2 text-sm text-red-300">{deleteForm.errors.delete_reason}</p>}
                        <div className="mt-5 flex justify-end gap-2"><button type="button" className="btn-light" onClick={() => setDeleteOpen(false)}>Batal</button><button className="rounded-xl bg-red-500 px-4 py-2 text-sm font-bold text-white hover:bg-red-400" disabled={deleteForm.processing}>Hapus ke Arsip</button></div>
                    </form>
                </div>
            )}
        </AppLayout>
    );
}

function Info({ label, value }) { return <div><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><b>{value || "-"}</b></div>; }
