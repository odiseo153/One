export const COMPLAINT_TRANSITIONS: Record<string, string[]> = {
    received: ['in_progress', 'resolved'],
    in_progress: ['received', 'resolved'],
    resolved: ['in_progress'],
};

export function complaintStatusClass(status: string) {
    switch (status) {
        case 'in_progress':
            return 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-400';
        case 'resolved':
            return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
        default:
            return 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-400';
    }
}
