const chartPalette = ["#d61a52", "#2563eb", "#14b8a6", "#f59e0b", "#22c55e", "#ef4444", "#8b5cf6", "#f97316"];

const toNumber = (value) => Number(value ?? 0);
const percent = (value, total) => (total > 0 ? Math.round((toNumber(value) / total) * 100) : 0);
const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

function getFiveYAxisTicks(rawValue) {
    const safeMax = Math.max(1, toNumber(rawValue));
    const targetStep = Math.max(5, safeMax / 4);
    const magnitude = 10 ** Math.floor(Math.log10(targetStep));
    const candidates = [1, 2, 2.5, 5, 10].map((factor) => factor * magnitude);
    const step = Math.max(5, candidates.find((candidate) => candidate >= targetStep) ?? 10 * magnitude);

    return Array.from({ length: 5 }, (_, index) => index * step);
}

function smoothPath(points) {
    if (!points.length) return "";
    if (points.length === 1) return `M ${points[0][0]} ${points[0][1]}`;

    const commands = [`M ${points[0][0]} ${points[0][1]}`];
    for (let index = 0; index < points.length - 1; index += 1) {
        const current = points[index];
        const next = points[index + 1];
        const midX = (current[0] + next[0]) / 2;
        commands.push(`C ${midX} ${current[1]}, ${midX} ${next[1]}, ${next[0]} ${next[1]}`);
    }
    return commands.join(" ");
}

function EmptyChart({ children }) {
    return <div className="page-card theme-chart-empty flex h-[230px] items-center justify-center text-center text-sm font-semibold">{children}</div>;
}

function ChartTitle({ title, as = "h3" }) {
    const Component = as;
    return <Component className="chart-title mb-3 truncate text-sm font-black" title={title}>{title}</Component>;
}

export function InteractiveLineChart({ data = [], series = [], height = 280, empty = "Belum ada data untuk ditampilkan." }) {
    if (!data.length || !series.length) {
        return <p className="chart-muted text-sm">{empty}</p>;
    }

    const width = 900;
    const padding = { top: 24, right: 28, bottom: 50, left: 46 };
    const innerWidth = width - padding.left - padding.right;
    const innerHeight = height - padding.top - padding.bottom;
    const rawMax = Math.max(1, ...data.flatMap((row) => series.map((item) => toNumber(row[item.key]))));
    const ySteps = getFiveYAxisTicks(rawMax);
    const max = ySteps[ySteps.length - 1];
    const x = (index) => padding.left + (index * innerWidth) / Math.max(1, data.length - 1);
    const y = (value) => padding.top + innerHeight - (clamp(toNumber(value), 0, max) / max) * innerHeight;

    return (
        <div className="dashboard-chart min-w-0 overflow-hidden">
            <svg viewBox={`0 0 ${width} ${height}`} className="theme-chart-svg block h-auto w-full max-w-full" role="img" aria-label="Grafik garis" preserveAspectRatio="none">
                <defs>
                    <clipPath id="line-chart-clip">
                        <rect x={padding.left} y={padding.top - 12} width={innerWidth} height={innerHeight + 24} rx="8" />
                    </clipPath>
                </defs>

                {ySteps.map((value) => {
                    const yy = y(value);
                    return (
                        <g key={value}>
                            <line x1={padding.left} x2={width - padding.right} y1={yy} y2={yy} className="chart-grid-line" strokeWidth="1" vectorEffect="non-scaling-stroke" />
                            <text x={10} y={yy + 4} className="chart-axis-label text-[11px]">{value}</text>
                        </g>
                    );
                })}

                <g clipPath="url(#line-chart-clip)">
                    {series.map((item, seriesIndex) => {
                        const color = item.color ?? chartPalette[seriesIndex % chartPalette.length];
                        const itemPoints = data.map((row, index) => [x(index), y(row[item.key])]);
                        return (
                            <path key={item.key} d={smoothPath(itemPoints)} fill="none" stroke={color} strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" vectorEffect="non-scaling-stroke" />
                        );
                    })}
                </g>

                {data.map((row, index) => (
                    <g key={row.month ?? row.label ?? index}>
                        {series.map((item, seriesIndex) => {
                            const value = toNumber(row[item.key]);
                            const color = item.color ?? chartPalette[seriesIndex % chartPalette.length];
                            const label = row.month ?? row.label ?? `Data ${index + 1}`;
                            return (
                                <circle key={`${item.key}-${index}`} cx={x(index)} cy={y(value)} r="5.5" fill={color} className="chart-point cursor-pointer transition hover:opacity-80" vectorEffect="non-scaling-stroke">
                                    <title>{`${item.label}: ${value} data pada ${label}`}</title>
                                </circle>
                            );
                        })}
                        <text x={x(index)} y={height - 20} textAnchor="middle" className="chart-axis-label text-[10px] font-semibold">
                            {(row.monthShort ?? row.month ?? row.label ?? "").toString().slice(0, 4)}
                        </text>
                    </g>
                ))}
            </svg>
            <div className="chart-legend mt-3 flex min-w-0 flex-wrap gap-3 text-xs">
                {series.map((item, index) => (
                    <span key={item.key} className="inline-flex min-w-0 items-center gap-1.5">
                        <span className="inline-block h-2.5 w-2.5 shrink-0 rounded-full" style={{ backgroundColor: item.color ?? chartPalette[index % chartPalette.length] }} />
                        <span className="truncate">{item.label}</span>
                    </span>
                ))}
            </div>
        </div>
    );
}

export function PieChart({ data = [], title = "Distribusi", empty = "Belum ada data untuk ditampilkan." }) {
    const rows = data.filter((item) => toNumber(item.total) > 0);
    const total = rows.reduce((sum, item) => sum + toNumber(item.total), 0);

    if (!rows.length || total === 0) {
        return <p className="chart-muted text-sm">{empty}</p>;
    }

    const size = 210;
    const center = size / 2;
    const radius = 78;
    let currentAngle = -90;

    const polar = (angle) => {
        const rad = (Math.PI / 180) * angle;
        return [center + radius * Math.cos(rad), center + radius * Math.sin(rad)];
    };

    const slices = rows.map((item, index) => {
        const value = toNumber(item.total);
        const angle = (value / total) * 360;
        const start = currentAngle;
        const end = currentAngle + angle;
        currentAngle = end;
        const [x1, y1] = polar(start);
        const [x2, y2] = polar(end);
        const largeArc = angle > 180 ? 1 : 0;
        const path = `M ${center} ${center} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2} Z`;
        return { ...item, value, path, color: item.color ?? chartPalette[index % chartPalette.length] };
    });

    return (
        <div className="page-card theme-chart-card min-w-0 overflow-hidden">
            <ChartTitle title={title} as="h4" />
            <div className="grid min-w-0 gap-4 sm:grid-cols-[210px_minmax(0,1fr)] sm:items-center">
                <svg viewBox={`0 0 ${size} ${size}`} className="theme-chart-svg mx-auto h-48 w-48 max-w-full" role="img" aria-label={title}>
                    <circle cx={center} cy={center} r={radius + 8} className="chart-donut-track" />
                    {slices.map((slice) => (
                        <path key={slice.label} d={slice.path} fill={slice.color} className="cursor-pointer transition hover:opacity-80">
                            <title>{`${slice.label}: ${slice.value} data (${percent(slice.value, total)}%)`}</title>
                        </path>
                    ))}
                    <circle cx={center} cy={center} r="44" className="chart-donut-hole" />
                    <text x={center} y={center - 4} textAnchor="middle" className="chart-total-label text-xl font-black">{total}</text>
                    <text x={center} y={center + 15} textAnchor="middle" className="chart-axis-label text-[11px]">total</text>
                </svg>
                <div className="min-w-0 space-y-2">
                    {slices.map((slice) => (
                        <div key={slice.label} className="chart-legend-item flex min-w-0 items-center justify-between gap-3 rounded-xl border px-3 py-2 text-sm" title={`${slice.label}: ${slice.value} data (${percent(slice.value, total)}%)`}>
                            <span className="flex min-w-0 items-center gap-2">
                                <span className="h-3 w-3 shrink-0 rounded-full" style={{ backgroundColor: slice.color }} />
                                <span className="truncate font-semibold">{slice.label}</span>
                            </span>
                            <b className="chart-total-value shrink-0">{slice.value} <span className="chart-muted text-xs">({percent(slice.value, total)}%)</span></b>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

export function DashboardDonutChart({ title = "Distribusi", data = [], empty = "Belum ada data untuk ditampilkan.", centerLabel = "total" }) {
    const rows = data.filter((item) => toNumber(item.total) > 0);
    const total = rows.reduce((sum, item) => sum + toNumber(item.total), 0);

    if (!rows.length || total === 0) {
        return <EmptyChart>{empty}</EmptyChart>;
    }

    const size = 220;
    const center = size / 2;
    const radius = 78;
    let currentAngle = -90;

    const polar = (angle) => {
        const rad = (Math.PI / 180) * angle;
        return [center + radius * Math.cos(rad), center + radius * Math.sin(rad)];
    };

    const slices = rows.map((item, index) => {
        const value = toNumber(item.total);
        const angle = (value / total) * 360;
        const start = currentAngle;
        const end = currentAngle + angle;
        currentAngle = end;
        const [x1, y1] = polar(start);
        const [x2, y2] = polar(end);
        const largeArc = angle > 180 ? 1 : 0;
        const path = `M ${center} ${center} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2} Z`;
        return { ...item, value, path, color: item.color ?? chartPalette[index % chartPalette.length] };
    });

    return (
        <div className="page-card theme-chart-card min-w-0 overflow-hidden">
            <ChartTitle title={title} />
            <svg viewBox={`0 0 ${size} ${size}`} className="theme-chart-svg mx-auto block h-[210px] w-full max-w-[260px]" role="img" aria-label={title}>
                <circle cx={center} cy={center} r={radius + 8} className="chart-donut-track" />
                {slices.map((slice) => (
                    <path key={slice.label} d={slice.path} fill={slice.color} className="cursor-pointer transition hover:opacity-80">
                        <title>{`${slice.label}: ${slice.value} data (${percent(slice.value, total)}%)`}</title>
                    </path>
                ))}
                <circle cx={center} cy={center} r="49" className="chart-donut-hole" />
                <text x={center} y={center - 3} textAnchor="middle" className="chart-total-label text-2xl font-black">{total}</text>
                <text x={center} y={center + 18} textAnchor="middle" className="chart-axis-label text-[11px] font-bold">{centerLabel}</text>
            </svg>
            <div className="chart-legend mt-2 flex min-w-0 flex-wrap justify-center gap-x-3 gap-y-2 text-[11px] font-semibold">
                {slices.map((slice) => (
                    <span key={slice.label} className="inline-flex min-w-0 items-center gap-1.5" title={`${slice.label}: ${slice.value} data (${percent(slice.value, total)}%)`}>
                        <span className="h-2.5 w-2.5 shrink-0 rounded-full" style={{ backgroundColor: slice.color }} />
                        <span className="max-w-[120px] truncate">{slice.label}</span>
                    </span>
                ))}
            </div>
        </div>
    );
}

export function DashboardBarChart({ title = "Data", data = [], empty = "Belum ada data untuk ditampilkan.", color = "#f8bd38" }) {
    const rows = data.filter((item) => toNumber(item.total) > 0);
    const max = Math.max(1, ...rows.map((item) => toNumber(item.total)));

    if (!rows.length) {
        return <EmptyChart>{empty}</EmptyChart>;
    }

    return (
        <div className="page-card theme-chart-card min-w-0 overflow-hidden">
            <ChartTitle title={title} />
            <div className="space-y-3">
                {rows.map((row, index) => {
                    const value = toNumber(row.total);
                    const width = Math.max(7, (value / max) * 100);
                    const barColor = row.color ?? (index % 4 === 0 ? "#3b82f6" : index % 4 === 1 ? "#22c55e" : index % 4 === 2 ? color : "#fb7185");
                    return (
                        <div key={row.label} className="grid min-w-0 grid-cols-[90px_minmax(0,1fr)] items-center gap-3" title={`${row.label}: ${value} data`}>
                            <span className="chart-label truncate text-xs font-semibold">{row.label}</span>
                            <div className="chart-bar-track min-w-0 overflow-hidden rounded">
                                <div className="flex h-8 items-center justify-end rounded px-2 text-xs font-black text-white" style={{ width: `${width}%`, backgroundColor: barColor }}>
                                    {value}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export function DashboardLineChart({ title = "Tren 6 Bulan Terakhir", data = [], valueKey = "total", series = null, color = "#ff5c7a", empty = "Belum ada data untuk ditampilkan." }) {
    if (!data.length) {
        return <EmptyChart>{empty}</EmptyChart>;
    }

    const chartSeries = series?.length ? series : [{ key: valueKey, label: "Total", color }];
    const width = 520;
    const height = 230;
    const padding = { top: 20, right: 22, bottom: 38, left: 38 };
    const innerWidth = width - padding.left - padding.right;
    const innerHeight = height - padding.top - padding.bottom;
    const rawMax = Math.max(1, ...data.flatMap((row) => chartSeries.map((item) => toNumber(row[item.key]))));
    const ySteps = getFiveYAxisTicks(rawMax);
    const max = ySteps[ySteps.length - 1];
    const x = (index) => padding.left + (index * innerWidth) / Math.max(1, data.length - 1);
    const y = (value) => padding.top + innerHeight - (clamp(toNumber(value), 0, max) / max) * innerHeight;

    return (
        <div className="page-card theme-chart-card min-w-0 overflow-hidden">
            <ChartTitle title={title} />
            <svg viewBox={`0 0 ${width} ${height}`} className="theme-chart-svg block h-[230px] w-full max-w-full" role="img" aria-label={title} preserveAspectRatio="none">
                {ySteps.map((value) => {
                    const yy = y(value);
                    return (
                        <g key={value}>
                            <line x1={padding.left} x2={width - padding.right} y1={yy} y2={yy} className="chart-grid-line" strokeWidth="1" vectorEffect="non-scaling-stroke" />
                            <text x="6" y={yy + 4} className="chart-axis-label text-[10px] font-semibold">{value}</text>
                        </g>
                    );
                })}

                {chartSeries.map((item, seriesIndex) => {
                    const seriesColor = item.color ?? chartPalette[seriesIndex % chartPalette.length];
                    const points = data.map((row, index) => [x(index), y(row[item.key])]);
                    return <path key={item.key} d={smoothPath(points)} fill="none" stroke={seriesColor} strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" vectorEffect="non-scaling-stroke" />;
                })}

                {data.map((row, index) => {
                    const label = row.month ?? row.label ?? `Data ${index + 1}`;
                    return (
                        <g key={label}>
                            {chartSeries.map((item, seriesIndex) => {
                                const value = toNumber(row[item.key]);
                                const seriesColor = item.color ?? chartPalette[seriesIndex % chartPalette.length];
                                return (
                                    <circle key={`${item.key}-${label}`} cx={x(index)} cy={y(value)} r="5" fill={seriesColor} className="chart-point cursor-pointer" vectorEffect="non-scaling-stroke">
                                        <title>{`${item.label ?? item.key}: ${value} data pada ${label}`}</title>
                                    </circle>
                                );
                            })}
                            <text x={x(index)} y={height - 14} textAnchor="middle" className="chart-axis-label text-[10px] font-semibold">
                                {(row.label ?? row.month ?? "").toString().replace(" ", "\n")}
                            </text>
                        </g>
                    );
                })}
            </svg>
            <div className="chart-legend mt-2 flex flex-wrap gap-3 text-xs font-semibold">
                {chartSeries.map((item, index) => (
                    <span key={item.key} className="inline-flex items-center gap-1.5">
                        <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: item.color ?? chartPalette[index % chartPalette.length] }} />
                        {item.label ?? item.key}
                    </span>
                ))}
            </div>
        </div>
    );
}
