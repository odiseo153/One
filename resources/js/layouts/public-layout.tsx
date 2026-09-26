import { Head, Link, usePage } from '@inertiajs/react';
import { LayoutGrid, MessageSquareText, Search } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import type { ReactNode } from 'react';

const navItems = [
    {
        title: 'Inicio',
        href: '/',
        icon: LayoutGrid,
    },
    {
        title: 'Reportar queja',
        href: '/quejas',
        icon: MessageSquareText,
    },
    {
        title: 'Consultar estado',
        href: '/quejas/track',
        icon: Search,
    },
];

export default function PublicLayout({ children }: { children: ReactNode }) {
    const { name, auth } = usePage().props;

    return (
        <>
            <Head />
            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="sticky top-0 z-10 border-b border-[#19140035] bg-[#FDFDFC]/80 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/80">
                    <div className="mx-auto flex h-16 w-full max-w-5xl items-center justify-between px-6">
                        <Link href="/" className="flex items-center gap-2">
                            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-[#1b1b18]">
                                <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                            </div>
                            <span className="text-sm font-semibold">
                                {name}
                            </span>
                        </Link>
                        <nav className="flex items-center gap-1 text-sm">
                            {navItems.map((item) => (
                                <Link
                                    key={item.title}
                                    href={item.href}
                                    className="inline-flex items-center gap-2 rounded-md px-3 py-1.5 transition-colors hover:bg-[#19140035] dark:hover:bg-[#3E3E3A]"
                                >
                                    <item.icon className="size-4" />
                                    <span className="hidden sm:inline">
                                        {item.title}
                                    </span>
                                </Link>
                            ))}
                            {auth.user && (
                                <Link
                                    href="/dashboard"
                                    className="inline-flex items-center rounded-md border border-[#19140035] px-3 py-1.5 font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                                >
                                    Dashboard
                                </Link>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-5xl flex-1 px-6 py-10">
                    {children}
                </main>

                <footer className="border-t border-[#19140035] py-6 text-center text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                    {name} — Plataforma de gestión de quejas ciudadanas
                </footer>
            </div>
        </>
    );
}
