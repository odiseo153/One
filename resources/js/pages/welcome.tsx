import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    CheckCircle2,
    ClipboardCheck,
    LayoutGrid,
    MapPin,
    MessageSquareText,
    Search,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { dashboard, login, register } from '@/routes';

const categories = ['Baches', 'Alumbrado', 'Basura', 'Agua'];

const features = [
    {
        title: 'Reporta en minutos',
        description:
            'Llena un formulario sencillo con la categoría, la descripción y la ubicación. No necesitas crear una cuenta.',
        icon: MessageSquareText,
    },
    {
        title: 'Sigue el avance con un código',
        description:
            'Al reportar recibirás un código único con el que podrás consultar el estado de tu queja en cualquier momento.',
        icon: Search,
    },
    {
        title: 'El municipio gestiona y resuelve',
        description:
            'El personal municipal asigna, da seguimiento y registra cada solución para tu comunidad.',
        icon: ClipboardCheck,
    },
];

const steps = [
    {
        title: 'Reporta',
        description: 'Cuéntanos qué está pasando en tu entorno.',
        icon: MessageSquareText,
    },
    {
        title: 'Recibe tu código',
        description: 'Guárdalo para consultar el estado más adelante.',
        icon: CheckCircle2,
    },
    {
        title: 'Consulta el avance',
        description: 'Sigue cada cambio hasta que la queja se resuelva.',
        icon: Search,
    },
];

export default function Welcome() {
    const { name, auth } = usePage().props;

    return (
        <>
            <Head title={name} />
            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="sticky top-0 z-10 border-b border-[#19140035] bg-[#FDFDFC]/80 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/80">
                    <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-6">
                        <Link
                            href="/"
                            className="flex items-center gap-2 font-medium"
                        >
                            <span className="flex size-8 items-center justify-center rounded-md bg-[#1b1b18]">
                                <span className="text-white dark:text-black">
                                    <Building2 className="size-5" />
                                </span>
                            </span>
                            <span>{name}</span>
                        </Link>
                        <nav className="flex items-center gap-1 text-sm">
                            <Link
                                href="/quejas"
                                className="inline-flex items-center gap-2 rounded-md px-3 py-1.5 transition-colors hover:bg-[#19140035] dark:hover:bg-[#3E3E3A]"
                            >
                                <MessageSquareText className="size-4" />
                                Reportar queja
                            </Link>
                            <Link
                                href="/quejas/track"
                                className="inline-flex items-center gap-2 rounded-md px-3 py-1.5 transition-colors hover:bg-[#19140035] dark:hover:bg-[#3E3E3A]"
                            >
                                <Search className="size-4" />
                                Consultar estado
                            </Link>
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex items-center gap-2 rounded-md border border-[#19140035] px-3 py-1.5 font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                                >
                                    <LayoutGrid className="size-4" />
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="inline-flex items-center gap-2 rounded-md px-3 py-1.5 transition-colors hover:bg-[#19140035] dark:hover:bg-[#3E3E3A]"
                                    >
                                        Iniciar sesión
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="inline-flex items-center gap-2 rounded-md bg-[#1b1b18] px-3 py-1.5 font-medium text-white hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-white"
                                    >
                                        Crear cuenta
                                    </Link>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex-1">
                    <section className="mx-auto max-w-6xl px-6 py-16 lg:py-24">
                        <div className="mx-auto max-w-3xl text-center">
                            <span className="inline-flex items-center gap-2 rounded-full border border-[#19140035] px-3 py-1 text-xs font-medium text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                <ShieldCheck className="size-3.5" />
                                Plataforma de gestión municipal
                            </span>
                            <h1 className="mt-4 text-4xl font-semibold tracking-tight lg:text-6xl">
                                Reporta y da seguimiento a los problemas de tu
                                municipio
                            </h1>
                            <p className="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                                Baches, alumbrado, basura o agua: cuéntanos qué
                                está pasando en tu comunidad y el municipio se
                                encargará de gestionarlo, con seguimiento
                                transparente.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                                <Link
                                    href="/quejas"
                                    className="inline-flex items-center gap-2 rounded-md bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-white"
                                >
                                    <MessageSquareText className="size-4" />
                                    Reportar una queja
                                    <ArrowRight className="size-4" />
                                </Link>
                                <Link
                                    href="/quejas/track"
                                    className="inline-flex items-center gap-2 rounded-md border border-[#19140035] px-5 py-2.5 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                                >
                                    <Search className="size-4" />
                                    Consultar estado
                                </Link>
                            </div>
                            <div className="mt-8 flex flex-wrap items-center justify-center gap-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                <span className="mr-1">
                                    Reporta problemas de:
                                </span>
                                {categories.map((category) => (
                                    <span
                                        key={category}
                                        className="rounded-full border border-[#19140035] px-2.5 py-1 dark:border-[#3E3E3A]"
                                    >
                                        {category}
                                    </span>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="bg-[#F5F5F4] py-16 dark:bg-[#111110]">
                        <div className="mx-auto max-w-6xl px-6">
                            <div className="grid gap-6 md:grid-cols-3">
                                {features.map((feature) => (
                                    <div
                                        key={feature.title}
                                        className="rounded-xl border border-[#19140035] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]"
                                    >
                                        <span className="flex size-10 items-center justify-center rounded-lg bg-[#F53003]/10 text-[#F53003] dark:text-[#FF4433]">
                                            <feature.icon className="size-5" />
                                        </span>
                                        <h3 className="mt-4 font-medium">
                                            {feature.title}
                                        </h3>
                                        <p className="text-muted-foreground mt-1 text-sm">
                                            {feature.description}
                                        </p>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-16 text-center">
                                <h2 className="text-2xl font-semibold tracking-tight">
                                    ¿Cómo funciona?
                                </h2>
                                <div className="mt-8 grid gap-6 md:grid-cols-3">
                                    {steps.map((step, index) => (
                                        <div key={step.title}>
                                            <span className="text-muted-foreground flex items-center justify-center gap-2 text-sm font-medium">
                                                <span className="flex size-8 items-center justify-center rounded-full bg-[#1b1b18] text-sm font-semibold text-white dark:bg-[#EDEDEC] dark:text-[#1C1C1A]">
                                                    {index + 1}
                                                </span>
                                                {step.title}
                                            </span>
                                            <p className="text-muted-foreground mx-auto mt-2 max-w-xs text-sm">
                                                {step.description}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="mx-auto max-w-6xl px-6 py-16">
                        <div className="rounded-2xl border border-[#19140035] bg-[#fff2f2] p-10 text-center dark:border-[#3E3E3A] dark:bg-[#1D0002]">
                            <MapPin className="mx-auto size-8 text-[#F53003] dark:text-[#FF4433]" />
                            <h2 className="mt-4 text-2xl font-semibold tracking-tight">
                                ¿Hay un problema en tu comunidad?
                            </h2>
                            <p className="text-muted-foreground mx-auto mt-2 max-w-xl text-sm">
                                Reportarlo lleva menos de un minuto y el
                                municipio podrá atenderlo y darte seguimiento
                                con total transparencia.
                            </p>
                            <Link
                                href="/quejas"
                                className="mt-6 inline-flex items-center gap-2 rounded-md bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-white"
                            >
                                <MessageSquareText className="size-4" />
                                Empezar ahora
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-[#19140035] py-6 dark:border-[#3E3E3A]">
                    <div className="mx-auto flex flex-col items-center justify-between gap-3 px-6 text-xs text-[#706f6c] sm:flex-row dark:text-[#A1A09A]">
                        <span className="flex items-center gap-1.5">
                            <Users className="size-3.5" />
                            {name} — Gestión de quejas ciudadanas
                        </span>
                        <span>
                            © {new Date().getFullYear()} {name}
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
