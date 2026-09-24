#!/bin/sh
# onmaster.sh <command...>: run a command with core and all module repos on their default branch,
# with local changes stashed, then restore branches and changes.
E=$(cd "$(dirname "$0")/../.." && pwd)
# core plus every module that is its own git repo (directory or symlink)
REPOS="$E"
for d in "$E"/Modules/*; do [ -e "$d/.git" ] && REPOS="$REPOS $(cd "$d" && pwd -P)"; done
STATE=$(mktemp)
: > $STATE
for r in $REPOS; do
  cur=$(git -C $r rev-parse --abbrev-ref HEAD)
  def=master; git -C $r show-ref -q refs/heads/master || def=$(git -C $r symbolic-ref --short refs/remotes/origin/HEAD 2>/dev/null | sed 's#origin/##'); [ -z "$def" ] && def=$cur
  # repos with no master branch stay where they are (postprocess tracks stable, account uses main)
  git -C $r show-ref -q refs/heads/master || def=$cur
  case "$(basename $r)" in postprocess*) def=$cur ;; esac
  stashed=0
  if [ -n "$(git -C $r status --porcelain --untracked-files=no)" ]; then git -C $r stash push -q -m onmaster && stashed=1; fi
  [ "$cur" != "$def" ] && git -C $r checkout -q $def
  echo "$r $cur $stashed" >> $STATE
done
# wait for opcache to pick up the switched files
for i in $(seq 1 30); do timeout 0.25 tail -f /dev/null 2>/dev/null; done
"$@"
rc=$?
while read r cur stashed; do
  [ "$(git -C $r rev-parse --abbrev-ref HEAD)" != "$cur" ] && git -C $r checkout -q $cur
  [ "$stashed" = 1 ] && git -C $r stash pop -q
done < $STATE
for i in $(seq 1 30); do timeout 0.25 tail -f /dev/null 2>/dev/null; done
rm -f $STATE
exit $rc
