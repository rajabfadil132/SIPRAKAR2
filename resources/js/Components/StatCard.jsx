export default function StatCard({ label, value, hint, icon }) {
  const valueText = String(value ?? "-");
  const compact = valueText.length > 14;
  const veryCompact = valueText.length > 18;

  return (
    <div className="stat-card min-w-0 overflow-hidden">
      <div className="flex min-w-0 items-start justify-between gap-4">
        <div className="min-w-0 flex-1">
          <p className="text-sm text-slate-500">{label}</p>
          <h3
            className={`mt-2 max-w-full truncate font-black leading-tight tabular-nums ${veryCompact ? "text-lg sm:text-xl" : compact ? "text-xl sm:text-2xl" : "text-3xl"}`}
            title={valueText}
          >
            {valueText}
          </h3>
          {hint && <p className="mt-2 text-xs font-semibold text-[#4cceac]">{hint}</p>}
        </div>
        <div className="shrink-0 rounded-2xl bg-[#141b2d] p-3 text-[#4cceac]">{icon}</div>
      </div>
    </div>
  );
}
