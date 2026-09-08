export default function Heading({
    title,
    description,
}: {
    title: string;
    description?: string;
}) {
    return (
        <div className="mb-8 space-y-1">
            <h2 className="text-xl font-semibold tracking-tight text-[var(--admin-text)]">
                {title}
            </h2>

            {description && (
                <p className="text-sm leading-relaxed text-[var(--admin-text-muted)]">
                    {description}
                </p>
            )}
        </div>
    );
}