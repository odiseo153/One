import { Camera } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { PhotoRecord } from '@/modules/Project/types/projectDetails';

export function ProjectGalleryCard({ photos }: { photos: PhotoRecord[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Camera className="size-4" />
                    Galería de la obra ({photos.length})
                </CardTitle>
            </CardHeader>
            <CardContent>
                {photos.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        Sin fotos registradas.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-6">
                        {photos.map((photo) => (
                            <a
                                key={photo.id}
                                href={photo.photo_url}
                                target="_blank"
                                rel="noreferrer"
                                title={photo.caption ?? undefined}
                                className="group relative"
                            >
                                <img
                                    src={photo.photo_url}
                                    alt={photo.caption ?? 'Foto de la obra'}
                                    className="bg-muted aspect-square w-full rounded-md object-cover"
                                />
                            </a>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
