import AuditInfo from "@/Components/AuditInfo";
import PriorityBadge from "@/Components/PriorityBadge";
import StatusBadge from "@/Components/StatusBadge";
import AppLayout from "@/Layouts/AppLayout";
import { formatDate } from "@/Utils/date";
import { Link, router } from "@inertiajs/react";
import { ClipboardList, Edit3, Eye, FileText, WalletCards } from "lucide-react";

const rupiah = (value) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value ?? 0));

function Info({ label, value }) {
    return <div><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><b>{value || "-"}</b></div>;
}

export default function Show({ item, permissions = {}, canBecomePekerjaan = false }) {
    const pekerjaan = item?.pekerjaan ?? item?.pekerjaans ?? [];
    const isConverted = Boolean(item?.converted_to_pekerjaan_id);
    const estimasiItems = item?.estimasiItems ?? [];
    const estimasiTotal = Number(item?.estimasi_total ?? estimasiItems.reduce((sum, x) => sum + Number(x.subtotal ?? 0), 0));

    const canCreateRab = permissions["rab.create"] && item?.needs_rab && !item?.rab && !isConverted;
    const canConvert = permissions["pekerjaan.create"] && canBecomePekerjaan && !isConverted;

    const handleConvertToPekerjaan = () => {
        if (confirm("Jadikan program kerja ini sebagai Data Pekerjaan?")) {
            router.post(`/program-kerja/${item.id}/to-pekerjaan`, {}, { preserveScroll: true });
        }
    };

    return (
        <AppLayout title="Detail Program Kerja">
            <div className="mb-5 flex flex-wrap justify-between gap-3">
                <Link href="/program-kerja" className="btn-light">← Kembali</Link>
                <div className="flex flex-wrap gap-2">
                    {canCreateRab && (
                        <Link href={`/rab/create?program_kerja_id=${item.id}`} className="btn-light">
                            <WalletCards size={16} className="mr-2" />Buat RAB
                        </Link>
                    )}
                    {canConvert && (
                        <button type="button" onClick={handleConvertToPekerjaan} className="btn-primary">
                            <ClipboardList size={16} className="mr-2" />Jadikan Pekerjaan
                        </button>
                    )}
                    {permissions["program_kerja.edit"] && !isConverted && (
                        <Link href={`/program-kerja/${item.id}/edit`} className="btn-primary">
                            <Edit3 size={16} className="mr-2" />Edit Program
                        </Link>
                    )}
                </div>
            </div>

            <div className="space-y-6">
                {/* Header */}
                <div className="page-card">
                    <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p className="text-sm font-bold text-[#4cceac]">{item.kode_program}</p>
                            <h2 className="text-2xl font-black">{item.nama_program}</h2>
                        </div>
                        <StatusBadge value={item.status} />
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <Info label="Sumber" value={item.source_type || "PROKER"} />
                        <Info label="Tahun" value={item.tahun} />
                        <div><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Prioritas</p><div className="mt-1"><PriorityBadge value={item.prioritas} /></div></div>
                        <Info label="Kebutuhan RAB" value={item.needs_rab ? "Perlu RAB" : "Tidak perlu RAB"} />
                        <Info label="Status RAB" value={item.needs_rab ? (item.rab?.status_rab || "Diajukan") : "Tidak perlu RAB"} />
                        {item.needs_rab && <Info label="Estimasi Tersimpan" value={estimasiTotal > 0 ? rupiah(estimasiTotal) : "Tidak ada estimasi"} />}
                        <Info label="Cabang" value={item.cabang?.nama_cabang} />
                        <Info label="Kategori" value={item.kategori?.nama_kategori} />
                        <Info label="Target Mulai" value={formatDate(item.target_mulai)} />
                        <Info label="Target Selesai" value={formatDate(item.target_selesai)} />
                    </div>

                    <div className="mt-5 grid gap-4 md:grid-cols-2">
                        <Info label="Deskripsi" value={item.deskripsi} />
                        <Info label="Keterangan" value={item.keterangan} />
                    </div>

                    {/* Estimasi Tersimpan */}
                    {item.needs_rab && estimasiItems.length > 0 && (
                        <div className="mt-4">
                            <p className="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Estimasi Tersimpan</p>
                            <div className="table-shell">
                                <table className="data-table min-w-[500px]">
                                    <thead><tr><th>Item</th><th className="w-24 text-right">Jumlah Item</th><th className="w-32 text-right">Harga Satuan</th><th className="w-36 text-right">Subtotal</th></tr></thead>
                                    <tbody>
                                        {estimasiItems.map((d) => (
                                            <tr key={d.id}>
                                                <td><b>{d.nama_item}</b></td>
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

                    {/* RAB Warning/Link */}
                    {item.needs_rab && !item.rab && (
                        <p className="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-200">
                            Program ini membutuhkan RAB. Sistem akan membuat RAB otomatis dari estimasi item. Setujui RAB sebelum program dijadikan Data Pekerjaan.
                        </p>
                    )}
                    {item.needs_rab && item.rab && item.rab.status_rab !== "Disetujui" && (
                        <p className="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-200">
                            Program baru bisa dijadikan Data Pekerjaan setelah RAB berstatus Disetujui.
                        </p>
                    )}
                    {item.needs_rab && item.rab && (
                        <Link href={`/rab/${item.rab.id}`} className="mt-4 inline-flex items-center text-sm font-bold text-[#4cceac]">
                            <FileText size={16} className="mr-2" />Lihat RAB {item.rab.status_rab}
                        </Link>
                    )}
                </div>

                {/* Pekerjaan List */}
                <div className="page-card">
                    <h2 className="mb-4 font-bold">Data Pekerjaan dari Program Ini</h2>
                    {pekerjaan.length === 0 ? (
                        <p className="text-center text-sm text-slate-500">Belum dijadikan Data Pekerjaan.</p>
                    ) : (
                        <div className="table-shell">
                            <table className="data-table min-w-[900px] table-fixed">
                                <thead><tr><th className="w-40">Kode</th><th>Pekerjaan</th><th className="w-40">Petugas</th><th className="w-36 text-center">Progres</th><th className="w-36 text-center">Status</th><th className="w-24 text-right">Aksi</th></tr></thead>
                                <tbody>
                                    {pekerjaan.map((p) => (
                                        <tr key={p.id}>
                                            <td className="text-[#4cceac] font-semibold table-nowrap">{p.kode_pekerjaan}</td>
                                            <td><div className="truncate" title={p.nama_pekerjaan}>{p.nama_pekerjaan}</div><p className="text-xs text-slate-500">{p.kategori?.nama_kategori ?? "-"}</p></td>
                                            <td>{p.petugas?.name ?? "-"}</td>
                                            <td className="text-center font-bold">{p.progress ?? 0}%</td>
                                            <td className="text-center"><StatusBadge value={p.status} /></td>
                                            <td className="text-right">{permissions["pekerjaan.show"] && <Link href={`/pekerjaan/${p.id}`} className="icon-btn"><Eye size={15} /></Link>}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                <div className="page-card h-fit">
                    <h2 className="mb-4 font-bold">Riwayat</h2>
                    <AuditInfo item={item} />
                </div>
            </div>
        </AppLayout>
    );
}