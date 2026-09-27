import { Head, useForm } from '@inertiajs/react';
import { Search } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { complaintService } from '@/modules/Complaint/services/complaintService';

export default function TrackComplaint() {
    const form = useForm({ tracking_code: '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const code = form.data.tracking_code.trim();
        if (!code) {
            return;
        }
        complaintService.track(code);
    };

    return (
        <>
            <Head title="Consultar estado" />
            <div className="mx-auto max-w-md">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Search className="size-4" />
                            Consultar el estado de tu queja
                        </CardTitle>
                        <CardDescription>
                            Ingresa el código de seguimiento que recibiste al
                            reportar.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="tracking_code">
                                    Código de seguimiento
                                </Label>
                                <Input
                                    id="tracking_code"
                                    value={form.data.tracking_code}
                                    onChange={(e) =>
                                        form.setData(
                                            'tracking_code',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="CMP-XXXXXXXX"
                                    autoComplete="off"
                                    autoFocus
                                />
                                <InputError
                                    message={form.errors.tracking_code}
                                />
                            </div>
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={form.processing}
                            >
                                <Search />
                                Consultar
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
