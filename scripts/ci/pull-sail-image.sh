#!/usr/bin/env bash
# pull-sail-image.sh — pull image CI Sail dari daftar kandidat berurutan.
#
# Urutan kandidat (sama seperti sebelumnya):
#   1. ghcr.io/<repo>/ci-sail:sha-<7 char SHA>   (hanya ada bila path Docker berubah)
#   2. $CI_SAIL_IMAGE                            (ci-sail:8.4)
#   3. ghcr.io/<repo>/ci-sail:<branch>
#   4. ghcr.io/<repo>/ci-sail:latest
#
# Perilaku:
#   - Probe `docker manifest inspect` sekali per kandidat. Bila registry menjawab
#     "manifest unknown" / "not found", kandidat langsung dilewati tanpa retry.
#   - Error lain (jaringan, timeout, rate limit) di-retry sampai PULL_RETRIES kali.
#     Setelah itu tetap dicoba `docker pull` (dengan retry sendiri) sebelum pindah.
#   - Pull yang gagal juga di-retry hanya untuk kegagalan sementara.
#   - Bila semua kandidat gagal: exit 1 (job tetap gagal).
#
# Env wajib : GITHUB_REPOSITORY, GITHUB_SHA
# Env opsional: CI_SAIL_IMAGE, GITHUB_BASE_REF, GITHUB_REF_NAME
# Tuning    : PULL_RETRIES (3), PULL_RETRY_DELAY (10 s), PULL_TIMEOUT (60 s)
# Hasil     : image terpilih di-tag sebagai sail-8.4/app
set -uo pipefail

retries="${PULL_RETRIES:-3}"
retry_delay="${PULL_RETRY_DELAY:-10}"
pull_timeout="${PULL_TIMEOUT:-60}"
missing_pattern='manifest unknown|not found|no such manifest|404'

repo="${GITHUB_REPOSITORY:?GITHUB_REPOSITORY wajib diset}"
sha="${GITHUB_SHA:?GITHUB_SHA wajib diset}"
branch_tag="${GITHUB_BASE_REF:-${GITHUB_REF_NAME:-}}"
branch_tag=$(printf '%s' "$branch_tag" | tr -cd '[:alnum:]._-')

candidates=(
  "ghcr.io/${repo}/ci-sail:sha-${sha::7}"
  "${CI_SAIL_IMAGE:-}"
  "ghcr.io/${repo}/ci-sail:${branch_tag}"
  "ghcr.io/${repo}/ci-sail:latest"
)

# Mengembalikan 0 bila image ada, 1 bila registry menyatakan tidak ada,
# 2 bila error lain yang masih mungkin sementara.
probe_image() {
  local image="$1" attempt out
  for ((attempt = 1; attempt <= retries; attempt++)); do
    if out=$(timeout "${pull_timeout}s" docker manifest inspect "$image" 2>&1); then
      return 0
    fi
    if grep -qiE "$missing_pattern" <<<"$out"; then
      return 1
    fi
    if ((attempt < retries)); then
      echo "Probe ${image} gagal (${attempt}/${retries}), coba lagi dalam ${retry_delay}s..."
      sleep "$retry_delay"
    fi
  done
  return 2
}

pull_image() {
  local image="$1" attempt
  for ((attempt = 1; attempt <= retries; attempt++)); do
    if timeout "${pull_timeout}s" docker pull "$image"; then
      return 0
    fi
    if ((attempt < retries)); then
      echo "Pull ${image} gagal (${attempt}/${retries}), coba lagi dalam ${retry_delay}s..."
      sleep "$retry_delay"
    fi
  done
  return 1
}

for image in "${candidates[@]}"; do
  if [ -z "$image" ]; then
    continue
  fi

  probe_image "$image"
  case $? in
    1)
      echo "CI Sail image ${image} tidak ada di registry, lewati."
      continue
      ;;
    2)
      echo "Probe ${image} tidak konklusif; tetap mencoba pull."
      ;;
  esac

  if pull_image "$image"; then
    docker tag "$image" sail-8.4/app
    exit 0
  fi
  echo "CI Sail image ${image} gagal di-pull, lanjut ke kandidat berikutnya."
done

echo "::error::Unable to pull any CI Sail image candidate after retries."
exit 1
