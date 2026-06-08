export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `btn-light inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-bold transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 ${
                    disabled && 'opacity-60'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
