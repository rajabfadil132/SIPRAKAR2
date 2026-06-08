import Toast from "@/Components/Toast";

export default function GuestLayout({ children, title = 'SIPRAKAR', subtitle = 'Sistem Management Program Kerja Sarana Prasarana' }) {
    return (
        <div className="siprakar-auth-theme min-h-screen bg-slate-50 px-4 py-8 text-slate-900 sm:flex sm:items-center sm:justify-center">
            <Toast />
            <div className="pointer-events-none fixed inset-0 opacity-60 [background:radial-gradient(circle_at_top_left,#4cceac33,transparent_35%),radial-gradient(circle_at_bottom_right,#6870fa33,transparent_35%)]" />
            <div className="relative w-full max-w-2xl rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-200/80">
                <div className="mb-6 text-center">
                    <img src="/images/siprakar-logo.svg" alt="Logo SIPRAKAR" className="mx-auto h-24 w-24 object-contain drop-shadow-xl" />
                    <h1 className="mt-3 text-2xl font-black tracking-wide text-slate-900">{title}</h1>
                    <p className="mx-auto mt-1 max-w-md text-sm font-semibold text-[#4cceac]">{subtitle}</p>
                </div>
                {children}
            </div>
        </div>
    );
}
