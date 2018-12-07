#!/bin/bash

SCRIPTDIR="$(realpath "$(dirname ${0})")"
WORKDIR="$(realpath "$SCRIPTDIR/../../../../gitolite-data")"

export GIT_SSH="$SCRIPTDIR/ssh-wrapper.sh"
cd "$WORKDIR/gitolite-admin"
git "${@}"
