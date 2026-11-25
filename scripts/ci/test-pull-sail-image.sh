#!/usr/bin/env bash
# test-pull-sail-image.sh — uji lokal untuk scripts/ci/pull-sail-image.sh.
# Memakai stub `docker` dan `timeout`/`sleep` palsu; tidak menyentuh registry.
# Jalankan: bash scripts/ci/test-pull-sail-image.sh
set -uo pipefail

here="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
script="${here}/pull-sail-image.sh"
fail=0
total=0

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
mkdir -p "$work/bin"

# timeout: lewati argumen durasi lalu jalankan perintah.
cat >"$work/bin/timeout" <<'EOF'
#!/usr/bin/env bash
shift
exec "$@"
EOF

# sleep: no-op agar test cepat.
cat >"$work/bin/sleep" <<'EOF'
#!/usr/bin/env bash
exit 0
EOF

# docker: perilaku ditentukan file $DOCKER_SCRIPT (per baris: "<op> <image> <result>").
# result: ok | missing | netfail. Setiap pemanggilan dicatat ke $DOCKER_LOG.
cat >"$work/bin/docker" <<'EOF'
#!/usr/bin/env bash
# Bentuk pemanggilan: `manifest inspect <img>`, `pull <img>`, `tag <src> <dst>`.
case "$1" in
  manifest) op="manifest"; image="$3" ;;
  pull)     op="pull";     image="$2" ;;
  tag)      op="tag";      image="$2" ;;
  *)        op="$1";       image="$2" ;;
esac
echo "$op $image" >>"$DOCKER_LOG"
result=$(awk -v op="$op" -v img="$image" '$1==op && $2==img {print $3; exit}' "$DOCKER_SCRIPT")
result="${result:-missing}"
case "$op:$result" in
  manifest:ok|pull:ok)
    [ "$op" = "pull" ] && echo "pulled $image"
    [ "$op" = "manifest" ] && echo "{}"
    exit 0 ;;
  manifest:missing)
    echo "manifest unknown: manifest unknown" >&2; exit 1 ;;
  manifest:netfail)
    echo "dial tcp: i/o timeout" >&2; exit 1 ;;
  pull:missing)
    echo "Error response from daemon: not found" >&2; exit 1 ;;
  pull:netfail)
    echo "net/http: TLS handshake timeout" >&2; exit 1 ;;
  tag:*)
    echo "tag $image" >>"$DOCKER_LOG"; exit 0 ;;
esac
exit 1
EOF
chmod +x "$work/bin/"*

export GITHUB_REPOSITORY="gmedia/erp"
export GITHUB_SHA="0123456789abcdef"
export GITHUB_REF_NAME="chore/issue-512-ci-sail-pull"
export GITHUB_BASE_REF="main"
export CI_SAIL_IMAGE="ghcr.io/gmedia/erp/ci-sail:8.4"
export PULL_RETRIES=3 PULL_RETRY_DELAY=0 PULL_TIMEOUT=5
export PATH="$work/bin:$PATH"

sha_img="ghcr.io/gmedia/erp/ci-sail:sha-0123456"
ci_img="ghcr.io/gmedia/erp/ci-sail:8.4"
branch_img="ghcr.io/gmedia/erp/ci-sail:main"
latest_img="ghcr.io/gmedia/erp/ci-sail:latest"

# run_case <nama> <expect_exit> <expect_pulled_image|-> <expect_text|-> <script-lines...>
run_case() {
  local name="$1" want_exit="$2" want_pull="$3" want_text="$4"
  shift 4
  total=$((total + 1))
  export DOCKER_SCRIPT="$work/script.$total" DOCKER_LOG="$work/log.$total"
  : >"$DOCKER_LOG"
  printf '%s\n' "$@" >"$DOCKER_SCRIPT"
  local out code
  out=$(bash "$script" 2>&1)
  code=$?
  local ok=1
  [ "$code" -eq "$want_exit" ] || ok=0
  if [ "$want_pull" != "-" ]; then
    grep -qx "tag $want_pull" "$DOCKER_LOG" || ok=0
  fi
  if [ "$want_text" != "-" ]; then
    grep -qF -- "$want_text" <<<"$out" || ok=0
  fi
  if [ "$ok" -eq 1 ]; then
    echo "PASS $name"
  else
    echo "FAIL $name (exit=$code want=$want_exit)"
    echo "--- output ---"; echo "$out"; echo "--- docker log ---"; cat "$DOCKER_LOG"
    fail=$((fail + 1))
  fi
}

# 1. Kandidat sha- tidak ada: dilewati SEKALI tanpa retry, pakai ci-sail:8.4.
run_case "sha missing skips without retry" 0 "$ci_img" "tidak ada di registry" \
  "manifest $sha_img missing" "manifest $ci_img ok" "pull $ci_img ok"
# Pastikan tidak ada pull/probe ulang untuk sha- yang missing.
total=$((total + 1))
export DOCKER_SCRIPT="$work/script.$total" DOCKER_LOG="$work/log.$total"
: >"$DOCKER_LOG"
printf '%s\n' "manifest $sha_img missing" "manifest $ci_img ok" "pull $ci_img ok" >"$DOCKER_SCRIPT"
bash "$script" >/dev/null 2>&1
count=$(grep -c "^manifest $sha_img$" "$DOCKER_LOG")
if [ "$count" -eq 1 ]; then
  echo "PASS sha missing probed exactly once"
else
  echo "FAIL sha missing probed $count times"
  fail=$((fail + 1))
fi

# 2. Kegagalan jaringan pada image yang ada di-retry lalu berhasil.
total=$((total + 1))
export DOCKER_SCRIPT="$work/script.$total" DOCKER_LOG="$work/log.$total"
: >"$DOCKER_LOG"
printf '%s\n' "manifest $ci_img netfail" "pull $ci_img ok" >"$DOCKER_SCRIPT"
bash "$script" >/dev/null 2>&1
code=$?
probes=$(grep -c "^manifest $ci_img$" "$DOCKER_LOG")
if [ "$code" -eq 0 ] && [ "$probes" -eq "$PULL_RETRIES" ] && grep -qx "tag $ci_img" "$DOCKER_LOG"; then
  echo "PASS network failure retried then pulled"
else
  echo "FAIL network retry (exit=$code probes=$probes)"; cat "$DOCKER_LOG"; fail=$((fail + 1))
fi

# 3. Urutan prioritas: bila sha ada, dipakai (tidak mencoba ci-sail:8.4).
total=$((total + 1))
export DOCKER_SCRIPT="$work/script.$total" DOCKER_LOG="$work/log.$total"
: >"$DOCKER_LOG"
printf '%s\n' "manifest $sha_img ok" "pull $sha_img ok" >"$DOCKER_SCRIPT"
bash "$script" >/dev/null 2>&1
code=$?
if [ "$code" -eq 0 ] && grep -qx "tag $sha_img" "$DOCKER_LOG" && ! grep -q "ci-sail:8.4" "$DOCKER_LOG"; then
  echo "PASS sha candidate has priority"
else
  echo "FAIL priority order"; cat "$DOCKER_LOG"; fail=$((fail + 1))
fi

# 4. Fallback berurutan: sha & 8.4 & branch tidak ada, latest ada.
run_case "falls back to latest" 0 "$latest_img" "tidak ada di registry" \
  "manifest $sha_img missing" "manifest $ci_img missing" "manifest $branch_img missing" \
  "manifest $latest_img ok" "pull $latest_img ok"

# 5. Semua kandidat tidak ada: job harus gagal (exit 1), tidak ada tag.
total=$((total + 1))
export DOCKER_SCRIPT="$work/script.$total" DOCKER_LOG="$work/log.$total"
: >"$DOCKER_LOG"
printf '%s\n' "manifest $sha_img missing" "manifest $ci_img missing" \
  "manifest $branch_img missing" "manifest $latest_img missing" >"$DOCKER_SCRIPT"
out=$(bash "$script" 2>&1)
code=$?
if [ "$code" -eq 1 ] && grep -q "Unable to pull any CI Sail image" <<<"$out" && ! grep -q "^tag " "$DOCKER_LOG"; then
  echo "PASS all missing fails job"
else
  echo "FAIL all missing should exit 1 (exit=$code)"; echo "$out"; fail=$((fail + 1))
fi

# 6. Kandidat ada di manifest tetapi pull gagal permanen: pindah ke kandidat berikutnya.
run_case "pull failure moves to next candidate" 0 "$ci_img" "gagal di-pull" \
  "manifest $sha_img ok" "pull $sha_img missing" "manifest $ci_img ok" "pull $ci_img ok"

echo "----"
echo "total=$total failed=$fail"
[ "$fail" -eq 0 ]
