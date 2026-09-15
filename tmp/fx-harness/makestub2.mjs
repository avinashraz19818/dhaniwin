import * as acorn from '/tmp/node_modules/acorn/dist/acorn.mjs';
import fs from 'fs';

const VUE_IMPLS = {
  r: `(v) => ({ value: v })`,
  c: `(fn) => typeof fn === 'function' ? { get value() { return fn(); } } : { get value() { return fn.get(); }, set value(v) { fn.set(v); } }`,
  R: `() => {}`,
  P: `(fn) => { __onMounted.push(fn); }`,
  Y: `(o) => o`,
  ke: `(o) => o`,
  am: `(s, d) => (d === undefined ? {} : d)`,
  at: `() => ({ query: {}, params: {}, replace: async () => {} })`,
  t: `() => ({ query: {}, params: {}, replace: async () => {} })`,
  dn: `() => ({ value: 'visible' })`,
  d2: `() => ({ lotteryInline: { value: false } })`,
  dp: `{ Howl: class { constructor() {} load() {} play() {} pause() {} stop() {} seek() {} clear() {} duration() {} } }`,
  dq: `() => ({ soundBus: { on() {} }, syncMuted: () => {} })`,
  dt: `() => ({ localStore: { get: () => null, set() {}, remove() {} } })`,
  du: `new Proxy({}, { get: (t, k) => String(k) })`,
  ds: `() => ({ minutes: 0, seconds: 0 })`,
  dr: `() => {}`,
  b8: `(o) => o`,
  aZ: `(fn, fb) => (typeof fn === 'function' ? fn() : fb)`,
  aU: `() => {}`, a0: `() => {}`, b0: `() => {}`, b1: `(x) => String(x)`, a_: `() => {}`, aY: `(c) => c`,
  ca: `() => {}`, aD: `() => 'CLOSED'`, aH: `(x) => String(x)`, u: `() => ({})`, dx: `(ms) => new Promise(r => setTimeout(r, ms))`,
  f: `() => ({})`, aX: `(fn, fb) => fb`, c0: `(fn) => fn`, dy: `(x) => x`, dO: `function (o) { this.get = async () => ({}); this.post = async () => ({}); }`,
};

function importsOf(src) {
  const ast = acorn.parse(src, { ecmaVersion: 'latest', sourceType: 'module' });
  const out = [];
  for (const node of ast.body) if (node.type === 'ImportDeclaration')
    out.push({ source: node.source.value, names: node.specifiers.filter(s => s.type === 'ImportSpecifier').map(s => s.imported.name) });
  return out;
}

const target = process.argv[2];
const src = fs.readFileSync(target, 'utf8');
let rewritten = src;
const stubs = {};
for (const imp of importsOf(src)) {
  const stubName = 'stub_' + imp.source.replace(/[^a-zA-Z0-9]/g, '_') + '.mjs';
  if (!stubs[stubName]) {
    let body = '';
    if (imp.source.includes('index-BgAwOX9Q')) {
      body = `globalThis.__onMounted = globalThis.__onMounted || [];\n` +
        imp.names.map(nm => VUE_IMPLS[nm] !== undefined
          ? `export const ${nm} = ${VUE_IMPLS[nm]};`
          : `export const ${nm} = (...a) => globalThis.__uni ? globalThis.__uni() : a[0];`).join('\n') + '\n';
    } else if (imp.source.includes('useWorkerIntervalFn')) {
      body = `export const u = (cb, ms, opts) => { globalThis.__tickCb = cb; return { pause() { globalThis.__tickOn = false; }, resume() { globalThis.__tickOn = true; }, start() { globalThis.__tickOn = true; }, isActive: { get value() { return globalThis.__tickOn !== false; }, set value(v) { globalThis.__tickOn = v; } } }; };\n`;
    } else if (imp.source.includes('WingoSkeleton')) {
      body = `const L = (v) => ({ value: v });
export const a = () => ({
  synchronizer: { getCurrentTime: () => Date.now() },
  gameCode: L('WinGo_30S'), lotteryCode: L('WinGo'), gameInfo: L({}),
  triggerTimer: { on() {} }, trigger: { emit() {} },
  setLotteryCode() {}, getGameInfo: async () => {}, getGameList: async () => {},
  updateBalance: async () => ({}), getUserInfo: async () => {}, onBetTrigger: () => {},
  token: L('testtoken'), user: L(null), balance: L(1), state: {}, redirectUrl: L(''),
  skin: L(1), skincolor: L(''), serviceTime: L(Date.now()), gameList: L([]), currentGame: L({}),
  lottery_skin: L('skin_wade'), balanceLoading: L(false), dollarSign: L('Rs'),
  useProvide: () => {}, onLotteryJump: () => {}, showBeck: L(false), follow: L(false), lang: L('en'),
  webSocketUrl: '',
});
export const d = async () => ({ result: true, data: { current: { issueNumber: '20260915100050999', startTime: Date.now(), endTime: Date.now() + 30000 }, intervalMinute: 0.5, gameCode: 'WinGo_30S' } });
export const e = async () => ({ result: true, data: { list: [{ issueNumber: '20260915100050999', number: '5' }, { issueNumber: '20260915100050998', number: '3' }], totalPage: 1 } });
export const f = async (p) => ({ result: true, data: { status: true, winAmount: 7.5, issueNumber: (p && p.issueNumber) || 'x' } });
export const h = async () => ({ result: false });
export const i = async () => ({ result: false });
${imp.names.filter(n => !['a','d','e','f','h','i'].includes(n)).map(n => `export const ${n} = (...q) => Promise.resolve({ result: false });`).join('\n')}
`;
    } else {
      body = imp.names.map(nm => `export const ${nm} = (...a) => globalThis.__uni ? globalThis.__uni() : a[0];`).join('\n') + '\n';
    }
    stubs[stubName] = body;
  }
  rewritten = rewritten.split(imp.source).join('./' + stubName);
}
for (const [name, body] of Object.entries(stubs)) fs.writeFileSync(name, body);
fs.writeFileSync('target.mjs', rewritten);
console.log('OK ->', 'target.mjs,', Object.keys(stubs).length, 'stubs');
