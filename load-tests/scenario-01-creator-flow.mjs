/**
 * Scenario 01 — Creator full flow (start → pottu placement → finalize)
 * Usage: node load-tests/scenario-01-creator-flow.mjs [--players=10000] [--concurrency=100] [--base=http://127.0.0.1:8001]
 */

const args = Object.fromEntries(
    process.argv.slice(2).map((arg) => {
        const [key, value] = arg.replace(/^--/, '').split('=');
        return [key, value ?? 'true'];
    })
);

const PLAYERS = Number(args.players ?? process.env.LOAD_TEST_PLAYERS ?? 10_000);
const CONCURRENCY = Number(args.concurrency ?? process.env.LOAD_TEST_CONCURRENCY ?? 100);
const BASE_URL = (args.base ?? process.env.LOAD_TEST_BASE ?? 'http://127.0.0.1:8001').replace(/\/$/, '');
const CAMPAIGN_SLUG = 'sundarikk-pottu-thodal';

const stats = {
    total: 0,
    success: 0,
    failed: 0,
    latencies: [],
    errors: {},
};

function recordError(step, status, message) {
    const key = `${step}:${status ?? 'network'}:${String(message).slice(0, 80)}`;
    stats.errors[key] = (stats.errors[key] ?? 0) + 1;
}

async function request(method, path, { token, body } = {}) {
    const started = performance.now();
    const headers = { Accept: 'application/json' };

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    if (token) {
        headers['X-Player-Session'] = token;
    }

    const response = await fetch(`${BASE_URL}${path}`, {
        method,
        headers,
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    const elapsed = performance.now() - started;
    let json = null;

    try {
        json = await response.json();
    } catch {
        json = null;
    }

    return { response, json, elapsed };
}

async function runPlayer(index) {
    const name = `LoadPlayer${index}`;

    const start = await request('POST', '/api/v1/sessions/start', {
        body: {
            campaign_slug: CAMPAIGN_SLUG,
            name,
            friend_name: 'Friend',
            challenge_title: `${name} challenged you!`,
            challenge_message: `Can you beat ${name}?`,
        },
    });

    if (!start.response.ok) {
        recordError('start', start.response.status, start.json?.message ?? start.response.statusText);
        return false;
    }

    const payload = start.json?.data ?? start.json;
    const uuid = payload?.session?.uuid;
    const token = payload?.token;
    const imageId = payload?.questions?.images?.[0]?.id;

    if (!uuid || !token || !imageId) {
        recordError('start', start.response.status, 'missing uuid/token/image');
        return false;
    }

    const placement = await request('POST', `/api/v1/sessions/${uuid}/pottu-placement`, {
        token,
        body: {
            image_id: imageId,
            x: 0.48,
            y: 0.32,
            size: 52,
            rotation: 0,
            board_width: 400,
            board_height: 600,
        },
    });

    if (!placement.response.ok) {
        recordError('placement', placement.response.status, placement.json?.message ?? placement.response.statusText);
        return false;
    }

    const finalize = await request('POST', `/api/v1/sessions/${uuid}/finalize`, { token });

    if (!finalize.response.ok) {
        recordError('finalize', finalize.response.status, finalize.json?.message ?? finalize.response.statusText);
        return false;
    }

    const challengeToken = finalize.json?.data?.challenge_token ?? finalize.json?.challenge_token;

    if (!challengeToken) {
        recordError('finalize', finalize.response.status, 'missing challenge_token');
        return false;
    }

    stats.latencies.push(start.elapsed + placement.elapsed + finalize.elapsed);
    return true;
}

function percentile(sorted, p) {
    if (!sorted.length) {
        return 0;
    }

    const index = Math.ceil((p / 100) * sorted.length) - 1;
    return sorted[Math.max(0, index)];
}

async function worker(queue) {
    while (queue.length) {
        const index = queue.shift();
        if (index === undefined) {
            return;
        }

        stats.total += 1;

        try {
            const ok = await runPlayer(index);
            if (ok) {
                stats.success += 1;
            } else {
                stats.failed += 1;
            }
        } catch (error) {
            stats.failed += 1;
            recordError('runtime', null, error.message);
        }

        if (stats.total % 500 === 0) {
            const pct = ((stats.total / PLAYERS) * 100).toFixed(1);
            process.stdout.write(`\rProgress: ${stats.total}/${PLAYERS} (${pct}%) | ok=${stats.success} fail=${stats.failed}`);
        }
    }
}

async function main() {
    console.log('Pottu Load Test — Scenario 01: Creator Flow');
    console.log(`Base URL     : ${BASE_URL}`);
    console.log(`Players      : ${PLAYERS.toLocaleString()}`);
    console.log(`Concurrency  : ${CONCURRENCY}`);
    console.log('');

    const health = await fetch(`${BASE_URL}/up`);

    if (!health.ok) {
        console.error(`Server not reachable at ${BASE_URL} (health check failed)`);
        process.exit(1);
    }

    const queue = Array.from({ length: PLAYERS }, (_, i) => i + 1);
    const startedAt = Date.now();

    await Promise.all(Array.from({ length: CONCURRENCY }, () => worker(queue)));

    const durationSec = (Date.now() - startedAt) / 1000;
    const sorted = [...stats.latencies].sort((a, b) => a - b);
    const rps = stats.total / durationSec;

    console.log('\n');
    console.log('=== Results ===');
    console.log(`Duration     : ${durationSec.toFixed(1)}s`);
    console.log(`Throughput   : ${rps.toFixed(1)} players/s`);
    console.log(`Success      : ${stats.success.toLocaleString()} (${((stats.success / stats.total) * 100).toFixed(2)}%)`);
    console.log(`Failed       : ${stats.failed.toLocaleString()}`);
    console.log(`Latency p50  : ${percentile(sorted, 50).toFixed(0)}ms (full flow)`);
    console.log(`Latency p95  : ${percentile(sorted, 95).toFixed(0)}ms`);
    console.log(`Latency p99  : ${percentile(sorted, 99).toFixed(0)}ms`);

    if (Object.keys(stats.errors).length) {
        console.log('\nTop errors:');
        Object.entries(stats.errors)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 10)
            .forEach(([key, count]) => console.log(`  ${count}x ${key}`));
    }

    process.exit(stats.failed > 0 ? 1 : 0);
}

main();
