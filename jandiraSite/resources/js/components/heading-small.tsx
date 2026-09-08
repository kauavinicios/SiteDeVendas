export default function HeadingSmall({
    title,
    description,
}: {
    title: string;
    description?: string;
}) {
    return (
        <header className="space-y-1">
            <h3 className="text-base font-semibold text-[var(--admin-text)]">
                {title}
            </h3>

            {description && (
                <p className="text-sm leading-relaxed text-[var(--admin-text-muted)]">
                    {description}
                </p>
            )}
        </header>
    );
}