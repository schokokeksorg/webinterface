#!/bin/bash

SCRIPTDIR="$(realpath "$(dirname ${0})")"
WORKDIR="$(realpath "$SCRIPTDIR/../../../../gitolite-data")"

if [ -f "$WORKDIR/sshkey" ] ; then 
  echo 'SSH-Key exists!'
  exit 1
fi

ssh-keygen -t ecdsa -P '' -f "${WORKDIR}/sshkey"

echo 'Paste the following public key in gitolite-config and allow it to access the gitolite-admin repository!'
echo '----------------------'
cat "$WORKDIR/sshkey.pub"
echo '----------------------'
echo -n 'Press ENTER when ready...'
read

export GIT_SSH="$SCRIPTDIR/ssh-wrapper.sh"
cd "$WORKDIR"
git clone "git@git.$(hostname -d):gitolite-admin"

cd "gitolite-admin"
echo 'Probing pull...'
git pull
echo 'Probing push...'
git push

echo 'Everything set up!'


