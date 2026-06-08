import AuditInfo from "@/Components/AuditInfo";
import StatusBadge from "@/Components/StatusBadge";
import AppLayout from "@/Layouts/AppLayout";
import { formatDate } from "@/Utils/date";
import { Link, router } from "@inertiajs/react";
import { Edit3, Eye, ThumbsDown, ThumbsUp, Undo2 } from "lucide-react";
import { useState } from "react";

const rupiah = (value) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value ?? 0));
function Info({ label, value }) { return <div><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><b>{value || "-"}</b></div>; }

export default function Show({ item, permissions = {}, itemsEditable = false }) {
    const currentStatus = item.status_rab;
    const program = item.program_kerja ?? item.programKerja;
    const pekerjaan = item.pekerjaan;
    const estimasiItems = program?.estimasiItems ?? [];
    const estimasiTotal = Number(program?.estimasi_total ?? 0);
    const totalRab = Number(item.total_rab ?? 0);
    const selisih = totalRab - estimasiTotal;

    const canReviewRab = ["Diajukan", "Direvisi"].includes(currentStatus) && Boolean(permissions["rab.edit"]);
    const [reviewNote, setReviewNote] = useState(item.catatan ?? "");

    const doAction = (action) => {
        const messages = {
            approve: "Setujui RAB ini? Program Kerja dapat dijadikan Data Pekerjaan.",
            revise: "Tandai RAB ini perlu revisi? Item akan tetap bisa diedit.",
            reject: "Tolak RAB ini?",
        };
        if (!confirm(messages[action])) return;
        const data = ["revise", "reject"].includes(action) ? { catatan: reviewNote } : {};
        router.post(`/rab/${item.id}/${action}`, data, { preserveScroll: true });
    };

    return (
        <AppLayout title="Detail RAB">
            <div className="mb-5 flex flex-wrap justify-between gap-3">
                <Link href="/rab" className="btn-light">← Kembali</Link>
                <div className="flex flex-wrap gap-2">
                    {permissions["program_kerja.show"] && program && <Link href={`/program-kerja/${program.id}`} className="btn-light"><Eye size={16} className="mr-2" />Detail Program</Link>}
                    {permissions["pekerjaan.show"] && pekerjaan && <Link href={`/pekerjaan/${pekerjaan.id}`} className="btn-light"><Eye size={16} className="mr-2" />Detail Pekerjaan</Link>}
                    {permissions["rab.edit"] && <Link href={`/rab/${item.id}/edit`} className="btn-primary"><Edit3 size={16} className="mr-2" />Edit RAB</Link>}
                </div>
            </div>

            <div className="space-y-6">
                <div className="page-card">
                    <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p className="text-sm font-bold text-[#4cceac]">{item.nomor_rab}</p>
                            <h2 className="text-2xl font-black">{program?.nama_program ?? pekerjaan?.nama_pekerjaan ?? "-"}</h2>
                        </div>
                        <StatusBadge value={currentStatus} />
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <Info label="Kode Program" value={program?.kode_program ?? pekerjaan?.kode_pekerjaan} />
                        <Info label="Tanggal RAB" value={formatDate(item.tanggal_rab)} />
                        <Info label="Estimasi Total" value={estimasiTotal > 0 ? rupiah(estimasiTotal) : "Tidak ada estimasi"} />
                        <Info label="Total RAB" value={rupiah(totalRab)} />
                        {estimasiTotal > 0 && <Info label="Selisih" value={selisih >= 0 ? `+${rupiah(selisih)}` : rupiah(selisih)} />}
                        <Info label="Cabang" value={program?.cabang?.nama_cabang ?? pekerjaan?.cabang?.nama_cabang} />
                        <Info label="Kategori" value={program?.kategori?.nama_kategori ?? pekerjaan?.kategori?.nama_kategori} />
                        <Info label="Sumber" value={program?.source_type ?? "PROKER"} />
                        <Info label="Diajukan" value={item.submitted_at ? formatDate(item.submitted_at) : "-"} />
                        <Info label="Direview oleh" value={item.reviewer?.name} />
                        <Info label="Tanggal Review" value={item.reviewed_at ? formatDate(item.reviewed_at) : "-"} />
                        <Info label="Catatan" value={item.catatan} />
                    </div>

                    {estimasiTotal > 0 && (
                        <div className="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                            <div className="rounded-2xl border border-[#29314b] bg-[#141b2d] p-4 text-center">
                                <p className="text-xs font-bold uppercase tracking-wide text-slate-500">Estimasi</p>
                                <b className="text-lg text-slate-400">{rupiah(estimasiTotal)}</b>
                            </div>
                            <div className="rounded-2xl border border-[#29314b] bg-[#141b2d] p-4 text-center">
                                <p className="text-xs font-bold uppercase tracking-wide text-slate-500">Total RAB</p>
                                <b className="text-lg text-[#4cceac]">{rupiah(totalRab)}</b>
                            </div>
                            <div className="rounded-2xl border border-[#29314b] bg-[#141b2d] p-4 text-center">
                                <p className="text-xs font-bold uppercase tracking-wide text-slate-500">Selisih</p>
                                <b className={`text-lg ${selisih > 0 ? "text-red-400" : "text-emerald-400"}`}>{selisih >= 0 ? "+" : ""}{rupiah(selisih)}</b>
                            </div>
                            <div className="rounded-2xl border border-[#29314b] bg-[#141b2d] p-4 text-center">
                                <p className="text-xs font-bold uppercase tracking-wide text-slate-500">Status Selisih</p>
                                {selisih < 0 ? <span className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-300">Hemat</span> : selisih === 0 ? <span className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-300">Sesuai</span> : <span className="rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-1 text-xs font-bold text-red-300">Melebihi</span>}
                            </div>
                        </div>
                    )}
                </div>

                {canReviewRab && (
                    <div className="page-card">
                        <h2 className="mb-2 font-bold">Aksi RAB</h2>
                        <p className="mb-4 text-sm text-slate-500">Setujui RAB jika sudah benar. Jika item RAB diubah, status otomatis menjadi Direvisi.</p>
                        <textarea className="input mb-4 min-h-[90px]" value={reviewNote} onChange={(e) => setReviewNote(e.target.value)} placeholder="Catatan review untuk revisi atau penolakan." />
                        <div className="flex flex-wrap gap-2">
                            <button type="button" className="btn-primary" onClick={() => doAction("approve")}><ThumbsUp size={16} className="mr-2" />Setujui RAB</button>
                            <button type="button" className="btn-light" onClick={() => doAction("revise")}><Undo2 size={16} className="mr-2" />Tandai Revisi</button>
                            <button type="button" className="rounded-xl border border-red-500/30 px-4 py-2 text-sm font-semibold text-red-300 hover:bg-red-500/10" onClick={() => doAction("reject")}><ThumbsDown size={16} className="mr-2 inline" />Tolak RAB</button>
                        </div>
                    </div>
                )}

                {estimasiItems.length > 0 && (
                    <div className="page-card">
                        <div className="mb-4"><h2 className="font-bold">Referensi Estimasi dari Program Kerja</h2><p className="text-sm text-slate-500">Estimasi ini adalah acuan awal. Item RAB resmi ada di bawah.</p></div>
                        <div className="table-shell">
                            <table className="data-table min-w-[600px]">
                                <thead><tr><th>Item</th><th className="w-32 text-right">Jumlah Item</th><th className="w-36 text-right">Harga Satuan</th><th className="w-32 text-right">Subtotal</th></tr></thead>
                                <tbody>
                                    {estimasiItems.map((d) => (
                                        <tr key={d.id}>
                                            <td><b>{d.nama_item}</b><p className="text-xs text-slate-500">{d.keterangan ?? "-"}</p></td>
                                            <td className="text-right">{d.jumlah_item}</td>
                                            <td className="text-right">{rupiah(d.harga_satuan)}</td>
                                            <td className="text-right font-semibold text-slate-400">{rupiah(d.subtotal)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                <div className="page-card">
                    <div className="mb-4">
                        <h2 className="font-bold">Item RAB Resmi</h2>
                        <p className="text-sm text-slate-500">Item {itemsEditable ? "masih dapat diedit. Setiap perubahan akan membuat status RAB Direvisi." : "terkunci karena RAB sudah disetujui/ditolak."}</p>
                    </div>
                    <div className="table-shell">
                        <table className="data-table min-w-[760px] table-fixed">
                            <thead><tr><th>Item</th><th className="w-32 text-right">Jumlah Item</th><th className="w-36 text-right">Harga Satuan</th><th className="w-36 text-right">Subtotal</th></tr></thead>
                            <tbody>
                                {(item.details ?? []).map((d) => (
                                    <tr key={d.id}>
                                        <td><b>{d.nama_item}</b><p className="text-xs text-slate-500">{d.keterangan ?? "-"}</p></td>
                                        <td className="text-right">{d.jumlah_item}</td>
                                        <td className="text-right">{rupiah(d.harga_satuan)}</td>
                                        <td className="text-right font-bold text-[#4cceac]">{rupiah(d.subtotal)}</td>
                                    </tr>
                                ))}
                                {(item.details ?? []).length === 0 && <tr><td colSpan="4" className="text-center text-slate-500">Belum ada item RAB.</td></tr>}
                            </tbody>
                        </table>
                    </div>
                    <div className="mt-4 rounded-2xl border border-[#29314b] bg-[#141b2d] px-4 py-3 text-right">
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-500">Total RAB</p>
                        <b className="text-xl text-[#4cceac]">{rupiah(totalRab)}</b>
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-2">
                    <div className="page-card"><h2 className="mb-4 font-bold">Riwayat RAB</h2><AuditInfo item={item} /></div>
                    <div className="page-card">
                        <h2 className="mb-4 font-bold">Riwayat Item RAB</h2>
                        <div className="space-y-3">
                            {(item.details ?? []).map((d) => (
                                <div key={d.id} className="rounded-2xl border border-[#29314b] bg-[#141b2d] p-3">
                                    <b>{d.nama_item}</b>
                                    <AuditInfo item={d} />
                                </div>
                            ))}
                            {(item.details ?? []).length === 0 && <p className="text-sm text-slate-500">Belum ada riwayat item.</p>}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
