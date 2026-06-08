const map = {
  Selesai: 'bg-emerald-500/15 text-emerald-300',
  'Menunggu Verifikasi': 'bg-amber-500/15 text-amber-300',
  Diterima: 'bg-sky-500/15 text-sky-300',
  Pending: 'bg-amber-500/15 text-amber-300',
  Diproses: 'bg-indigo-500/15 text-indigo-300',
  Ditolak: 'bg-red-500/15 text-red-300',
  'Dijadikan Program Kerja': 'bg-purple-500/15 text-purple-300',
  'Dijadikan Pekerjaan': 'bg-purple-500/15 text-purple-300',
  'Sedang berjalan': 'bg-sky-500/15 text-sky-300',
  'Belum dilaksanakan': 'bg-slate-500/15 text-slate-300',
  'Belum Diproses': 'bg-slate-500/15 text-slate-300',
  Tertunda: 'bg-amber-500/15 text-amber-300',
  Dibatalkan: 'bg-red-500/15 text-red-300',
  Terlambat: 'bg-red-500/15 text-red-300',
  Draft: 'bg-slate-500/15 text-slate-300',
  Direncanakan: 'bg-indigo-500/15 text-indigo-300',
  Disetujui: 'bg-emerald-500/15 text-emerald-300',
  'RAB Diajukan': 'bg-amber-500/15 text-amber-300',
  'RAB Direvisi': 'bg-purple-500/15 text-purple-300',
  'RAB Disetujui': 'bg-emerald-500/15 text-emerald-300',
  'Siap Dijadikan Pekerjaan': 'bg-sky-500/15 text-sky-300',
  Diajukan: 'bg-amber-500/15 text-amber-300',
  Direvisi: 'bg-purple-500/15 text-purple-300',
  Berjalan: 'bg-sky-500/15 text-sky-300',
  Progres: 'bg-indigo-500/15 text-indigo-300',
  'Pekerjaan Telah Diselesaikan': 'bg-emerald-500/15 text-emerald-300',
};

const labels = {
  Pending: 'Menunggu Verifikasi',
  Progres: 'Sedang Diproses',
  Diproses: 'Sedang Diproses',
  'Sedang berjalan': 'Sedang Berjalan',
  'Belum dilaksanakan': 'Belum Dilaksanakan',
  'Pekerjaan Telah Diselesaikan': 'Selesai',
};

export default function StatusBadge({ value }) {
  const label = labels[value] ?? value ?? '-';
  return <span className={`badge ${map[value] ?? map[label] ?? 'bg-slate-500/15 text-slate-300'}`}>{label}</span>;
}
