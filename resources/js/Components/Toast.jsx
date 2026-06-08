import { usePage } from "@inertiajs/react";
import { AlertCircle, CheckCircle2, Info, X } from "lucide-react";
import { useEffect, useMemo, useState } from "react";

const firstErrorMessage = (errors = {}) => {
    const first = Object.values(errors)[0];
    if (Array.isArray(first)) return first[0];
    return first;
};

const toastConfig = {
    success: {
        icon: CheckCircle2,
        label: "Berhasil",
        className: "border-emerald-500/30 bg-emerald-950/95 text-emerald-50 shadow-emerald-950/30",
        iconClassName: "bg-emerald-400/15 text-emerald-200",
    },
    warning: {
        icon: AlertCircle,
        label: "Perhatian",
        className: "border-amber-500/35 bg-amber-950/95 text-amber-50 shadow-amber-950/30",
        iconClassName: "bg-amber-400/15 text-amber-200",
    },
    error: {
        icon: AlertCircle,
        label: "Gagal",
        className: "border-red-500/35 bg-red-950/95 text-red-50 shadow-red-950/30",
        iconClassName: "bg-red-400/15 text-red-200",
    },
    info: {
        icon: Info,
        label: "Info",
        className: "border-sky-500/35 bg-sky-950/95 text-sky-50 shadow-sky-950/30",
        iconClassName: "bg-sky-400/15 text-sky-200",
    },
};

const statusMessage = (status) => ({
    "verification-link-sent": "Link verifikasi email sudah dikirim.",
}[status] ?? status);

function buildToast(flash = {}, errors = {}) {
    if (flash?.success) return { type: "success", message: flash.success };
    if (flash?.error) return { type: "error", message: flash.error };
    if (flash?.warning) return { type: "warning", message: flash.warning };
    if (flash?.status) return { type: "info", message: statusMessage(flash.status) };

    if (Object.keys(errors ?? {}).length > 0) {
        const first = firstErrorMessage(errors);
        return {
            type: "error",
            message: first ? `Error validasi: ${first}` : "Error validasi. Periksa kembali data yang wajib diisi.",
        };
    }

    return null;
}

export default function Toast() {
    const { flash = {}, errors = {} } = usePage().props;
    const toast = useMemo(() => buildToast(flash, errors), [flash, errors]);
    const [visible, setVisible] = useState(Boolean(toast));

    useEffect(() => {
        setVisible(Boolean(toast));
        if (!toast) return undefined;

        const timer = setTimeout(() => setVisible(false), 4600);
        return () => clearTimeout(timer);
    }, [toast]);

    if (!visible || !toast?.message) return null;

    const config = toastConfig[toast.type] ?? toastConfig.info;
    const Icon = config.icon;

    return (
        <div className="pointer-events-none fixed right-3 top-3 z-[90] w-[calc(100vw-1.5rem)] max-w-md sm:right-5 sm:top-5 sm:w-96">
            <div className={`pointer-events-auto w-full rounded-2xl border px-4 py-3 text-sm shadow-2xl ${config.className}`} role="status" aria-live="polite">
                <div className="flex items-start gap-3">
                    <span className={`grid h-9 w-9 shrink-0 place-items-center rounded-xl ${config.iconClassName}`}>
                        <Icon size={19} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="font-black leading-tight">{config.label}</p>
                        <p className="mt-0.5 break-words font-semibold leading-snug opacity-90">{toast.message}</p>
                    </div>
                    <button type="button" onClick={() => setVisible(false)} className="rounded-lg p-1 opacity-70 transition hover:bg-white/10 hover:opacity-100" aria-label="Tutup toast">
                        <X size={16} />
                    </button>
                </div>
            </div>
        </div>
    );
}
