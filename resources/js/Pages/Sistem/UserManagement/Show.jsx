import AuditInfo from "@/Components/AuditInfo";
import StatusBadge from "@/Components/StatusBadge";
import AppLayout from "@/Layouts/AppLayout";
import { Link } from "@inertiajs/react";
import { Edit3, Eye } from "lucide-react";

function Info({ label, value }) { return <div><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><b>{value || "-"}</b></div>; }

export default function Show({ item, permissions = {} }) {
    const jobs = item?.pekerjaan_ditugaskan ?? item?.pekerjaanDitugaskan ?? [];
    const roleCategory = item.role_category?.name ?? item.roleCategory?.name;
    return (
        <AppLayout title="Detail User">
            <div className="mb-5 flex flex-wrap justify-between gap-3"><Link href="/users-management" className="btn-light">Kembali</Link>{permissions["users.edit"] && <Link href={`/users-management/${item.id}/edit`} className="btn-primary"><Edit3 size={16} className="mr-2" />Edit User</Link>}</div>
            <div className="space-y-6">
                <div className="space-y-6">
                    <div className="page-card"><h2 className="mb-4 text-2xl font-black">{item.name}</h2><div className="grid gap-4 md:grid-cols-3"><Info label="Email" value={item.email} /><Info label="Role" value={item.role?.nama_role} /><Info label="Subkategori" value={roleCategory} /><Info label="Jenis Identitas" value={item.identity_type} /><Info label="Nomor Identitas" value={item.identity_number} /><Info label="Status" value={item.status} /><Info label="Cabang" value={item.cabang?.nama_cabang} /><Info label="No. HP" value={item.phone} /></div></div>
                    <div className="page-card"><h2 className="mb-4 font-bold">Tugas Pekerjaan</h2><div className="table-shell"><table className="data-table min-w-[900px] table-fixed"><thead><tr><th className="w-40">Kode</th><th>Pekerjaan</th><th className="w-40">Cabang</th><th className="w-36 text-center">Progres</th><th className="w-36 text-center">Status</th><th className="w-24 text-right">Aksi</th></tr></thead><tbody>{jobs.map((p) => <tr key={p.id}><td className="table-nowrap text-[#4cceac] font-semibold">{p.kode_pekerjaan}</td><td><div className="truncate" title={p.nama_pekerjaan}>{p.nama_pekerjaan}</div><p className="text-xs text-slate-500">{p.kategori?.nama_kategori ?? "-"}</p></td><td>{p.cabang?.nama_cabang ?? "-"}</td><td className="text-center font-bold">{p.progress}%</td><td className="text-center"><StatusBadge value={p.status} /></td><td className="text-right">{permissions["pekerjaan.show"] && <Link href={`/pekerjaan/${p.id}`} className="icon-btn"><Eye size={15} /></Link>}</td></tr>)}{jobs.length === 0 && <tr><td colSpan="6" className="text-center text-slate-500">Belum ada pekerjaan ditugaskan.</td></tr>}</tbody></table></div></div>
                </div>
                <div className="page-card h-fit"><h2 className="mb-4 font-bold">Riwayat</h2><AuditInfo item={item} /></div>
            </div>
        </AppLayout>
    );
}
