export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center rounded-xl border border-transparent bg-red-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition duration-150 ease-in-out hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 ${
                    disabled && 'opacity-60'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
