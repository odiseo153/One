import { Head, Link, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ArrowRight, MailCheck, Palette, ShieldCheck } from 'lucide-react';
import { edit as appearance } from '@/routes/appearance';
import { dashboard } from '@/routes';
import { edit as profile } from '@/routes/profile';
import { edit as security } from '@/routes/security';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

export default function Dashboard() {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <section className="border-sidebar-border/70 bg-card rounded-xl border p-6 shadow-sm">
                    <Heading
                        title={`Bienvenido, ${auth.user.name}`}
                        description="Tu panel ya está cargando contenido real. Desde aquí puedes revisar tu cuenta y entrar directo a la configuración principal."
                    />
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="gap-3">
                            <MailCheck className="text-muted-foreground size-5" />
                            <div className="space-y-1">
                                <CardTitle>Perfil</CardTitle>
                                <CardDescription>
                                    Revisa tu nombre, correo y estado de
                                    verificación.
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-1 text-sm">
                                <p className="font-medium">{auth.user.email}</p>
                                <p className="text-muted-foreground">
                                    {auth.user.email_verified_at
                                        ? 'Correo verificado.'
                                        : 'Correo pendiente de verificación.'}
                                </p>
                            </div>
                            <Link
                                href={profile()}
                                className="inline-flex items-center gap-2 text-sm font-medium"
                            >
                                Abrir perfil
                                <ArrowRight className="size-4" />
                            </Link>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="gap-3">
                            <ShieldCheck className="text-muted-foreground size-5" />
                            <div className="space-y-1">
                                <CardTitle>Seguridad</CardTitle>
                                <CardDescription>
                                    Gestiona contraseña, acceso y segundo
                                    factor.
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-muted-foreground text-sm">
                                Estado de 2FA:{' '}
                                {auth.user.two_factor_confirmed_at
                                    ? 'activo'
                                    : 'inactivo'}
                                .
                            </p>
                            <Link
                                href={security()}
                                className="inline-flex items-center gap-2 text-sm font-medium"
                            >
                                Abrir seguridad
                                <ArrowRight className="size-4" />
                            </Link>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="gap-3">
                            <Palette className="text-muted-foreground size-5" />
                            <div className="space-y-1">
                                <CardTitle>Apariencia</CardTitle>
                                <CardDescription>
                                    Ajusta tema y preferencia visual de la
                                    aplicación.
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-muted-foreground text-sm">
                                Cambia entre modo claro y oscuro desde la
                                configuración.
                            </p>
                            <Link
                                href={appearance()}
                                className="inline-flex items-center gap-2 text-sm font-medium"
                            >
                                Abrir apariencia
                                <ArrowRight className="size-4" />
                            </Link>
                        </CardContent>
                    </Card>
                </section>

                <Card>
                    <CardHeader>
                        <CardTitle>Resumen</CardTitle>
                        <CardDescription>
                            Accesos rápidos para continuar dentro de la cuenta.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 md:grid-cols-3">
                        <Link
                            href={dashboard()}
                            className="hover:bg-accent rounded-lg border p-4 text-sm transition-colors"
                        >
                            <p className="font-medium">Inicio</p>
                            <p className="text-muted-foreground mt-1">
                                Vista general del panel actual.
                            </p>
                        </Link>
                        <Link
                            href={profile()}
                            className="hover:bg-accent rounded-lg border p-4 text-sm transition-colors"
                        >
                            <p className="font-medium">Editar perfil</p>
                            <p className="text-muted-foreground mt-1">
                                Actualiza datos personales.
                            </p>
                        </Link>
                        <Link
                            href={security()}
                            className="hover:bg-accent rounded-lg border p-4 text-sm transition-colors"
                        >
                            <p className="font-medium">Revisar seguridad</p>
                            <p className="text-muted-foreground mt-1">
                                Contraseña y autenticación.
                            </p>
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
