import { Head, useForm } from '@inertiajs/react';
import { Camera, Check } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import Wordmark from '@/components/site/wordmark';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { manilaNow } from '@/lib/site';
import kiosk from '@/routes/kiosk';

/** Manila time, ticking, read after mount so the server render never guesses it. */
function useClock(): Date | null {
    const [now, setNow] = useState<Date | null>(null);

    useEffect(() => {
        const tick = () => setNow(manilaNow());
        tick();
        const id = window.setInterval(tick, 15_000);

        return () => window.clearInterval(id);
    }, []);

    return now;
}

/**
 * The branch tablet: employee number, PIN, and a photo to clock in. The camera
 * runs in the page (no hand-off to the camera app, which reloads the tab on
 * some Android phones) and the photo is shrunk to about 1280 px before upload.
 */
export default function KioskShow({
    branch,
}: {
    branch: { name: string; slug: string };
}) {
    const now = useClock();
    const form = useForm<{
        employee_no: string;
        pin: string;
        photo: File | null;
    }>({ employee_no: '', pin: '', photo: null });

    const video = useRef<HTMLVideoElement>(null);
    const fileInput = useRef<HTMLInputElement>(null);
    const stream = useRef<MediaStream | null>(null);
    const [live, setLive] = useState(false);
    const [shot, setShot] = useState<string | null>(null);
    const [cameraError, setCameraError] = useState('');
    const [done, setDone] = useState<string | null>(null);

    const stop = () => {
        stream.current?.getTracks().forEach((t) => t.stop());
        stream.current = null;
        setLive(false);
    };

    useEffect(
        () => () => stream.current?.getTracks().forEach((t) => t.stop()),
        [],
    );

    const keep = (source: CanvasImageSource, w: number, h: number) => {
        const k = Math.min(1, 1280 / Math.max(w, h));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(w * k);
        canvas.height = Math.round(h * k);
        canvas
            .getContext('2d')
            ?.drawImage(source, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(
            (blob) => {
                if (blob) {
                    form.setData(
                        'photo',
                        new File([blob], 'clock-in.jpg', {
                            type: 'image/jpeg',
                        }),
                    );
                    setShot(canvas.toDataURL('image/jpeg', 0.6));
                }
            },
            'image/jpeg',
            0.85,
        );
    };

    const openCamera = async () => {
        setCameraError('');

        if (!navigator.mediaDevices?.getUserMedia) {
            fileInput.current?.click();

            return;
        }

        try {
            stream.current = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 1280 } },
                audio: false,
            });

            if (video.current) {
                video.current.srcObject = stream.current;
                await video.current.play();
            }

            setLive(true);
        } catch {
            stop();
            setCameraError(
                'The camera did not open here. Tap Take photo again to use the device camera.',
            );
        }
    };

    const snap = () => {
        const v = video.current;

        if (v) {
            keep(v, v.videoWidth, v.videoHeight);
        }

        stop();
    };

    return (
        <>
            <Head title={`Clock-in kiosk · ${branch.name}`} />
            <main className="flex min-h-svh flex-col items-center bg-pearl px-6 py-10">
                <Wordmark />
                <div className="mt-8 flex w-full max-w-lg flex-col gap-6">
                    <header className="text-center">
                        <p className="text-sm text-muted-foreground">
                            {branch.name} · clock-in kiosk
                        </p>
                        <p className="mt-2 min-h-[1em] font-display text-6xl leading-none tabular-nums">
                            {now?.toLocaleTimeString('en-PH', {
                                hour: 'numeric',
                                minute: '2-digit',
                            })}
                        </p>
                        <p className="mt-2 text-muted-foreground">
                            {now?.toLocaleDateString('en-PH', {
                                weekday: 'long',
                                month: 'long',
                                day: 'numeric',
                            })}
                        </p>
                    </header>

                    {done && (
                        <p
                            role="status"
                            className="flex items-center gap-3 bg-ocean p-5 text-lg text-white"
                        >
                            <Check className="size-6 shrink-0" /> {done}
                        </p>
                    )}

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            setDone(null);
                            form.post(kiosk.punch(branch.slug).url, {
                                forceFormData: true,
                                preserveScroll: true,
                                onSuccess: (page) => {
                                    // Clear for the next person; the message stays a few seconds.
                                    setDone(
                                        (
                                            page.props.flash as
                                                | { success?: string | null }
                                                | undefined
                                        )?.success ?? null,
                                    );
                                    window.setTimeout(
                                        () => setDone(null),
                                        8000,
                                    );
                                    form.reset();
                                    setShot(null);
                                    stop();
                                },
                                onError: () => form.reset('pin'),
                            });
                        }}
                        className="flex flex-col gap-5 border border-border bg-white p-6"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="employee_no">Employee no.</Label>
                            <Input
                                id="employee_no"
                                value={form.data.employee_no}
                                onChange={(e) =>
                                    form.setData('employee_no', e.target.value)
                                }
                                autoComplete="off"
                                autoCapitalize="characters"
                                className="h-12 text-lg"
                                required
                            />
                            <InputError message={form.errors.employee_no} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="pin">PIN</Label>
                            <Input
                                id="pin"
                                type="password"
                                inputMode="numeric"
                                autoComplete="off"
                                maxLength={6}
                                value={form.data.pin}
                                onChange={(e) =>
                                    form.setData(
                                        'pin',
                                        e.target.value.replace(/\D/g, ''),
                                    )
                                }
                                className="h-12 text-lg tracking-[0.4em]"
                                required
                            />
                            <InputError message={form.errors.pin} />
                        </div>

                        <div className="grid gap-3">
                            <Label>Photo (needed to clock in)</Label>
                            <div
                                className={
                                    live || shot
                                        ? 'aspect-[4/3] overflow-hidden bg-charcoal'
                                        : 'hidden'
                                }
                            >
                                <video
                                    ref={video}
                                    playsInline
                                    muted
                                    className={
                                        live
                                            ? 'h-full w-full -scale-x-100 object-cover'
                                            : 'hidden'
                                    }
                                />
                                {shot && !live && (
                                    <img
                                        src={shot}
                                        alt="Your clock-in photo"
                                        className="h-full w-full object-cover"
                                    />
                                )}
                            </div>
                            {live ? (
                                <Button
                                    type="button"
                                    onClick={snap}
                                    className="h-14 bg-charcoal text-white"
                                >
                                    <Camera className="size-5" /> Take photo
                                </Button>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() =>
                                        cameraError
                                            ? fileInput.current?.click()
                                            : openCamera()
                                    }
                                    className="press flex h-14 items-center justify-center gap-2 border border-dashed border-border font-medium hover:border-royal"
                                >
                                    <Camera className="size-5" />
                                    {shot
                                        ? 'Photo taken. Tap to retake'
                                        : 'Take photo'}
                                </button>
                            )}
                            <input
                                ref={fileInput}
                                type="file"
                                accept="image/*"
                                capture="user"
                                className="sr-only"
                                tabIndex={-1}
                                onChange={async (e) => {
                                    const file = e.target.files?.[0];
                                    e.target.value = '';

                                    if (file) {
                                        const bitmap =
                                            await createImageBitmap(file);
                                        keep(
                                            bitmap,
                                            bitmap.width,
                                            bitmap.height,
                                        );
                                    }
                                }}
                            />
                            {cameraError && (
                                <p className="text-sm text-destructive">
                                    {cameraError}
                                </p>
                            )}
                            <InputError message={form.errors.photo} />
                        </div>

                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="h-14 w-full text-base"
                        >
                            {form.processing && <Spinner />}
                            Clock in or out
                        </Button>
                    </form>
                    <p className="text-center text-sm text-muted-foreground">
                        Forgot your PIN? Ask HR or your branch manager.
                    </p>
                </div>
            </main>
        </>
    );
}
