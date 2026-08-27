# brace-expansion compatibility adapter

Legacy build dependencies in this project still expect the CommonJS API from
`brace-expansion` 1.x, where requiring the package returns the expansion
function directly. The security-fixed 5.0.8 release exposes that function as a
named export instead.

This private adapter preserves the older callable API while delegating all
expansion work to the unmodified 5.0.8 implementation. The top-level npm
override makes legacy consumers use this adapter, so the vulnerable 1.x
implementation is not installed.
