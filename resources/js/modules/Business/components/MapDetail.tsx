export function MapDetail({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid grid-cols-[110px_1fr] gap-2">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium break-words">{value}</dd>
        </div>
    );
}
