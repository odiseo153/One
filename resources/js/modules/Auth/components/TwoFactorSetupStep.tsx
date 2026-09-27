import { Check, Copy } from 'lucide-react';
import AlertError from '@/components/alert-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useAppearance } from '@/hooks/use-appearance';
import { useClipboard } from '@/hooks/use-clipboard';

export function TwoFactorSetupStep({
    qrCodeSvg,
    manualSetupKey,
    buttonText,
    onNextStep,
    errors,
}: {
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
    buttonText: string;
    onNextStep: () => void;
    errors: string[];
}) {
    const { resolvedAppearance } = useAppearance();
    const [copiedText, copy] = useClipboard();
    const IconComponent = copiedText === manualSetupKey ? Check : Copy;

    return errors?.length ? (
        <AlertError errors={errors} />
    ) : (
        <>
            <div className="mx-auto flex max-w-md overflow-hidden">
                <div className="border-border mx-auto aspect-square w-64 rounded-lg border">
                    <div className="z-10 flex h-full w-full items-center justify-center p-5">
                        {qrCodeSvg ? (
                            <div
                                className="aspect-square w-full rounded-lg bg-white p-2 [&_svg]:size-full"
                                dangerouslySetInnerHTML={{ __html: qrCodeSvg }}
                                style={{
                                    filter:
                                        resolvedAppearance === 'dark'
                                            ? 'invert(1) brightness(1.5)'
                                            : undefined,
                                }}
                            />
                        ) : (
                            <Spinner />
                        )}
                    </div>
                </div>
            </div>
            <div className="flex w-full space-x-5">
                <Button className="w-full" onClick={onNextStep}>
                    {buttonText}
                </Button>
            </div>
            <div className="relative flex w-full items-center justify-center">
                <div className="bg-border absolute inset-0 top-1/2 h-px w-full" />
                <span className="bg-card relative px-2 py-1">
                    or, enter the code manually
                </span>
            </div>
            <div className="flex w-full space-x-2">
                <div className="border-border flex w-full items-stretch overflow-hidden rounded-xl border">
                    {!manualSetupKey ? (
                        <div className="bg-muted flex h-full w-full items-center justify-center p-3">
                            <Spinner />
                        </div>
                    ) : (
                        <>
                            <input
                                type="text"
                                readOnly
                                value={manualSetupKey}
                                className="bg-background text-foreground h-full w-full p-3 outline-none"
                            />
                            <button
                                onClick={() => copy(manualSetupKey)}
                                className="border-border hover:bg-muted border-l px-3"
                            >
                                <IconComponent className="w-4" />
                            </button>
                        </>
                    )}
                </div>
            </div>
        </>
    );
}
