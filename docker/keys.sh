#!/bin/sh
set -eu

umask 077
for name in db-password app-key demo-password; do
    path="/run/rin/$name"
    if [ ! -s "$path" ]; then
        case "$name" in
            app-key) printf 'base64:' > "$path.tmp" ;;
            demo-password) printf 'Demo-9!' > "$path.tmp" ;;
            *) : > "$path.tmp" ;;
        esac
        head -c 32 /dev/urandom | base64 | tr -d '\n' >> "$path.tmp"
        mv "$path.tmp" "$path"
    fi
done
