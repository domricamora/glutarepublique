import { Head, Link } from '@inertiajs/react';
import Wordmark from '@/components/site/wordmark';
import kiosk from '@/routes/kiosk';

/** Set up a tablet once: pick its branch, then bookmark the page it opens. */
export default function KioskIndex({
    branches,
}: {
    branches: { id: number; name: string; slug: string }[];
}) {
    return (
        <>
            <Head title="Clock-in kiosk" />
            <main className="flex min-h-svh flex-col items-center justify-center gap-10 bg-pearl px-6 py-12">
                <Wordmark />
                <div className="w-full max-w-md">
                    <h1 className="font-display text-4xl">
                        Which branch is this tablet at?
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        Choose once, then bookmark the page that opens.
                    </p>
                    <ul className="mt-8 grid gap-2">
                        {branches.map((b) => (
                            <li key={b.id}>
                                <Link
                                    href={kiosk.show(b.slug).url}
                                    className="press flex min-h-14 items-center border border-border bg-white px-5 text-lg hover:border-royal"
                                >
                                    {b.name}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            </main>
        </>
    );
}
