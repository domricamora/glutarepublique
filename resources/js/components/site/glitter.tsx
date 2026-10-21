import type { AnimationEvent, CSSProperties } from 'react';
import { useEffect, useState } from 'react';

type Glint = { star: boolean; size: number; style: CSSProperties };

/**
 * Light glints that twinkle over the home hero. Each one plays a single
 * fade-and-turn, then moves to a new random spot before its next cycle, so
 * the glitter never settles into a visible pattern. Positions are written
 * straight to the element on each iteration instead of through state, so
 * nothing re-renders. The glints are made after mount, because random
 * positions rendered on the server would not match the client. Reduced
 * motion hides the layer in CSS.
 */
export default function Glitter({ count = 30 }: { count?: number }) {
    const [glints, setGlints] = useState<Glint[]>([]);

    useEffect(() => {
        setGlints(
            Array.from({ length: count }, (_, i) => ({
                star: i % 3 !== 2,
                size:
                    i % 3 === 2
                        ? 3 + Math.random() * 4
                        : 12 + Math.random() * 18,
                style: {
                    ...place(),
                    animationDuration: `${2.4 + Math.random() * 2.6}s`,
                    animationDelay: `${Math.random() * 4}s`,
                } as CSSProperties,
            })),
        );
    }, [count]);

    const move = (e: AnimationEvent<HTMLSpanElement>) => {
        const { left, top } = place();
        e.currentTarget.style.left = left;
        e.currentTarget.style.top = top;
    };

    return (
        <div
            aria-hidden="true"
            className="glitter pointer-events-none absolute inset-0"
        >
            {glints.map((g, i) => (
                <span
                    key={i}
                    onAnimationIteration={move}
                    className={g.star ? 'glint' : 'glint glint-dot'}
                    style={{ ...g.style, width: g.size, height: g.size }}
                >
                    {g.star && (
                        <svg viewBox="0 0 24 24" className="size-full">
                            <path
                                d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12 6.4-.6 11.4-5.6 12-12z"
                                fill="currentColor"
                            />
                        </svg>
                    )}
                </span>
            ))}
        </div>
    );
}

// Kept off the outer edges so a glint is never cut in half.
const place = () => ({
    left: `${4 + Math.random() * 92}%`,
    top: `${6 + Math.random() * 86}%`,
});
