import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div
                className="
                    flex
                    size-9
                    aspect-square
                    items-center
                    justify-center
                    rounded-xl
                    bg-[var(--admin-primary)]
                    text-white
                    shadow-sm
                "
            >
                <AppLogoIcon className="size-5 fill-current" />
            </div>

            <div className="ml-2 grid flex-1 text-left">
                <span
                    className="
                        truncate
                        text-sm
                        font-semibold
                        leading-tight
                        text-[var(--admin-text)]
                    "
                >
                    Butique da Jandira
                </span>

                <span
                    className="
                        mt-0.5
                        truncate
                        text-xs
                        text-[var(--admin-text-muted)]
                    "
                >
                    Painel administrativo
                </span>
            </div>
        </>
    );
}