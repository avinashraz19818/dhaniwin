// usage: node run.mjs <betrule|usewingo>
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
const which = process.argv[2];
try {
  const mod = await import('./target.mjs');
  console.log('MODULE LOADED, exports:', Object.keys(mod));
  if (which === 'betrule') {
    const store = mod.u({ limitBetTime: 5, processSound: (e) => {}, startCallback: () => {}, useNext: true });
    console.log('ut() SETUP OK');
    // drive ticks 6..0 twice to exercise switch + poller + popup
    for (let round = 0; round < 2; round++) {
      for (let c = 6; c >= 0; c--) {
        globalThis.__tickCb && await globalThis.__tickCb();
      }
      await new Promise(r => setTimeout(r, 300));
    }
    await new Promise(r => setTimeout(r, 1200));
    console.log('TICKS OK');
  } else {
    const w = mod.u({ skin: 1, video: false });
    console.log('Nt() SETUP OK');
    for (const fn of globalThis.__onMounted) { await fn(); }
    console.log('onMounted OK');
    for (let round = 0; round < 2; round++) {
      for (let c = 6; c >= 0; c--) { globalThis.__tickCb && await globalThis.__tickCb(); }
      await new Promise(r => setTimeout(r, 300));
    }
    await new Promise(r => setTimeout(r, 1500));
    console.log('TICKS OK');
  }
  process.exit(0);
} catch (e) {
  console.error('CRASH:', e && e.stack ? e.stack.split('\n').slice(0, 6).join('\n') : e);
  process.exit(1);
}
