import { execFileSync } from 'node:child_process';

/**
 * Jalankan PHP di konteks aplikasi lewat container Sail (selalu `-u sail`,
 * lihat AGENTS.md). Argumen dikirim tanpa shell, jadi data cukup di-encode
 * base64 agar tidak ada masalah quoting.
 */
export function runTinker(php: string): string {
    return execFileSync(
        'docker',
        ['compose', 'exec', '-T', '-u', 'sail', 'laravel.test', 'php', 'artisan', 'tinker', '--execute', php],
        { encoding: 'utf8' },
    ).trim();
}

export function encodeForPhp(data: unknown): string {
    return `json_decode(base64_decode('${Buffer.from(JSON.stringify(data)).toString('base64')}'), true)`;
}
