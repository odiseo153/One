import { Link } from '@inertiajs/react';
import {
    BarChart3,
    BookOpen,
    Building2,
    FolderGit2,
    HardHat,
    LayoutGrid,
    Map,
    MapPinned,
    MessageSquareText,
    Store,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        children: [
            {
                title: 'Oidseo',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ],
    },
    {
        title: 'Usuarios',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Municipios',
        href: '/admin/municipalities',
        icon: Building2,
    },
    {
        title: 'Sectores',
        href: '/admin/sectors',
        icon: Map,
    },
    {
        title: 'Mapa sectorial',
        href: '/admin/sector-map',
        icon: MapPinned,
    },
    {
        title: 'Negocios',
        href: '/admin/registered-businesses',
        icon: Store,
    },
    {
        title: 'Quejas',
        href: '/admin/complaints',
        icon: MessageSquareText,
    },
    {
        title: 'Obras',
        href: '/admin/projects',
        icon: HardHat,
    },
    {
        title: 'Reportes de obras',
        href: '/admin/projects/reports',
        icon: BarChart3,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
