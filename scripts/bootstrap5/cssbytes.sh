#!/bin/sh
# cssbytes.sh: raw and gzipped bytes of the hand written CSS and of bootstrap.min.css.
# Hand written is every .css file in core and the module repos except the Bootstrap, icon and
# font files, node_modules, vendor and tools skipped, module symlinks followed, plus <style> blocks in views.
E=$(cd "$(dirname "$0")/../.." && pwd)
cd "$E"
HAND=$(find -L . -name "*.css" -not -path "*/node_modules/*" -not -path "*/vendor/*" -not -path "*/.git/*" \
  | grep -vE "bootstrap\.min\.css|bootstrap2-icons\.css|svg-icons\.css|montserrat\.css" | sort)
STYLE=$(grep -rlI "<style" --include=*.php --include=*.html . | grep -vE "node_modules|/vendor/|/scripts/|/tools/" \
  | xargs awk 'FNR==1{f=0} /<\/style>/{f=0;next} /<style/{f=1;next} f')
printf '%-22s %6s %6s %8s\n' "" files raw gzipped
printf '%-22s %6s %6s %8s\n' "hand written .css" "$(echo "$HAND" | wc -l)" \
  "$(echo "$HAND" | xargs cat | wc -c)" "$(echo "$HAND" | xargs cat | gzip -9 | wc -c)"
printf '%-22s %6s %6s %8s\n' "<style> blocks" "" "$(printf '%s' "$STYLE" | wc -c)" "$(printf '%s' "$STYLE" | gzip -9 | wc -c)"
printf '%-22s %6s %6s %8s\n' "bootstrap.min.css" 1 "$(wc -c < Lib/bootstrap5/css/bootstrap.min.css)" \
  "$(gzip -9 < Lib/bootstrap5/css/bootstrap.min.css | wc -c)"
