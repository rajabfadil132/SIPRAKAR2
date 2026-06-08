import { ArrowDown, ArrowUp, CircleAlert, Minus } from "lucide-react";

const config = {
    Rendah: {
        label: "Rendah",
        icon: ArrowDown,
        className: "bg-emerald-500/15 text-emerald-300 ring-emerald-500/25",
    },
    Sedang: {
        label: "Sedang",
        icon: Minus,
        className: "bg-sky-500/15 text-sky-300 ring-sky-500/25",
    },
    Tinggi: {
        label: "Tinggi",
        icon: ArrowUp,
        className: "bg-amber-500/15 text-amber-300 ring-amber-500/25",
    },
    Mendesak: {
        label: "Mendesak",
        icon: CircleAlert,
        className: "bg-red-500/15 text-red-300 ring-red-500/25",
    },
};

export default function PriorityBadge({ value }) {
    const item = config[value] ?? config.Sedang;
    const Icon = item.icon;

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-black ring-1 ${item.className}`} title={`Prioritas ${item.label}`}>
            <Icon size={14} strokeWidth={2.6} />
            {item.label}
        </span>
    );
}
