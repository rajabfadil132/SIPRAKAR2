import { Link, usePage } from "@inertiajs/react";
import Toast from "@/Components/Toast";
import { formatDate } from "@/Utils/date";
import {
    Bell,
    CalendarCheck,
    ChevronDown,
    ChevronRight,
    ClipboardList,
    Database,
    Edit3,
    FileSpreadsheet,
    FileText,
    Gauge,
    Home,
    LogOut,
    Menu,
    Moon,
    Search as SearchIcon,
    Settings,
    ShieldCheck,
    Sun,
    Users,
    Wrench,
    X,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";

const siprakarMenus = [
    { section: true, label: "Menu" },
    { label: "Dashboard", href: "/dashboard", icon: Gauge },

    { section: true, label: "Data" },
    {
        label: "Perencanaan",
        icon: CalendarCheck,
        children: [
            { label: "Program Kerja", href: "/program-kerja", icon: CalendarCheck },
            { label: "Data Pekerjaan", href: "/pekerjaan", icon: ClipboardList },
            { label: "Tugas Saya", href: "/tugas-saya", icon: Wrench },
        ],
    },
    {
        label: "Monitoring",
        icon: Wrench,
        children: [
            { label: "RAB Pekerjaan", href: "/rab", icon: FileSpreadsheet },
            { label: "Laporan & Statistik", href: "/reports", icon: FileText },
            { label: "Arsip Pekerjaan", href: "/pekerjaan/archive", icon: Database },
        ],
    },
];

const systemMenus = [
    { section: true, label: "Administrasi" },
    {
        label: "Pengaturan Sistem",
        icon: Settings,
        children: [
            { label: "Master Data", href: "/master-data", icon: Database },
            { label: "User Management", href: "/users-management", icon: Users },
            { label: "Hak Akses Role", href: "/role-permissions", icon: ShieldCheck },
            { label: "Riwayat Aktivitas", href: "/activity-logs", icon: ClipboardList },
        ],
    },
];

const portalLinks = [
    { key: "siprakar", label: "SIPRAKAR", href: "/siprakar", icon: ClipboardList, area: "siprakar" },
    { key: "system", label: "Pengaturan Sistem", href: "/pengaturan-sistem", icon: Settings, area: "system" },
];

const portalConfig = {
    siprakar: {
        title: "SIPRAKAR",
        subtitle: "Program Kerja & Prasarana",
        description: "Kelola program kerja, pekerjaan, penugasan, RAB, laporan, dan audit SIPRAKAR.",
        menus: siprakarMenus,
        portals: ["system"],
    },
    system: {
        title: "Pengaturan Sistem",
        subtitle: "Master Data & Hak Akses",
        description: "Kelola master data, pengguna, role, hak akses, dan audit sistem.",
        menus: systemMenus,
        portals: ["siprakar"],
    },
};

function getActivePortal(current = "") {
    if (current.startsWith("/master-data") || current.startsWith("/users-management") || current.startsWith("/role-permissions") || current.startsWith("/activity-logs") || current.startsWith("/pengaturan-sistem")) return "system";
    return "siprakar";
}

function createPortalMenu(current = "") {
    const activePortal = getActivePortal(current);
    const config = portalConfig[activePortal] ?? portalConfig.siprakar;
    const portals = config.portals.map((key) => portalLinks.find((item) => item.key === key)).filter(Boolean);

    return [
        ...config.menus,
        ...(portals.length > 0 ? [{ section: true, label: activePortal === "siprakar" ? "Administrasi" : "Portal" }, ...portals] : []),
    ];
}

function removeEmptySections(items, permissions = {}, isAdmin = false, isSuperadmin = false) {
    return items.filter((item, index) => {
        if (!item.section) return canSee(item, permissions, isAdmin, isSuperadmin);
        const nextSectionIndex = items.findIndex((next, nextIndex) => nextIndex > index && next.section);
        const sectionItems = nextSectionIndex === -1 ? items.slice(index + 1) : items.slice(index + 1, nextSectionIndex);
        return sectionItems.some((next) => canSee(next, permissions, isAdmin, isSuperadmin));
    });
}

function canSee(item) {
    if (item.section) return true;
    return true;
}

function isActive(current, item) {
    if (item.href) return current === item.href || current.startsWith(`${item.href}/`);
    return item.children?.some((child) => current === child.href || current.startsWith(`${child.href}/`));
}

const moduleBreadcrumbs = [
    { prefix: "/program-kerja", parent: "SIPRAKAR", label: "Program Kerja", create: "Tambah Program Kerja", edit: "Edit Program Kerja", detail: "Detail Program Kerja" },
    { prefix: "/pekerjaan/archive", parent: "SIPRAKAR", label: "Arsip Pekerjaan" },
    { prefix: "/tugas-saya", parent: "SIPRAKAR", label: "Tugas Saya" },
    { prefix: "/pekerjaan", parent: "SIPRAKAR", label: "Data Pekerjaan", create: "Tambah Pekerjaan", edit: "Edit Pekerjaan", detail: "Detail Pekerjaan" },
    { prefix: "/rab", parent: "SIPRAKAR", label: "RAB Program Kerja", create: "Tambah RAB", edit: "Edit RAB", detail: "Detail RAB" },
    { prefix: "/reports", parent: "SIPRAKAR", label: "Laporan & Statistik" },
    { prefix: "/activity-logs", parent: "Pengaturan Sistem", label: "Riwayat Aktivitas" },
    { prefix: "/master-data", parent: "Pengaturan Sistem", label: "Master Data" },
    { prefix: "/users-management", parent: "Pengaturan Sistem", label: "Manajemen Pengguna", create: "Tambah Pengguna", edit: "Edit Pengguna", detail: "Detail Pengguna" },
    { prefix: "/role-permissions", parent: "Pengaturan Sistem", label: "Role & Hak Akses" },
    { prefix: "/profile", label: "Profil" },
];

function buildBreadcrumbs(current, title) {
    const path = current || "/dashboard";

    if (path === "/") return [{ label: "SIPRAKAR" }];
    if (path === "/siprakar") return [{ label: "SIPRAKAR" }];
    if (path === "/pengaturan-sistem") return [{ label: "Pengaturan Sistem" }];

    if (path.startsWith("/dashboard")) return [{ label: "SIPRAKAR" }, { label: "Dashboard" }];

    const module = moduleBreadcrumbs.find((item) => path === item.prefix || path.startsWith(`${item.prefix}/`));
    if (!module) return [{ label: title || "Halaman" }];

    const rest = path.slice(module.prefix.length).split("/").filter(Boolean);
    const isModuleIndex = rest.length === 0;
    const crumbs = module.parent ? [{ label: module.parent }] : [];

    crumbs.push({ label: module.label, href: isModuleIndex ? undefined : module.prefix });

    if (isModuleIndex) return crumbs;

    if (rest[0] === "create") {
        crumbs.push({ label: module.create || `Tambah ${module.label}` });
    } else if (rest[1] === "edit") {
        crumbs.push({ label: module.edit || `Edit ${module.label}` });
    } else {
        crumbs.push({ label: title && title !== module.label ? title : module.detail || `Detail ${module.label}` });
    }

    return crumbs;
}

function Breadcrumbs({ current, title }) {
    const crumbs = useMemo(() => buildBreadcrumbs(current, title), [current, title]);
    const currentCrumb = crumbs[crumbs.length - 1];
    const parentCrumb = crumbs.length > 1 ? crumbs[crumbs.length - 2] : null;

    return (
        <nav className="min-w-0 flex-1" aria-label="Breadcrumb">
            <ol className="hidden min-w-0 items-center gap-1 text-sm font-bold text-[#a3a3a3] sm:flex">
                {crumbs.map((crumb, index) => {
                    const isLast = index === crumbs.length - 1;
                    const content = (
                        <span className={`flex min-w-0 items-center gap-2 rounded-xl px-2.5 py-1.5 ${isLast ? "text-[#e0e0e0]" : "transition hover:bg-[#1F2A40] hover:text-[#4cceac]"}`}>
                            {index === 0 && <Home size={15} className="shrink-0" />}
                            <span className="truncate">{crumb.label}</span>
                        </span>
                    );

                    return (
                        <li key={`${crumb.label}-${index}`} className="flex min-w-0 items-center gap-1">
                            {index > 0 && <ChevronRight size={15} className="shrink-0 text-[#4cceac]" />}
                            {!isLast && crumb.href ? <Link href={crumb.href} className="min-w-0">{content}</Link> : content}
                        </li>
                    );
                })}
            </ol>
            <div className="min-w-0 sm:hidden">
                <p className="truncate text-sm font-black leading-tight text-[#e0e0e0]">{currentCrumb?.label ?? title}</p>
                {parentCrumb && <p className="mt-0.5 truncate text-[11px] font-bold text-[#4cceac]">{parentCrumb.label}</p>}
            </div>
        </nav>
    );
}

function MenuItem({ item, current, isAdmin, isSuperadmin, permissions, isCollapsed, closeMobile }) {
    const visibleChildren = item.children?.filter((child) => canSee(child, permissions, isAdmin, isSuperadmin)) ?? [];
    const [open, setOpen] = useState(() => isActive(current, item));

    if (!canSee(item, permissions, isAdmin, isSuperadmin)) return null;

    if (item.section) {
        if (isCollapsed) return <div className="my-3 h-px bg-white/10" />;
        return <p className="mb-2 mt-5 px-4 text-[11px] font-bold uppercase tracking-[0.22em] text-[#a3a3a3]">{item.label}</p>;
    }

    const Icon = item.icon;

    if (visibleChildren.length > 0) {
        const active = isActive(current, item);
        const firstChild = visibleChildren[0];

        if (isCollapsed && firstChild?.href) {
            return (
                <Link
                    href={firstChild.href}
                    onClick={closeMobile}
                    title={`${item.label}: ${firstChild.label}`}
                    className={`group flex items-center justify-center rounded-xl px-4 py-3 text-sm transition ${active ? "bg-[#6870fa]/20 text-[#868dfb]" : "text-[#e0e0e0] hover:bg-white/10 hover:text-[#868dfb]"}`}
                >
                    <Icon size={20} className="shrink-0" />
                </Link>
            );
        }

        return (
            <div>
                <button
                    type="button"
                    onClick={() => setOpen(!open)}
                    className={`group flex w-full items-center justify-between rounded-xl px-4 py-3 text-sm transition ${active ? "bg-[#6870fa]/20 text-[#868dfb]" : "text-[#e0e0e0] hover:bg-white/10 hover:text-[#868dfb]"}`}
                    title={item.label}
                >
                    <span className="flex min-w-0 items-center gap-3">
                        <Icon size={20} className="shrink-0" />
                        <span className="truncate font-semibold">{item.label}</span>
                    </span>
                    {open ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
                </button>

                {open && (
                    <div className="ml-5 mt-1 space-y-1 border-l border-white/10 pl-3">
                        {visibleChildren.map((child) => (
                            <MenuItem key={child.href} item={child} current={current} isAdmin={isAdmin} isSuperadmin={isSuperadmin} permissions={permissions} isCollapsed={false} closeMobile={closeMobile} />
                        ))}
                    </div>
                )}
            </div>
        );
    }

    const active = isActive(current, item);
    return (
        <Link
            href={item.href}
            onClick={closeMobile}
            title={item.label}
            className={`group flex items-center justify-between rounded-xl px-4 py-3 text-sm transition ${active ? "bg-[#6870fa]/20 text-[#868dfb]" : "text-[#e0e0e0] hover:bg-white/10 hover:text-[#868dfb]"}`}
        >
            <span className="flex min-w-0 items-center gap-3">
                <Icon size={20} className="shrink-0" />
                {!isCollapsed && <span className="truncate font-semibold">{item.label}</span>}
            </span>
        </Link>
    );
}

function SidebarContent({ current, isAdmin, isSuperadmin, permissions, isCollapsed, closeMobile, onToggleCollapse, onMobileClose }) {
    const activePortal = getActivePortal(current);
    const portal = portalConfig[activePortal] ?? portalConfig.siprakar;
    const menuItems = removeEmptySections(createPortalMenu(current), permissions, isAdmin, isSuperadmin);

    return (
        <div className="flex h-full flex-col bg-[#1F2A40] text-white shadow-2xl">
            <div className={`flex shrink-0 border-b border-[#29314b] ${isCollapsed ? "h-24 flex-col items-center justify-center gap-2 px-2" : "h-20 items-center justify-between gap-3 px-4"}`}>
                <Brand collapsed={isCollapsed} portal={portal} />
                {onToggleCollapse ? (
                    <button
                        type="button"
                        onClick={onToggleCollapse}
                        className={`inline-flex shrink-0 items-center justify-center rounded-xl border border-[#29314b] bg-[#141b2d] text-[#e0e0e0] transition hover:border-[#6870fa] hover:text-[#4cceac] ${isCollapsed ? "h-8 w-8" : "h-10 w-10"}`}
                        aria-label={isCollapsed ? "Buka sidebar" : "Ciutkan sidebar"}
                        aria-expanded={!isCollapsed}
                        title={isCollapsed ? "Buka sidebar" : "Ciutkan sidebar"}
                    >
                        <Menu size={isCollapsed ? 17 : 20} />
                    </button>
                ) : (
                    <button type="button" onClick={onMobileClose} className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-[#29314b] bg-white/10 text-white" aria-label="Tutup menu">
                        <X size={18} />
                    </button>
                )}
            </div>
            <nav className={`app-scrollbar flex-1 space-y-1 overflow-y-auto pb-4 pt-4 ${isCollapsed ? "px-3" : "px-4"}`}>
                {menuItems.map((item, index) => (
                    <MenuItem key={`${item.label}-${item.href ?? index}`} item={item} current={current} isAdmin={isAdmin} isSuperadmin={isSuperadmin} permissions={permissions} isCollapsed={isCollapsed} closeMobile={closeMobile} />
                ))}
            </nav>
        </div>
    );
}

function Brand({ collapsed = false, portal = portalConfig.siprakar }) {
    return (
        <Link
            href="/"
            className={`flex min-w-0 shrink-0 items-center rounded-2xl transition hover:opacity-90 ${collapsed ? "justify-center" : "gap-3"}`}
            title="Beranda SIPRAKAR"
        >
            <img
                src="/images/siprakar-logo.svg"
                alt="Logo SIPRAKAR"
                className={`${collapsed ? "h-12 w-12" : "h-12 w-12"} shrink-0 object-contain drop-shadow-lg`}
            />
            {!collapsed && (
                <div className="min-w-0">
                    <div className="truncate text-xl font-black tracking-wide text-[#e0e0e0]">{portal.title}</div>
                    <div className="truncate text-[11px] font-semibold text-[#4cceac]">{portal.subtitle}</div>
                </div>
            )}
        </Link>
    );
}

function ProfileMenu({ auth, initials }) {
    const [open, setOpen] = useState(false);
    const roleLabel = [auth?.user?.role?.nama_role, auth?.user?.role_category?.name || auth?.user?.roleCategory?.name].filter(Boolean).join(" · ") || "User";

    return (
        <div className="relative">
            <button type="button" onClick={() => setOpen((value) => !value)} className="flex items-center gap-3 rounded-xl bg-[#1F2A40] px-3 py-2 text-left transition hover:ring-2 hover:ring-[#6870fa]/40" aria-haspopup="menu" aria-expanded={open}>
                <div className="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#6870fa] text-xs font-black text-white">{initials}</div>
                <div className="hidden min-w-0 leading-tight md:block">
                    <b className="block max-w-40 truncate text-sm text-[#e0e0e0]">{auth?.user?.name ?? "User"}</b>
                    <span className="block text-xs font-semibold text-[#4cceac]">{roleLabel}</span>
                </div>
                <ChevronDown size={16} className="hidden text-[#a3a3a3] sm:block" />
            </button>

            {open && (
                <>
                    <button type="button" className="fixed inset-0 z-40 cursor-default" aria-label="Tutup menu profil" onClick={() => setOpen(false)} />
                    <div className="absolute right-0 z-50 mt-3 w-72 overflow-hidden rounded-2xl border border-[#29314b] bg-[#1F2A40] text-[#e0e0e0] shadow-2xl" role="menu">
                        <div className="border-b border-[#29314b] px-4 py-4">
                            <div className="flex items-center gap-3">
                                <div className="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#6870fa] text-sm font-black text-white">{initials}</div>
                                <div className="min-w-0">
                                    <b className="block truncate text-base">{auth?.user?.name ?? "User"}</b>
                                    <p className="truncate text-xs text-[#a3a3a3]">{auth?.user?.email ?? "-"}</p>
                                </div>
                            </div>
                        </div>

                        <div className="p-2">
                            <Link href="/profile" className="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition hover:bg-[#141b2d] hover:text-[#4cceac]" onClick={() => setOpen(false)}>
                                <Edit3 size={17} />
                                Edit Profil
                            </Link>
                            <Link href="/logout" method="post" as="button" className="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-red-300 transition hover:bg-red-500/10">
                                <LogOut size={17} />
                                Keluar
                            </Link>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}

function ThemeToggle({ theme, setTheme }) {
    const isLight = theme === "light";

    const changeTheme = () => {
        const nextTheme = isLight ? "dark" : "light";
        setTheme(nextTheme);
        if (typeof window !== "undefined") window.localStorage.setItem("siprakar-theme-light-default", nextTheme);
    };

    return (
        <button
            type="button"
            onClick={changeTheme}
            className={`inline-flex h-10 w-10 items-center justify-center rounded-xl border transition ${isLight ? "border-slate-200 bg-white text-slate-700 hover:border-[#d61a52] hover:text-[#d61a52]" : "border-white/10 bg-white/10 text-[#e0e0e0] hover:border-[#d61a52] hover:text-white"}`}
            title={isLight ? "Gunakan mode dark" : "Gunakan mode light"}
            aria-label={isLight ? "Gunakan mode dark" : "Gunakan mode light"}
        >
            {isLight ? <Moon size={18} /> : <Sun size={18} />}
        </button>
    );
}

const notificationKey = (item) => item.notification_id ?? [item.type, item.id, item.time ?? item.status ?? item.progress ?? ""].join("-");

const notificationTone = (type = "") => {
    const value = type.toLowerCase();
    if (value.includes("deadline")) return "bg-red-500/15 text-red-300";
    if (value.includes("selesai")) return "bg-emerald-500/15 text-emerald-300";
    if (value.includes("diperbarui")) return "bg-amber-500/15 text-amber-300";
    if (value.includes("diajukan")) return "bg-sky-500/15 text-sky-300";
    if (value.includes("ditolak")) return "bg-red-500/15 text-red-300";
    return "bg-indigo-500/15 text-indigo-300";
};

function NotificationMenu({ notifications }) {
    const [open, setOpen] = useState(false);
    const [readIds, setReadIds] = useState([]);
    const items = notifications?.items ?? [];
    const readSet = useMemo(() => new Set(readIds), [readIds]);
    const annotatedItems = useMemo(
        () => items.map((item) => ({ ...item, notificationKey: notificationKey(item), isRead: item.is_read || readSet.has(notificationKey(item)) })),
        [items, readSet]
    );
    const unreadCount = annotatedItems.filter((item) => !item.isRead).length;

    const markAsRead = (item) => {
        const key = notificationKey(item);
        setReadIds((current) => (current.includes(key) ? current : [...current, key]));
        if (item.notification_id && typeof window !== "undefined") {
            window.axios?.post(`/notifications/${item.notification_id}/read`).catch(() => {});
        }
    };

    return (
        <div className="relative">
            <button type="button" onClick={() => setOpen((value) => !value)} className="relative rounded-xl bg-[#1F2A40] p-2 text-[#e0e0e0] transition hover:text-[#4cceac]" title="Notifikasi">
                <Bell size={20} />
                {unreadCount > 0 && <span className="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-[#db4f4a] px-1 text-[10px] font-bold text-white">{unreadCount > 9 ? "9+" : unreadCount}</span>}
            </button>
            {open && (
                <>
                    <button type="button" className="fixed inset-0 z-40 cursor-default" aria-label="Tutup notifikasi" onClick={() => setOpen(false)} />
                    <div className="absolute right-0 z-50 mt-3 w-96 max-w-[calc(100vw-1rem)] overflow-hidden rounded-2xl border border-[#29314b] bg-[#1F2A40] shadow-2xl">
                        <div className="border-b border-[#29314b] px-4 py-3">
                            <b>Notifikasi</b>
                            <p className="text-xs text-[#a3a3a3]">Pekerjaan, RAB, tenggat, dan tindak lanjut terbaru.</p>
                        </div>
                        <div className="app-scrollbar max-h-96 overflow-y-auto p-2">
                            {items.length === 0 && <p className="p-4 text-sm text-[#a3a3a3]">Tidak ada notifikasi aktif.</p>}
                            {annotatedItems.map((item) => (
                                <Link key={item.notificationKey} href={item.href ?? `/pekerjaan/${item.id}`} className="relative block rounded-xl p-3 pr-8 text-sm hover:bg-[#141b2d]" onClick={() => { markAsRead(item); setOpen(false); }}>
                                    {!item.isRead && <span className="absolute right-3 top-3 h-2.5 w-2.5 rounded-full bg-[#db4f4a] shadow-[0_0_0_3px_rgba(219,79,74,0.16)]" aria-label="Belum dibaca" />}
                                    <div className="mb-1 flex items-start justify-between gap-3">
                                        <span className={`rounded-lg px-2 py-0.5 text-[11px] font-bold ${notificationTone(item.type)}`}>{item.type}</span>
                                        {item.progress !== undefined && item.progress !== null && <span className="shrink-0 text-xs font-bold text-[#4cceac]">{item.progress}%</span>}
                                    </div>
                                    <b className="line-clamp-2 text-[#e0e0e0]">{item.title}</b>
                                    <p className="mt-1 text-xs text-[#a3a3a3]">{item.message}</p>
                                    <p className="mt-1 text-xs text-[#a3a3a3]">{item.target_selesai ? `Target: ${formatDate(item.target_selesai)} · ` : ""}{item.cabang ?? "-"} · {item.status}</p>
                                    {item.whatsapp_href && (
                                        <button
                                            type="button"
                                            className="mt-3 rounded-lg bg-emerald-500/15 px-3 py-1.5 text-xs font-black text-emerald-300 hover:bg-emerald-500/25"
                                            onClick={(event) => {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                window.open(item.whatsapp_href, "_blank", "noopener,noreferrer");
                                                markAsRead(item);
                                            }}
                                        >
                                            Kirim template WhatsApp
                                        </button>
                                    )}
                                </Link>
                            ))}
                        </div>
                        <Link href="/pekerjaan" className="block border-t border-[#29314b] px-4 py-3 text-center text-sm font-bold text-[#4cceac]" onClick={() => setOpen(false)}>
                            Lihat Pekerjaan
                        </Link>
                    </div>
                </>
            )}
        </div>
    );
}

export default function AppLayout({ title = "Dashboard", children, showPageHeader = true }) {
    const { auth, notifications } = usePage().props;
    const permissions = auth?.permissions ?? {};
    const current = typeof window !== "undefined" ? window.location.pathname : "";
    const activePortal = getActivePortal(current);
    const portal = portalConfig[activePortal] ?? portalConfig.siprakar;
    const isAdmin = Boolean(auth?.isAdmin);
    const isSuperadmin = Boolean(auth?.isSuperadmin);
    const [isCollapsed, setIsCollapsed] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [theme, setTheme] = useState(() => (typeof window === "undefined" ? "light" : window.localStorage.getItem("siprakar-theme-light-default") || "light"));

    useEffect(() => {
        if (typeof document === "undefined") return;
        document.documentElement.dataset.siprakarTheme = theme;
        if (typeof window !== "undefined") window.localStorage.setItem("siprakar-theme-light-default", theme);
    }, [theme]);

    const initials = useMemo(() => (auth?.user?.name ?? "User").split(" ").filter(Boolean).map((part) => part[0]).join("").slice(0, 2).toUpperCase(), [auth?.user?.name]);
    const sidebarWidth = isCollapsed ? "lg:w-20" : "lg:w-72";
    const mainPadding = isCollapsed ? "lg:pl-20" : "lg:pl-72";

    return (
        <div className={`siprakar-admin-theme min-h-screen ${theme === "light" ? "light-mode bg-[#f5f7fb] text-slate-900" : "bg-[#141b2d] text-[#e0e0e0]"}`}>
            <Toast />
            <aside className={`fixed inset-y-0 left-0 z-30 hidden ${sidebarWidth} lg:block`}>
                <SidebarContent current={current} isAdmin={isAdmin} isSuperadmin={isSuperadmin} permissions={permissions} isCollapsed={isCollapsed} onToggleCollapse={() => setIsCollapsed((value) => !value)} />
            </aside>

            {mobileOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <button type="button" className="absolute inset-0 bg-black/60" onClick={() => setMobileOpen(false)} aria-label="Tutup menu" />
                    <aside className="relative h-full w-72">
                        <SidebarContent current={current} isAdmin={isAdmin} isSuperadmin={isSuperadmin} permissions={permissions} isCollapsed={false} closeMobile={() => setMobileOpen(false)} onMobileClose={() => setMobileOpen(false)} />
                    </aside>
                </div>
            )}

            <main className={`min-h-screen transition-all ${mainPadding}`}>
                <header className="sticky top-0 z-20 flex h-20 items-center justify-between gap-3 border-b border-[#29314b] bg-[#141b2d]/95 px-4 backdrop-blur md:px-6">
                    <div className="flex min-w-0 flex-1 items-center gap-3">
                        <button type="button" onClick={() => setMobileOpen(true)} className="rounded-xl border border-[#29314b] bg-[#1F2A40] p-2 text-[#e0e0e0] lg:hidden" aria-label="Buka sidebar">
                            <Menu size={20} />
                        </button>
                        <Breadcrumbs current={current} title={title} />
                    </div>

                    <div className="flex shrink-0 items-center gap-2 md:gap-3">
                        <ThemeToggle theme={theme} setTheme={setTheme} />
                        {permissions["notifications.view"] && <NotificationMenu notifications={notifications} />}
                        <ProfileMenu auth={auth} initials={initials} />
                    </div>
                </header>

                <section className="app-content px-4 py-6 md:px-6 xl:px-8">
                    {showPageHeader && (
                        <div className="mb-6 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h1 className="text-3xl font-black uppercase tracking-wide text-[#e0e0e0]">{title}</h1>
                                <p className="mt-1 text-sm font-semibold text-[#4cceac]">{portal.description}</p>
                            </div>
                        </div>
                    )}
                    {children}
                </section>
            </main>
        </div>
    );
}
