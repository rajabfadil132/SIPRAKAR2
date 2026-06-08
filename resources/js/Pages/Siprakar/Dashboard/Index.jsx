import { DashboardBarChart, DashboardDonutChart, DashboardLineChart } from "@/Components/SimpleCharts";
import StatCard from "@/Components/StatCard";
import StatusBadge from "@/Components/StatusBadge";
import AppLayout from "@/Layouts/AppLayout";
import { formatDate } from "@/Utils/date";
import { Link } from "@inertiajs/react";
import {
    AlertTriangle,
    BriefcaseBusiness,
    CalendarClock,
    CheckCircle2,
    ClipboardList,
    Clock3,
    Eye,
    FileWarning,
    ListChecks,
    LoaderCircle,
    UserX,
} from "lucide-react";

const rupiah = (value) => `Rp ${Number(value ?? 0).toLocaleString("id-ID")}`;
const isLate = (date) => date && new Date(date).setHours(0, 0, 0, 0) < new Date().setHours(0, 0, 0, 0);

const rabProgram = (rab) => rab?.program_kerja ?? rab?.programKerja ?? null;
const rabPekerjaan = (rab) => rab?.pekerjaan ?? null;
const rabCode = (rab) => rabProgram(rab)?.kode_program ?? rabPekerjaan(rab)?.kode_pekerjaan ?? "-";
const rabName = (rab) => rabProgram(rab)?.nama_program ?? rabPekerjaan(rab)?.nama_pekerjaan ?? "-";
const rabCabang = (rab) => rabProgram(rab)?.cabang?.nama_cabang ?? rabPekerjaan(rab)?.cabang?.nama_cabang ?? "-";

export default function Dashboard({
    summary = {},
    statusCounts = [],
    barCounts = [],
    barTitle = "Pekerjaan per Kategori",
    trend = [],
    recentPekerjaan = [],
    runningItems = [],
    monitoring = {},
    rabWaiting = [],
    deadlineItems = [],
    incompleteItems = [],
    myTasks = [],
    canViewRab = false,
}) {
    return (
        <AppLayout title="Dashboard SIPRAKAR" showPageHeader={false}>
            <div className="mb-6">
                <h1 className="text-2xl font-black text-slate-900 dark:text-white">Dashboard SIPRAKAR</h1>
                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Ringkasan program kerja, RAB, pekerjaan, petugas, checklist, dan deadline yang perlu ditindaklanjuti.
                </p>
            </div>

            <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="RAB Perlu Review" value={summary.direncanakan ?? 0} hint="Diajukan/direvisi" icon={<ClipboardList size={22} />} />
                <StatCard label="Siap Dijadikan Pekerjaan" value={summary.siap_pekerjaan ?? 0} hint="Bisa dibuat pekerjaan" icon={<BriefcaseBusiness size={22} />} />
                <StatCard label="Belum Dilaksanakan" value={summary.belum ?? 0} hint="Pekerjaan belum mulai" icon={<Clock3 size={22} />} />
                <StatCard label="Berjalan" value={summary.berjalan ?? 0} hint="Sedang dikerjakan" icon={<LoaderCircle size={22} />} />
                <StatCard label="Selesai" value={summary.selesai ?? 0} hint="Pekerjaan selesai" icon={<CheckCircle2 size={22} />} />
                <StatCard label="RAB Menunggu Review" value={summary.rab_waiting ?? 0} hint="Perlu setujui/revisi/tolak" icon={<FileWarning size={22} />} />
                <StatCard label="Deadline Dekat/Lewat" value={summary.deadline_alerts ?? 0} hint={`${summary.near_deadline ?? 0} dekat · ${summary.overdue ?? 0} lewat`} icon={<CalendarClock size={22} />} />
                <StatCard label="Tanpa Petugas/Checklist" value={summary.without_setup ?? 0} hint={`${summary.without_assignee ?? 0} petugas · ${summary.without_checklist ?? 0} checklist`} icon={<UserX size={22} />} />
            </section>

            <section className="mt-6">
                <div className="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h2 className="font-bold text-slate-900 dark:text-white">Butuh Tindakan</h2>
                        <p className="text-sm text-slate-500 dark:text-slate-400">Daftar ringkas yang sebaiknya dicek lebih dulu oleh admin.</p>
                    </div>
                </div>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                    <Link href="/program-kerja?status=RAB%20Diajukan" className="page-card block transition hover:border-[#4cceac]"><ClipboardList className="mb-3 text-[#4cceac]" size={22} /><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Program RAB perlu review</p><b className="mt-1 block text-2xl">{monitoring.program_waiting_decision ?? 0}</b></Link>
                    <Link href="/program-kerja?status=Siap%20Dijadikan%20Pekerjaan" className="page-card block transition hover:border-[#4cceac]"><BriefcaseBusiness className="mb-3 text-[#4cceac]" size={22} /><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Program siap jadi pekerjaan</p><b className="mt-1 block text-2xl">{monitoring.program_ready ?? 0}</b></Link>
                    <Link href="/pekerjaan" className="page-card block transition hover:border-[#4cceac]"><UserX className="mb-3 text-[#4cceac]" size={22} /><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Pekerjaan tanpa petugas</p><b className="mt-1 block text-2xl">{monitoring.without_assignee ?? 0}</b></Link>
                    <Link href="/pekerjaan" className="page-card block transition hover:border-[#4cceac]"><ListChecks className="mb-3 text-[#4cceac]" size={22} /><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanpa checklist</p><b className="mt-1 block text-2xl">{monitoring.without_checklist ?? 0}</b></Link>
                    <Link href="/pekerjaan" className="page-card block transition hover:border-[#4cceac]"><CalendarClock className="mb-3 text-[#4cceac]" size={22} /><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Deadline dekat</p><b className="mt-1 block text-2xl">{monitoring.near_deadline ?? 0}</b></Link>
                    {canViewRab ? <Link href="/rab" className="page-card block transition hover:border-[#4cceac]"><FileWarning className="mb-3 text-[#4cceac]" size={22} /><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">RAB menunggu review</p><b className="mt-1 block text-2xl">{monitoring.rab_waiting ?? 0}</b></Link> : <Link href="/pekerjaan" className="page-card block transition hover:border-[#4cceac]"><AlertTriangle className="mb-3 text-red-300" size={22} /><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Melewati deadline</p><b className="mt-1 block text-2xl">{monitoring.overdue ?? 0}</b></Link>}
                </div>
            </section>

            <section className="mt-6 grid min-w-0 gap-6 xl:grid-cols-3">
                <DashboardDonutChart title="Status Pekerjaan" data={statusCounts} centerLabel="pekerjaan" />
                <DashboardBarChart title={barTitle} data={barCounts} />
                <DashboardLineChart title="Tren 6 Bulan: Program & Selesai" data={trend} series={[{ key: "total_program", label: "Total Program", color: "#6870fa" }, { key: "done", label: "Selesai", color: "#4cceac" }]} />
            </section>

            <section className="mt-6 grid gap-6 xl:grid-cols-2">
                <div className="page-card min-w-0">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 className="font-bold">RAB Menunggu Review</h2>
                            <p className="text-sm text-slate-500">RAB yang perlu aksi setujui, minta revisi, atau tolak.</p>
                        </div>
                        <Link href="/rab" className="text-sm font-semibold text-[#4cceac]">Lihat semua</Link>
                    </div>
                    <div className="table-shell">
                        <table className="data-table min-w-[760px] table-fixed">
                            <thead><tr><th className="w-36">Nomor</th><th>Program/Pekerjaan</th><th className="w-36">Cabang</th><th className="w-36 text-right">Nominal</th><th className="w-20">Aksi</th></tr></thead>
                            <tbody>
                                {rabWaiting.map((rab) => <tr key={rab.id}><td className="font-semibold text-[#4cceac]">{rab.nomor_rab}</td><td><div className="truncate font-semibold" title={rabName(rab)}>{rabCode(rab)} · {rabName(rab)}</div><p className="text-xs text-slate-500">Status: {rab.status_rab}</p></td><td>{rabCabang(rab)}</td><td className="table-currency font-semibold">{rupiah(rab.total_rab)}</td><td><Link href={`/rab/${rab.id}`} className="icon-btn" title="Review RAB"><Eye size={15} /></Link></td></tr>)}
                                {rabWaiting.length === 0 && <tr><td colSpan="5" className="text-center text-slate-500">Tidak ada RAB menunggu review.</td></tr>}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="page-card min-w-0">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 className="font-bold">Deadline Dekat / Lewat</h2>
                            <p className="text-sm text-slate-500">Pekerjaan yang target selesainya kurang dari 7 hari atau sudah melewati deadline.</p>
                        </div>
                        <Link href="/pekerjaan" className="text-sm font-semibold text-[#4cceac]">Lihat semua</Link>
                    </div>
                    <div className="max-h-[390px] space-y-3 overflow-y-auto pr-1">
                        {deadlineItems.map((item) => (
                            <Link key={item.id} href={`/pekerjaan/${item.id}`} className="block rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-[#4cceac] dark:border-[#29314b] dark:bg-[#141b2d]">
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div className="min-w-0"><b className="line-clamp-2 text-sm">{item.nama_pekerjaan}</b><p className="mt-1 text-xs text-slate-500">{item.kode_pekerjaan} · {item.cabang?.nama_cabang ?? "-"}</p></div>
                                    <span className={`rounded-full px-2 py-1 text-xs font-black ${isLate(item.target_selesai) ? "bg-red-500/15 text-red-400" : "bg-amber-500/15 text-amber-400"}`}>{isLate(item.target_selesai) ? "Lewat" : "Dekat"}</span>
                                </div>
                                <p className="mt-2 text-xs text-slate-500">Target: {formatDate(item.target_selesai)} · Petugas: {item.petugas?.name ?? "Belum ada"}</p>
                                <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-[#29314b]"><div className="h-2 rounded-full bg-[#4cceac]" style={{ width: `${item.progress}%` }} /></div>
                            </Link>
                        ))}
                        {deadlineItems.length === 0 && <p className="text-sm text-slate-500">Tidak ada pekerjaan yang mendekati atau melewati deadline.</p>}
                    </div>
                </div>
            </section>

            <section className="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <div className="page-card min-w-0">
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 className="font-bold">Pekerjaan Tanpa Petugas / Checklist</h2>
                            <p className="text-sm text-slate-500">Lengkapi petugas dan checklist supaya progress bisa berjalan.</p>
                        </div>
                        <Link href="/pekerjaan" className="shrink-0 text-xs font-bold text-[#4cceac]">Lihat semua</Link>
                    </div>
                    <div className="table-shell">
                        <table className="data-table min-w-[820px] table-fixed">
                            <thead><tr><th className="w-36">Kode</th><th>Pekerjaan</th><th className="w-40">Cabang</th><th className="w-44">Masalah</th><th className="w-32 text-center">Status</th><th className="w-20">Aksi</th></tr></thead>
                            <tbody>
                                {incompleteItems.map((p) => {
                                    const issues = [!p.petugas_id && "Tanpa petugas", Number(p.checklists_count ?? 0) === 0 && "Tanpa checklist"].filter(Boolean).join(" · ");
                                    return <tr key={p.id}><td className="font-semibold text-[#4cceac]">{p.kode_pekerjaan}</td><td><div className="truncate" title={p.nama_pekerjaan}>{p.nama_pekerjaan}</div><p className="text-xs text-slate-500">{p.kategori?.nama_kategori ?? "-"}</p></td><td>{p.cabang?.nama_cabang ?? "-"}</td><td>{issues || "Perlu dicek"}</td><td className="text-center"><StatusBadge value={p.status} /></td><td><Link href={`/pekerjaan/${p.id}`} className="icon-btn" title="Lengkapi"><Eye size={15} /></Link></td></tr>;
                                })}
                                {incompleteItems.length === 0 && <tr><td colSpan="6" className="text-center text-slate-500">Tidak ada pekerjaan tanpa petugas/checklist.</td></tr>}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="page-card min-w-0">
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 className="font-bold">Tugas Saya</h2>
                            <p className="text-sm text-slate-500">Pekerjaan aktif yang ditugaskan ke akun ini.</p>
                        </div>
                        <Link href="/tugas-saya" className="shrink-0 text-xs font-bold text-[#4cceac]">Lihat semua</Link>
                    </div>
                    <div className="max-h-[390px] space-y-3 overflow-y-auto pr-1">
                        {myTasks.map((item) => (
                            <Link href={`/pekerjaan/${item.id}`} key={item.id} className="block min-w-0 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-[#4cceac] dark:border-[#29314b] dark:bg-[#141b2d]">
                                <div className="flex min-w-0 items-start justify-between gap-3">
                                    <b className="line-clamp-2 min-w-0 text-sm leading-snug">{item.nama_pekerjaan}</b>
                                    <span className="shrink-0 text-xs font-bold text-[#4cceac]">{item.progress}%</span>
                                </div>
                                <p className="mt-1 min-w-0 break-words text-xs text-slate-500">Target: {formatDate(item.target_selesai)} · {item.cabang?.nama_cabang ?? "-"}</p>
                                <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-[#29314b]"><div className="h-2 rounded-full bg-[#4cceac]" style={{ width: `${item.progress}%` }} /></div>
                            </Link>
                        ))}
                        {myTasks.length === 0 && <p className="text-sm text-slate-500">Belum ada tugas aktif untuk akun ini.</p>}
                    </div>
                </div>
            </section>

            <section className="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <div className="page-card min-w-0">
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 className="font-bold">Pekerjaan Berjalan</h2>
                            <p className="text-sm text-slate-500">Daftar pekerjaan aktif yang perlu dipantau.</p>
                        </div>
                        <Link href="/pekerjaan?status=Diproses" className="shrink-0 text-xs font-bold text-[#4cceac]">Lihat semua</Link>
                    </div>
                    <div className="max-h-[420px] space-y-3 overflow-y-auto pr-1">
                        {runningItems.map((item) => (
                            <Link href={`/pekerjaan/${item.id}`} key={item.id} className="block min-w-0 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-[#4cceac] dark:border-[#29314b] dark:bg-[#141b2d]">
                                <div className="flex min-w-0 items-start justify-between gap-3">
                                    <b className="line-clamp-2 min-w-0 text-sm leading-snug">{item.nama_pekerjaan}</b>
                                    <span className="shrink-0 text-xs font-bold text-[#4cceac]">{item.progress}%</span>
                                </div>
                                <p className="mt-1 min-w-0 break-words text-xs text-slate-500">Target: {formatDate(item.target_selesai)} · {item.petugas?.name ?? "Belum ada petugas"}</p>
                                <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-[#29314b]"><div className="h-2 rounded-full bg-[#4cceac]" style={{ width: `${item.progress}%` }} /></div>
                            </Link>
                        ))}
                        {runningItems.length === 0 && <p className="text-sm text-slate-500">Tidak ada pekerjaan yang berjalan.</p>}
                    </div>
                </div>

                <div className="page-card min-w-0">
                    <div className="mb-4 flex items-center justify-between"><h2 className="font-bold">Pekerjaan Terbaru</h2><Link href="/pekerjaan" className="text-sm font-semibold text-[#4cceac]">Lihat semua</Link></div>
                    <div className="table-shell"><table className="data-table min-w-[900px] table-fixed"><thead><tr><th className="w-36">Kode</th><th>Pekerjaan</th><th className="w-40">Cabang</th><th className="w-36">Kategori</th><th className="w-36">Progres</th><th className="w-32 text-center">Status</th><th className="w-20">Aksi</th></tr></thead><tbody>{recentPekerjaan.map((p) => <tr key={p.id}><td className="table-nowrap font-medium text-[#4cceac]">{p.kode_pekerjaan}</td><td><div className="truncate" title={p.nama_pekerjaan}>{p.nama_pekerjaan}</div></td><td>{p.cabang?.nama_cabang ?? "-"}</td><td>{p.kategori?.nama_kategori ?? "-"}</td><td><div className="progress-cell"><div className="progress-bar"><div className="h-2 rounded-full bg-[#4cceac]" style={{ width: `${p.progress}%` }} /></div><span className="progress-value">{p.progress}%</span></div></td><td className="text-center"><StatusBadge value={p.status_label ?? p.status} /></td><td><Link href={`/pekerjaan/${p.id}`} className="icon-btn" title="Lihat detail"><Eye size={15} /></Link></td></tr>)}{recentPekerjaan.length === 0 && <tr><td colSpan="7" className="text-center text-slate-500">Belum ada pekerjaan.</td></tr>}</tbody></table></div>
                </div>
            </section>
        </AppLayout>
    );
}
