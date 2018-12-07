#!/bin/bash

SCRIPTDIR="$(realpath "$(dirname ${0})")"
WORKDIR="$(realpath "$SCRIPTDIR/../../../../gitolite-data")"

/usr/bin/ssh -i "$WORKDIR/sshkey" "${@}"
