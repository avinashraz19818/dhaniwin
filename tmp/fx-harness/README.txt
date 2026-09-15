Frontend execution harness (dev-only, not part of the site).

Usage (needs: npm i acorn in some node_modules reachable via /tmp):
  node makestub2.mjs <path to js/BetRule-DyIqbhnc.js or js/useWingo-*.js>
  node run.mjs betrule|usewingo

It rewrites the chunk's imports to Vue/DOM stubs and then actually CALLS
the store/page composables and drives countdown ticks, so runtime
crashes (ReferenceError / const-reassignment TypeError etc.) surface
here instead of on the user's phone. v31's blank-page bug was caught by
this harness as: "TypeError: Assignment to constant variable (line 398)".

run2.mjs = behavioural popup test (v33+):
  Scenario A: bet on the OPEN issue -> popup must NOT open (v32 fails this
  with result:null, exactly the round-10 screenshot).
  Scenario B: issue switch (timer end) -> exactly one popup with full slip.
Run: node makestub2.mjs ../../js/BetRule-DyIqbhnc.js && node run2.mjs
Generated stub_*.mjs / target.mjs are throwaway; do not commit them.
