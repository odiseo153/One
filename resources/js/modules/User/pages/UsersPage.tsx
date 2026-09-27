import { Head, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { userService } from '@/modules/User/services/userService';
import type {
    UserFormData,
    UserRecord,
    UsersPageProps,
} from '@/modules/User/types/user';
import { AdminListFilters } from '@/shared/components/AdminListFilters';

const NONE = '__none__';

export default function Users({
    users,
    municipalities,
    sectors,
    filters,
}: UsersPageProps) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<UserRecord | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const form = useForm<UserFormData>({
        name: '',
        email: '',
        password: '',
        municipality_id: NONE,
        sector_id: NONE,
        phone: '',
        status: 'active',
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (user: UserRecord) => {
        setEditing(user);
        form.setData({
            name: user.name,
            email: user.email,
            password: '',
            municipality_id: user.municipality_id
                ? String(user.municipality_id)
                : NONE,
            sector_id: user.sector_id ? String(user.sector_id) : NONE,
            phone: user.phone ?? '',
            status: user.status ?? 'active',
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        userService.save(form, editing?.id ?? null, () => setOpen(false));
    };

    const destroy = (user: UserRecord) => {
        if (confirm(`¿Desactivar a ${user.name}?`)) {
            userService.deactivate(user.id);
        }
    };

    const applyFilters = (patch: Record<string, string | undefined>) => {
        userService.filter({ ...filters, ...patch });
    };

    return (
        <>
            <Head title="Usuarios" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        title="Usuarios"
                        description="Gestiona los usuarios del sistema"
                    />
                    <Button onClick={openCreate}>
                        <Plus />
                        Crear usuario
                    </Button>
                </div>

                <AdminListFilters
                    search={search}
                    status={filters.status ?? 'active'}
                    placeholder="Buscar nombre, email o teléfono..."
                    onSearchChange={setSearch}
                    onSearch={() =>
                        applyFilters({ search: search || undefined })
                    }
                    onStatusChange={(status) => applyFilters({ status })}
                />

                <div className="border-sidebar-border/70 overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-muted-foreground text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Nombre
                                </th>
                                <th className="px-4 py-3 font-medium">Email</th>
                                <th className="px-4 py-3 font-medium">
                                    Teléfono
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Municipio
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Sector
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Estado
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-sidebar-border/50 divide-y">
                            {users.data.map((user) => (
                                <tr key={user.id} className="hover:bg-muted/30">
                                    <td className="px-4 py-3 font-medium">
                                        {user.name}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {user.email}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {user.phone ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {user.municipality?.name ?? '—'}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">
                                        {user.sector?.name ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <StatusBadge
                                            deletedAt={user.deleted_at}
                                        />
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => openEdit(user)}
                                            >
                                                <Pencil />
                                            </Button>
                                            {!user.deleted_at && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() =>
                                                        destroy(user)
                                                    }
                                                >
                                                    <Trash2 />
                                                </Button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {users.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="text-muted-foreground px-4 py-10 text-center"
                                    >
                                        No hay usuarios.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination
                    links={users.links}
                    from={users.from ?? undefined}
                    to={users.to ?? undefined}
                    total={users.total}
                />

                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>
                                {editing ? 'Editar usuario' : 'Crear usuario'}
                            </DialogTitle>
                            <DialogDescription>
                                {editing
                                    ? 'Actualiza los datos del usuario.'
                                    : 'Completa los datos para registrar un nuevo usuario.'}
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nombre</Label>
                                    <Input
                                        id="name"
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData('name', e.target.value)
                                        }
                                        autoComplete="off"
                                    />
                                    <InputError message={form.errors.name} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={form.data.email}
                                        onChange={(e) =>
                                            form.setData(
                                                'email',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="off"
                                    />
                                    <InputError message={form.errors.email} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="password">
                                        Contraseña
                                        {editing && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                (dejar en blanco para mantener)
                                            </span>
                                        )}
                                    </Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={form.data.password}
                                        onChange={(e) =>
                                            form.setData(
                                                'password',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="new-password"
                                    />
                                    <InputError
                                        message={form.errors.password}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="phone">Teléfono</Label>
                                    <Input
                                        id="phone"
                                        value={form.data.phone}
                                        onChange={(e) =>
                                            form.setData(
                                                'phone',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="off"
                                    />
                                    <InputError message={form.errors.phone} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Municipio</Label>
                                    <Select
                                        value={form.data.municipality_id}
                                        onValueChange={(value) => {
                                            form.setData(
                                                'municipality_id',
                                                value,
                                            );
                                            form.setData('sector_id', NONE);
                                        }}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>
                                                Sin municipio
                                            </SelectItem>
                                            {municipalities.map((m) => (
                                                <SelectItem
                                                    key={m.id}
                                                    value={String(m.id)}
                                                >
                                                    {m.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.municipality_id}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Sector</Label>
                                    <Select
                                        value={form.data.sector_id}
                                        onValueChange={(value) =>
                                            form.setData('sector_id', value)
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>
                                                Sin sector
                                            </SelectItem>
                                            {sectors
                                                .filter(
                                                    (sector) =>
                                                        form.data
                                                            .municipality_id ===
                                                            NONE ||
                                                        sector.municipality_id ===
                                                            Number(
                                                                form.data
                                                                    .municipality_id,
                                                            ),
                                                )
                                                .map((sector) => (
                                                    <SelectItem
                                                        key={sector.id}
                                                        value={String(
                                                            sector.id,
                                                        )}
                                                    >
                                                        {sector.name}
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={form.errors.sector_id}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Estado</Label>
                                    <Select
                                        value={form.data.status}
                                        onValueChange={(value) =>
                                            form.setData('status', value)
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">
                                                Activo
                                            </SelectItem>
                                            <SelectItem value="inactive">
                                                Inactivo
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.status} />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancelar
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {editing ? 'Guardar' : 'Crear'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}
