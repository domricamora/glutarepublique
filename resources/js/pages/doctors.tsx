import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { TeamGrid } from '@/components/site/team-grid';
import { useReveal } from '@/lib/site';
import { book } from '@/routes';
import type { Specialist } from '@/types/site';

/** Our team: the clinicians published from the staff roll (route /doctors). */
export default function Doctors({
    specialists,
}: {
    specialists: Specialist[];
}) {
    useReveal();

    return (
        <>
            <Head title="Our team">
                <meta
                    name="description"
                    content="Meet the nurses, aestheticians and facialists at Gluta RepubliQ Aesthetics: who they are and which branch they work from."
                />
            </Head>

            <section className="on-dark gutter bg-plum pt-16 pb-14 text-white lg:pt-24">
                <div className="mx-auto max-w-7xl">
                    <p className="eyebrow">Our team</p>
                    <h1 className="mt-4 max-w-4xl text-5xl leading-[1.05] sm:text-7xl">
                        The people who{' '}
                        <em className="text-champagne">look after you.</em>
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-lilac">
                        Every plan starts with a consultation with one of our
                        practitioners, who also performs and reviews your
                        treatment.
                    </p>
                </div>
            </section>

            <section className="gutter py-16 lg:py-24">
                {specialists.length > 0 ? (
                    <TeamGrid specialists={specialists} />
                ) : (
                    <p className="text-muted-foreground">
                        Our team profiles are being updated. Call any branch and
                        we will tell you who is in today.
                    </p>
                )}
                <Link
                    href={book().url}
                    className="btn btn-solid mt-14 inline-flex items-center gap-2"
                >
                    Book a consultation <ArrowRight className="size-4" />
                </Link>
            </section>
        </>
    );
}
