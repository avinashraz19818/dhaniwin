// Behavioural test for the win/loss popup gate (v33).
// usage: node run2.mjs
// Scenario A ("bet lagate hi popup" bug): a bet is placed on the CURRENT open
//   issue. The poller fires Ae() while the round is still open.
//   EXPECT: NO popup opens (popup must wait for timer end).
// Scenario B (popup at timer end): the issue switches (timer ended). The bet's
//   issue is now a finished history row.
//   EXPECT: exactly ONE popup, with full slip data (result row present).
globalThis.__uni = () => {
  const f = function() { return p; };
  const p = new Proxy(f, {
    get(t, k) {
      if (k === Symbol.toPrimitive) return () => 0;
      if (k === 'then') return undefined;
      if (k === Symbol.iterator) return undefined;
      return p;
    },
    set() { return true; }, apply() { return p; }, construct() { return p; },
  });
  return p;
};
globalThis.__onMounted = [];
globalThis.__opened = [];

const sleep = (ms) => new Promise(r => setTimeout(r, ms));
const CUR = '20260915100050999', NXT = '20260915100051000';

const mod = await import('./target.mjs');
const store = mod.u({ limitBetTime: 5, processSound: () => {}, startCallback: () => {}, useNext: true });

// capture popup open() calls
store.winner.value = { open: (payload) => { globalThis.__opened.push(payload); } };

// --- round is open: current issue = CUR ---
await store.updataCurrentIssue({ issueNumber: CUR, countdown: 30 });

// user bets on the CURRENT issue (instant-settle backend: bet is "settled"
// immediately, exactly like production)
store.mapBet.set(CUR, 1);

// poller + a direct call both try to pop while the round is still open
await store.getWinLossResult();
await sleep(900);
if (globalThis.__opened.length !== 0) {
  console.log('SCENARIO A FAIL: popup opened while round still open:', JSON.stringify(globalThis.__opened[0]).slice(0, 300));
  process.exit(1);
}
console.log('SCENARIO A OK: no popup while round open');

// --- timer ends: switch to next issue ---
await store.updataCurrentIssue({ issueNumber: NXT, countdown: 30 });
await store.getWinLossResult();   // history may still be stale here -> must NOT pop yet
const early = globalThis.__opened.length;
await sleep(7000);                // let the 400ms poller finish its job

if (globalThis.__opened.length !== 1) {
  console.log(`SCENARIO B FAIL: expected exactly 1 popup, got ${globalThis.__opened.length} (early=${early})`);
  process.exit(1);
}
const p0 = globalThis.__opened[0];
if (p0.issueNumber !== CUR) { console.log('SCENARIO B FAIL: wrong issue', p0.issueNumber); process.exit(1); }
if (p0.isWin !== true) { console.log('SCENARIO B FAIL: isWin wrong'); process.exit(1); }
if (!p0.result || p0.result.issueNumber !== CUR || !p0.result.number || !p0.result.color) {
  console.log('SCENARIO B FAIL: slip data missing ->', JSON.stringify(p0.result));
  process.exit(1);
}
console.log('SCENARIO B OK: one popup at timer end, slip filled (num=' + p0.result.number + ', color=' + p0.result.color + ')');
console.log('BEHAVIOUR TEST PASSED');
process.exit(0);
