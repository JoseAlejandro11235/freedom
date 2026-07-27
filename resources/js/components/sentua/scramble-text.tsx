import { useEffect, useState } from 'react';

const GLYPHS =
    'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz#@$%&*<>/\\|?!+=~^[]{}:;.,_-01IlO';

function randomGlyph(): string {
    return GLYPHS[Math.floor(Math.random() * GLYPHS.length)] ?? 'X';
}

function scramble(length: number): string {
    return Array.from({ length }, () => randomGlyph()).join('');
}

interface ScrambleTextProps {
    text: string;
    className?: string;
    /** Delay before scramble starts (ms) */
    delay?: number;
    /** Full scramble before the first letter locks (ms) */
    warmUpMs?: number;
    /** How long each character keeps cycling before locking (ms) */
    stepMs?: number;
    /** Tick rate while scrambling (ms) */
    tickMs?: number;
}

export function ScrambleText({
    text,
    className,
    delay = 220,
    warmUpMs = 700,
    stepMs = 160,
    tickMs = 48,
}: ScrambleTextProps) {
    const target = text.toUpperCase();
    const [display, setDisplay] = useState(() => scramble(target.length));

    useEffect(() => {
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (reduced) {
            setDisplay(target);
            return;
        }

        let locked = 0;
        let tickId: ReturnType<typeof setInterval> | undefined;
        let lockId: ReturnType<typeof setInterval> | undefined;
        let warmUpId: ReturnType<typeof setTimeout> | undefined;

        setDisplay(scramble(target.length));

        const paint = () => {
            setDisplay(
                target
                    .split('')
                    .map((char, i) => (i < locked ? char : randomGlyph()))
                    .join(''),
            );
        };

        const startId = setTimeout(() => {
            tickId = setInterval(paint, tickMs);

            warmUpId = setTimeout(() => {
                lockId = setInterval(() => {
                    locked += 1;
                    paint();

                    if (locked >= target.length) {
                        if (tickId) {
                            clearInterval(tickId);
                        }
                        if (lockId) {
                            clearInterval(lockId);
                        }
                        setDisplay(target);
                    }
                }, stepMs);
            }, warmUpMs);
        }, delay);

        return () => {
            clearTimeout(startId);
            if (warmUpId) {
                clearTimeout(warmUpId);
            }
            if (tickId) {
                clearInterval(tickId);
            }
            if (lockId) {
                clearInterval(lockId);
            }
        };
    }, [target, delay, warmUpMs, stepMs, tickMs]);

    return (
        <span className={className} aria-label={target}>
            {display.split('').map((char, i) => {
                return (
                    <span
                        key={`${target}-${i}`}
                        style={{
                            opacity: 1,
                            color: 'inherit',
                        }}
                    >
                        {char}
                    </span>
                );
            })}
        </span>
    );
}
