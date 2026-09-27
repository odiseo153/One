import { router, type InertiaFormProps } from '@inertiajs/react';
import type {
    ProjectAssignmentFormData,
    ProjectFormData,
    ProjectMilestoneFormData,
    ProjectStatusFormData,
    ProjectUpdateFormData,
} from '@/modules/Project/types/projectForms';

const NONE = '__none__';
const KEEP = '__keep__';

export const projectService = {
    save(form: InertiaFormProps<ProjectFormData>, projectId: number | null) {
        form.transform((values) => ({
            ...values,
            municipality_id: Number(values.municipality_id),
            sector_id:
                values.sector_id === NONE ? null : Number(values.sector_id),
            latitude: values.latitude === '' ? null : Number(values.latitude),
            longitude:
                values.longitude === '' ? null : Number(values.longitude),
            budget_assigned:
                values.budget_assigned === ''
                    ? null
                    : Number(values.budget_assigned),
            description: values.description || null,
            address_text: values.address_text || null,
            contractor_name: values.contractor_name || null,
            start_date_planned: values.start_date_planned || null,
            end_date_planned: values.end_date_planned || null,
        }));

        if (projectId) {
            form.patch(`/admin/projects/${projectId}`, {
                preserveScroll: true,
            });
            return;
        }

        form.post('/admin/projects', { preserveScroll: true });
    },

    saveUpdate(
        form: InertiaFormProps<ProjectUpdateFormData>,
        projectId: number,
    ) {
        form.transform((values) => {
            const { status, ...rest } = values;
            return status === KEEP ? rest : { ...rest, status };
        });
        form.post(`/admin/projects/${projectId}/updates`, {
            preserveScroll: true,
            forceFormData: true,
        });
    },

    filter(filters: Record<string, string | string[] | undefined>) {
        router.get('/admin/projects', filters, {
            preserveState: true,
            replace: true,
        });
    },

    filterReports(filters: Record<string, string | undefined>) {
        router.get('/admin/projects/reports', filters, {
            preserveScroll: true,
        });
    },

    openList() {
        router.get('/admin/projects');
    },

    assign(
        form: InertiaFormProps<ProjectAssignmentFormData>,
        projectId: number,
        onSuccess: () => void,
    ) {
        form.transform((values) => ({
            user_id:
                values.user_id === NONE ? undefined : Number(values.user_id),
            role_in_project: values.role_in_project,
        }));
        form.post(`/admin/projects/${projectId}/users`, {
            preserveScroll: true,
            onSuccess,
        });
    },

    changeRole(projectId: number, userId: number | undefined, role: string) {
        router.post(
            `/admin/projects/${projectId}/users`,
            { user_id: userId, role_in_project: role },
            { preserveScroll: true },
        );
    },

    removeMember(projectId: number, memberId: number) {
        router.delete(`/admin/projects/${projectId}/users/${memberId}`, {
            preserveScroll: true,
        });
    },

    updateStatus(
        form: InertiaFormProps<ProjectStatusFormData>,
        projectId: number,
    ) {
        form.patch(`/admin/projects/${projectId}/status`, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    },

    createMilestone(
        form: InertiaFormProps<ProjectMilestoneFormData>,
        projectId: number,
        onSuccess: () => void,
    ) {
        form.post(`/admin/projects/${projectId}/milestones`, {
            preserveScroll: true,
            onSuccess,
        });
    },

    updateMilestone(projectId: number, milestoneId: number, status: string) {
        router.patch(
            `/admin/projects/${projectId}/milestones/${milestoneId}/status`,
            { status },
            { preserveScroll: true },
        );
    },

    removeMilestone(projectId: number, milestoneId: number) {
        router.delete(
            `/admin/projects/${projectId}/milestones/${milestoneId}`,
            { preserveScroll: true },
        );
    },
};
